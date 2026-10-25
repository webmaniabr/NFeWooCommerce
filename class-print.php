<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

class WooCommerceNFePrint extends WooCommerceNFe {
	private $files_folder;

	public function __construct() {
		$this->files_folder = WooCommerceNFe_Security::private_directory();
	}

	public function print( $order_ids = array(), $type = 'normal' ) {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! $this->files_folder || ! in_array( $type, array( 'normal', 'simplificada', 'etiqueta' ), true ) ) { return false; }
		$data = array();
		foreach ( array_unique( array_filter( array_map( array( 'WooCommerceNFe_Security', 'validate_order_id' ), (array) $order_ids ) ) ) as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order || ! current_user_can( 'edit_shop_order', $order_id ) ) { return false; }
			$invoices = $order->get_meta( 'nfe' );
			if ( ! is_array( $invoices ) || ! $invoices ) { continue; }
			$nf = WooCommerceNFe_Security::sanitize_invoice( end( $invoices ) );
			$model = $nf['modelo'] ?? 'nfe';
			if ( $model === 'lote_rps' || ( $model === 'nfse' && ( $type !== 'normal' || empty( $nf['pdf_rps'] ) ) ) ) { continue; }
			$base = $nf['url_danfe'] ?? '';
			if ( ! $base && preg_match( '/^[0-9]{6}[A-Z0-9]{12}[0-9]{26}$/D', $nf['chave_acesso'] ?? '' ) ) { $base = 'https://nfe.webmania.com.br/danfe/' . $nf['chave_acesso'] . '/'; }
			if ( $type === 'normal' ) {
				$url = $model === 'nfse' ? $nf['pdf_rps'] : $base;
			} elseif ( $type === 'simplificada' ) {
				$url = ! empty( $nf['url_danfe_simplificada'] ) ? $nf['url_danfe_simplificada'] : str_replace( '/danfe/', '/danfe/simples/', $base );
			} else {
				$url = ! empty( $nf['url_danfe_etiqueta'] ) ? $nf['url_danfe_etiqueta'] : str_replace( '/danfe/', '/danfe/etiqueta/', $base );
			}
			$data[] = array( 'url' => $url, 'order_id' => $order_id );
		}
		$result = $data ? $this->createPDF( $data ) : false;
		if ( ! empty( $result['result'] ) ) {
			$link = wp_nonce_url( add_query_arg( array( 'action' => 'wmbr_download_pdf', 'file' => $result['file'] ), admin_url( 'admin-post.php' ) ), 'wmbr_pdf_' . $result['file'] );
			$this->add_success( 'Arquivo de impressão gerado. <a href="' . esc_url( $link ) . '" target="_blank" rel="noopener noreferrer">Baixar PDF</a> (disponível por 10 minutos).' );
		} else {
			$this->add_error( 'Erro ao gerar arquivo de impressão. Verifique os documentos selecionados.' );
		}
		return $result;
	}

	private function createPDF( $data ) {
		// Hosts often disable set_time_limit(); on PHP 8 calling it is then a fatal Error (not silenceable).
		if ( function_exists( 'wc_set_time_limit' ) ) { wc_set_time_limit( 300 ); }
		$files = array();
		$output = false;
		try {
			$pdf = new WooCommerceNFe_PDFMerger;
			foreach ( $data as $item ) {
				$content = self::curl_get_file_contents( $item['url'], $item['order_id'] );
				if ( ! is_string( $content ) || strpos( $content, '%PDF-' ) !== 0 || strpos( $content, '%%EOF' ) === false ) { throw new RuntimeException( 'Invalid PDF' ); }
				$file = tempnam( $this->files_folder, 'source-' );
				if ( ! $file ) { throw new RuntimeException( 'Temporary file unavailable' ); }
				$files[] = $file;
				if ( ! WooCommerceNFe_Security::filesystem()->chmod( $file, 0600 ) ) { throw new RuntimeException( 'Private file permissions unavailable' ); }
				if ( file_put_contents( $file, $content, LOCK_EX ) !== strlen( $content ) ) { throw new RuntimeException( 'Incomplete write' ); }
				$pdf->addPDF( $file, 'all' );
			}
			$output = tempnam( $this->files_folder, 'print-' );
			if ( ! $output ) { throw new RuntimeException( 'Temporary file unavailable' ); }
			if ( ! WooCommerceNFe_Security::filesystem()->chmod( $output, 0600 ) ) { throw new RuntimeException( 'Private file permissions unavailable' ); }
			$pdf->merge( 'file', $output );
			$token = bin2hex( random_bytes( 32 ) );
			set_transient( 'wmbr_pdf_' . $token, array( 'path' => $output, 'user_id' => get_current_user_id(), 'orders' => array_column( $data, 'order_id' ) ), 600 );
			wp_schedule_single_event( time() + 600, 'wmbr_remove_private_pdf', array( basename( $output ) ) );
			return array( 'result' => true, 'file' => $token );
		} catch ( Throwable $error ) {
			if ( $output && is_file( $output ) ) { wp_delete_file( $output ); }
			return array( 'result' => false );
		} finally {
			foreach ( $files as $file ) { if ( is_file( $file ) ) { wp_delete_file( $file ); } }
		}
	}

	public static function download() {
		$token = isset( $_GET['file'] ) && is_string( $_GET['file'] ) ? sanitize_text_field( wp_unslash( $_GET['file'] ) ) : '';
		$nonce = isset( $_GET['_wpnonce'] ) && is_string( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! current_user_can( 'manage_woocommerce' ) || ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/D', $token ) || ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'wmbr_pdf_' . $token ) ) { wp_die( 'Acesso não autorizado.', '', array( 'response' => 403 ) ); }
		$record = get_transient( 'wmbr_pdf_' . $token );
		$directory = WooCommerceNFe_Security::private_directory();
		if ( ! is_array( $record ) || (int) $record['user_id'] !== get_current_user_id() || ! $directory || ! is_file( $record['path'] ) || is_link( $record['path'] ) || realpath( dirname( $record['path'] ) ) !== realpath( $directory ) ) { wp_die( 'Arquivo indisponível ou expirado.', '', array( 'response' => 404 ) ); }
		foreach ( $record['orders'] as $id ) { if ( ! current_user_can( 'edit_shop_order', $id ) ) { wp_die( 'Acesso não autorizado.', '', array( 'response' => 403 ) ); } }
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="notas-fiscais.pdf"' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Length: ' . filesize( $record['path'] ) );
		// Stream PDF bytes without HTML escaping or loading a batch into memory; path, user, orders and nonce are checked above.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- WP_Filesystem has no binary streaming method.
		readfile( $record['path'] );
		exit;
	}

	public static function cleanup( $filename ) {
		$directory = WooCommerceNFe_Security::private_directory();
		if ( $directory && is_string( $filename ) && preg_match( '/^print-[a-zA-Z0-9]+$/D', $filename ) && is_file( $directory . '/' . $filename ) && ! is_link( $directory . '/' . $filename ) ) { wp_delete_file( $directory . '/' . $filename ); }
	}

	public static function curl_get_file_contents( $url, $order_id = 0 ) {
		// Validation is at the sink, including callers reading XML or legacy metadata.
		if ( ! WooCommerceNFe_HTTP::valid_url( $url, 'document' ) ) { return false; }
		$config = new WooCommerceNFeIssue;
		$config->get_credentials( $order_id );
		$content = WooCommerceNFe_HTTP::request( 'GET', $url, array(), $config->settings, 'document' );
		return is_wp_error( $content ) ? false : $content;
	}
}

add_action( 'wmbr_remove_private_pdf', array( 'WooCommerceNFePrint', 'cleanup' ) );
