<?php
require_once __DIR__ . '/class-tax-id.php';
require_once __DIR__ . '/class-client-ip.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The only transport allowed to send fiscal API credentials. */
class WooCommerceNFe_HTTP {
	public static function valid_url( $url, $service ) {
		if ( ! is_string( $url ) || preg_match( '/[\x00-\x20\x7f\\\\<>\x22\x27]/', $url ) ) {
			return false;
		}
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || ( $parts['scheme'] ?? '' ) !== 'https' ||
			isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['fragment'] ) ||
			( isset( $parts['port'] ) && $parts['port'] !== 443 ) ) {
			return false;
		}
		$host = strtolower( $parts['host'] ?? '' );
		$path = $parts['path'] ?? '';
		// No wildcard hosts, encoded paths, traversal or arbitrary API endpoints.
		if ( preg_match( '#(?:%|/\.{1,2}(?:/|$)|//)#', $path ) ) {
			return false;
		}
		if ( $service === 'nfe' ) {
			return in_array( $host, array( 'webmaniabr.com', 'webmania.com.br' ), true ) &&
				! isset( $parts['query'] ) && (bool) preg_match( '#^/api/1/nfe/(sefaz|certificado|emissao|consulta|cancelar|inutilizar|cartacorrecao|devolucao|ajuste|complementar)/$#D', $path );
		}
		if ( $service === 'nfse' ) {
			return in_array( $host, array( 'api.webmaniabr.com', 'api.webmania.com.br' ), true ) &&
				! isset( $parts['query'] ) && (bool) preg_match( '#^/2/nfse/(emissao|cancelar|consulta/[a-zA-Z0-9-]{1,64})$#D', $path );
		}
		if ( $service === 'document' ) {
			return in_array( $host, array( 'nfe.webmaniabr.com', 'nfe.webmania.com.br', 'webmaniabr.com', 'webmania.com.br', 'api.webmaniabr.com', 'api.webmania.com.br' ), true ) &&
				(bool) preg_match( '#^/(danfe|xmlnfe|xmlnfse|darps|nfse)/[a-zA-Z0-9/_-]+$#D', $path );
		}
		return false;
	}

	public static function headers( $credentials, $service, $url ) {
		$headers = array( 'Accept: application/json', 'Content-Type: application/json' );
		if ( $service === 'document' ) {
			$host = strtolower( wp_parse_url( $url, PHP_URL_HOST ) );
			$service = in_array( $host, array( 'api.webmaniabr.com', 'api.webmania.com.br' ), true ) ? 'nfse' : 'nfe';
			$headers = array( 'Accept: application/pdf, application/xml, text/xml' );
		}
		$fields = $service === 'nfse' ? array( 'bearer_access_token' => 'Authorization: Bearer ' ) : array(
			'consumer_key' => 'X-Consumer-Key: ', 'consumer_secret' => 'X-Consumer-Secret: ',
			'oauth_access_token' => 'X-Access-Token: ', 'oauth_access_token_secret' => 'X-Access-Token-Secret: ',
		);
		foreach ( $fields as $key => $prefix ) {
			$value = $credentials[ $key ] ?? '';
			if ( ! is_string( $value ) || preg_match( '/[\x00-\x20\x7f]/', $value ) ) {
				return new WP_Error( 'invalid_credentials', 'Credenciais de acesso inválidas.' );
			}
			if ( $value !== '' ) {
				$headers[] = $prefix . $value;
			}
		}
		return $headers;
	}

	public static function request( $method, $url, $data, $credentials, $service ) {
		if ( ! self::valid_url( $url, $service ) || ! in_array( $method, array( 'GET', 'POST', 'PUT' ), true ) ) {
			return new WP_Error( 'blocked_destination', 'Destino de comunicação não autorizado.' );
		}
		$headers = self::headers( $credentials, $service, $url );
		if ( is_wp_error( $headers ) ) {
			return $headers;
		}
		$limit = $service === 'document' ? 10 * 1024 * 1024 : 2 * 1024 * 1024;
		$request_headers = array();
		foreach ( $headers as $header ) {
			list( $name, $value ) = explode( ':', $header, 2 );
			$request_headers[ $name ] = ltrim( $value );
		}
		$args = array(
			'method' => $method,
			'headers' => $request_headers,
			'timeout' => $service === 'document' ? 30 : ( preg_match( '#/(certificado|sefaz)/$#', $url ) ? 15 : 300 ),
			'sslverify' => true,
			// Credentials must never be forwarded to another host, even on a redirect.
			'redirection' => 0,
			'reject_unsafe_urls' => true,
			'blocking' => true,
			'limit_response_size' => $limit + 1,
		);
		if ( $service !== 'document' ) {
			$args['body'] = wp_json_encode( WooCommerceNFe_TaxId::normalize_cnpj_fields( $data ) );
		}
		$response = wp_safe_remote_request( $url, $args );
		$result = ! is_wp_error( $response );
		$status = $result ? (int) wp_remote_retrieve_response_code( $response ) : 0;
		$body = $result ? wp_remote_retrieve_body( $response ) : '';
		if ( ! is_string( $body ) || strlen( $body ) > $limit ) {
			$result = false;
			$body = '';
		}
		if ( $service !== 'document' && $result !== false && in_array( $status, array( 400, 401, 403, 404, 405, 422 ), true ) ) {
			$rejection = json_decode( $body );
			if ( is_object( $rejection ) && ( isset( $rejection->error ) || isset( $rejection->errors ) ) ) { return $body; }
		}
		if ( $result === false || $status < 200 || $status >= 300 ) {
			// Never echo URLs, raw bodies or headers (they may contain secrets).
			return new WP_Error( 'api_transport', 'Não foi possível confirmar a resposta da Webmania. Consulte a nota antes de tentar emitir novamente.', array( 'status' => $status, 'uncertain' => $method !== 'GET' ) );
		}
		return $body;
	}

	public static function json_request( $method, $url, $data, $credentials, $service ) {
		$body = self::request( $method, $url, $data, $credentials, $service );
		if ( is_wp_error( $body ) ) {
			$details = $body->get_error_data();
			return (object) array( 'error' => $body->get_error_message(), 'request_uncertain' => ! empty( $details['uncertain'] ) );
		}
		$response = json_decode( $body );
		$has_result = is_object( $response ) && ( isset( $response->error ) || isset( $response->errors ) || isset( $response->status ) || isset( $response->expiration ) );
		$missing_invoice = $has_result && strpos( $url, '/emissao' ) !== false && ! isset( $response->error ) && ! isset( $response->errors ) && ( $response->status ?? '' ) !== 'reprovado' && empty( $response->uuid );
		if ( ! $has_result || $missing_invoice || json_last_error() !== JSON_ERROR_NONE ) {
			return (object) array( 'error' => 'Resposta inválida da Webmania. Consulte a nota antes de tentar emitir novamente.', 'request_uncertain' => $method !== 'GET' );
		}
		// Even diagnostic API responses must not reflect authentication values into HTML/logs.
		$secrets = array_values( array_filter( $credentials, static function ( $value ) { return is_string( $value ) && strlen( $value ) >= 8; } ) );
		return self::redact( $response, $secrets );
	}

	private static function redact( $value, $secrets ) {
		if ( is_object( $value ) || is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				if ( is_object( $value ) ) { $value->$key = self::redact( $item, $secrets ); }
				else { $value[$key] = self::redact( $item, $secrets ); }
			}
		} elseif ( is_string( $value ) && $secrets ) {
			$value = str_replace( $secrets, '[redacted]', $value );
		}
		return $value;
	}
}
