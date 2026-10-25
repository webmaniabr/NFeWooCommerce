<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

class WooCommerceNFe_Callback {
	public static function authenticate( $model, $query, $payload = null ) {
		$id = $query['order_id'] ?? '';
		$token = $query['token'] ?? '';
		if ( ! in_array( $model, array( 'nfe', 'nfse' ), true ) || ! is_scalar( $id ) || ! ctype_digit( (string) $id ) ) {
			return new WP_Error( 'callback_auth', 'Notificação não autorizada.', array( 'status' => 401 ) );
		}
		$order = wc_get_order( (int) $id );
		// wc_get_order() also returns refunds, which have no order key (fatal Error on a public endpoint).
		if ( ! $order instanceof WC_Order ) { $order = false; }
		if ( $order && WooCommerceNFe_Security::verify_callback_token( $order, $model, $token ) ) {
			return $order;
		}
		// Old notification URLs contain a customer-visible key. They can only request
		// a server-to-server refresh of an already stored UUID, never insert a note.
		$key = $query['order_key'] ?? '';
		$uuid = is_array( $payload ) ? ( $payload['uuid'] ?? '' ) : '';
		if ( ! isset( $query['token'] ) && $order && is_string( $key ) && $key !== '' && hash_equals( $order->get_order_key(), $key ) && is_string( $uuid ) && $uuid !== '' ) {
			foreach ( (array) $order->get_meta( 'nfe', true ) as $record ) {
				$record_model = $record['modelo'] ?? 'nfe';
				if ( ( $record['uuid'] ?? '' ) === $uuid && ( $model === 'nfe' ? $record_model === 'nfe' : in_array( $record_model, array( 'nfse', 'lote_rps' ), true ) ) ) {
					return $order;
				}
			}
		}
		return new WP_Error( 'callback_auth', 'Notificação não autorizada.', array( 'status' => 401 ) );
	}

	public static function receive( $model ) {
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) !== 'POST' ) {
			wp_send_json_error( array( 'message' => 'Método não permitido.' ), 405 );
		}
		if ( $model === 'nfe' ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- External signed callback; authenticate() validates its order token and process() confirms the invoice via the API.
			$payload = wp_unslash( $_POST );
			if ( strlen( wp_json_encode( $payload ) ) > 65536 ) { wp_send_json_error( array(), 413 ); }
		} else {
			$raw = file_get_contents( 'php://input', false, null, 0, 65537 );
			if ( strlen( $raw ) > 65536 ) { wp_send_json_error( array(), 413 ); }
			$payload = json_decode( $raw, true );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Server callback authentication uses a per-order HMAC, not a browser nonce.
		$order = self::authenticate( $model, wp_unslash( $_GET ), $payload );
		if ( is_wp_error( $order ) ) { wp_send_json_error( array( 'message' => $order->get_error_message() ), 401 ); }
		if ( ! WooCommerceNFe_Security::check_rate_limit( 'callback_' . $order->get_id(), 10, 60 ) ) { wp_send_json_error( array( 'message' => 'Limite de notificações excedido.' ), 429 ); }
		$result = self::process( $order, $model, $payload );
		if ( is_wp_error( $result ) ) {
			$details = $result->get_error_data();
			wp_send_json_error( array( 'message' => $result->get_error_message() ), $details['status'] ?? 400 );
		}
		wp_send_json_success();
	}

	/** Callback payloads only identify the record; status and URLs come from the API. */
	public static function process( $order, $model, $payload ) {
		$uuid = is_array( $payload ) ? ( $payload['uuid'] ?? '' ) : '';
		if ( ! is_string( $uuid ) || ! preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/iD', $uuid ) ) {
			return new WP_Error( 'callback_payload', 'Identificador de nota inválido.' );
		}
		$lock = WooCommerceNFe_Security::acquire_lock( $order->get_id() );
		if ( ! $lock ) { return new WP_Error( 'callback_busy', 'Pedido em processamento. Reenvie a notificação.', array( 'status' => 409 ) ); }
		try {
			$config = new WooCommerceNFeIssue;
			$config->get_credentials( $order->get_id() );
			$api = $model === 'nfe' ? new NFe( $config->settings ) : new NFSe( $config->settings['bearer_access_token'] );
			$response = $api->consultaNotaFiscal( $uuid );
			if ( ! is_object( $response ) || isset( $response->error ) || ( $response->uuid ?? '' ) !== $uuid ) {
				return new WP_Error( 'callback_query', 'Não foi possível confirmar a nota na Webmania.', array( 'status' => 502 ) );
			}
			// Reject an explicit order mismatch if the API includes the original data.
			$original = $response->data ?? null;
			if ( is_string( $original ) ) { $original = json_decode( $original ); }
			$id = $response->ID ?? ( is_object( $original ) ? ( $original->ID ?? null ) : null );
			if ( $id !== null && ( ! is_scalar( $id ) || (string) $id !== (string) $order->get_id() ) ) {
				return new WP_Error( 'callback_order', 'Nota vinculada a outro pedido.', array( 'status' => 403 ) );
			}
			$order = wc_get_order( $order->get_id() );
			$order->read_meta_data( true );
			$invoices = $order->get_meta( 'nfe', true );
			$invoices = is_array( $invoices ) ? $invoices : array();
			$invoices = array_map( array( 'WooCommerceNFe_Security', 'sanitize_invoice' ), $invoices );
			$invoice = self::invoice( $response, $model );
			if ( is_wp_error( $invoice ) ) { return $invoice; }
			self::upsert( $invoices, $invoice );
			if ( $invoice['modelo'] === 'lote_rps' && $invoice['status'] === 'processado' ) {
				$children = $response->info_nfse ?? array();
				if ( ! is_array( $children ) || count( $children ) > 100 ) { return new WP_Error( 'callback_batch', 'Lote inválido.' ); }
				foreach ( $children as $child ) {
					$child_id = is_object( $child ) ? ( $child->uuid ?? '' ) : '';
					if ( ! is_string( $child_id ) || ! preg_match( '/^[a-f0-9-]{36}$/iD', $child_id ) ) { return new WP_Error( 'callback_batch', 'Nota do lote inválida.' ); }
					$detail = $api->consultaNotaFiscal( $child_id );
					if ( ! is_object( $detail ) || ( $detail->uuid ?? '' ) !== $child_id || isset( $detail->error ) ) { return new WP_Error( 'callback_query', 'Não foi possível confirmar o lote.', array( 'status' => 502 ) ); }
					$item = self::invoice( $detail, 'nfse' );
					if ( is_wp_error( $item ) ) { return $item; }
					self::upsert( $invoices, $item );
				}
			}
			$order->update_meta_data( 'nfe', $invoices );
			$order->delete_meta_data( '_wmbr_uncertain_' . $model );
			$order->save();
			return true;
		} finally {
			WooCommerceNFe_Security::release_lock( $order->get_id(), $lock );
		}
	}

	public static function invoice( $response, $model ) {
		$uuid = $response->uuid ?? '';
		if ( ! is_string( $uuid ) || ! preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/iD', $uuid ) ) { return new WP_Error( 'invoice_uuid', 'Identificador de nota inválido.' ); }
		$status = $response->status ?? '';
		if ( ! is_string( $status ) || ! in_array( $status, array( 'aprovado', 'reprovado', 'cancelado', 'denegado', 'processamento', 'processando', 'processado', 'agendado', 'contingencia', 'substituido', 'pendente' ), true ) ) {
			return new WP_Error( 'callback_status', 'Status de nota inválido.' );
		}
		$record = array( 'uuid' => (string) $response->uuid, 'status' => $status, 'modelo' => $model === 'nfe' ? 'nfe' : ( $response->modelo ?? 'nfse' ), 'data' => date_i18n( 'd/m/Y' ) );
		if ( ! in_array( $record['modelo'], array( 'nfe', 'nfse', 'lote_rps' ), true ) ) { return new WP_Error( 'callback_model', 'Modelo de nota inválido.' ); }
		// Missing optional fields in a later status response must not erase saved invoice data.
		if ( isset( $response->chave ) ) { $record['chave_acesso'] = WooCommerceNFe_TaxId::normalize_access_key( $response->chave ); }
		if ( isset( $response->recibo ) && is_scalar( $response->recibo ) ) { $record['n_recibo'] = (int) $response->recibo; }
		$number_fields = $model === 'nfe' ? array( 'nfe' ) : ( $record['modelo'] === 'lote_rps' ? array( 'numero_lote', 'numero' ) : array( 'numero', 'nfe', 'numero_lote' ) );
		foreach ( $number_fields as $field ) {
			if ( isset( $response->$field ) && is_scalar( $response->$field ) ) {
				$record['n_nfe'] = (int) $response->$field;
				if ( $record['n_nfe'] !== 0 ) { break; }
			}
		}
		if ( $model === 'nfe' && isset( $response->serie ) && is_scalar( $response->serie ) ) {
			$record['n_serie'] = (int) $response->serie;
		} elseif ( $model !== 'nfe' && isset( $response->serie_rps, $response->numero_rps ) ) {
			// RPS identifiers are strings: municipal providers use letters and leading zeroes.
			$series = $response->serie_rps ?? '';
			$number = $response->numero_rps ?? '';
			$record['n_serie'] = ( is_scalar( $series ) ? sanitize_text_field( (string) $series ) : '' ) . ':' . ( is_scalar( $number ) ? sanitize_text_field( (string) $number ) : '' );
		}
		foreach ( array( 'xml' => 'url_xml', 'danfe' => 'url_danfe', 'danfe_simples' => 'url_danfe_simplificada', 'danfe_etiqueta' => 'url_danfe_etiqueta', 'pdf_nfse' => 'url_pdf', 'pdf_rps' => 'pdf_rps' ) as $source => $key ) {
			if ( ! isset( $response->$source ) ) { continue; }
			$value = $response->$source;
			$record[$key] = WooCommerceNFe_HTTP::valid_url( $value, 'document' ) ? $value : '';
		}
		if ( isset( $response->motivo ) ) {
			$reasons = is_array( $response->motivo ) ? $response->motivo : array( $response->motivo );
			$record['motivo'] = implode( ' | ', array_map( 'sanitize_text_field', array_filter( $reasons, 'is_scalar' ) ) );
		}
		return $record;
	}

	public static function upsert( &$invoices, $invoice ) {
		foreach ( $invoices as $key => $existing ) {
			if ( is_array( $existing ) && ( $existing['uuid'] ?? '' ) === $invoice['uuid'] ) {
				if ( ! empty( $existing['data'] ) && is_string( $existing['data'] ) ) { $invoice['data'] = $existing['data']; }
				$invoices[$key] = array_merge( $existing, $invoice );
				return;
			}
		}
		$invoices[] = $invoice;
	}
}
