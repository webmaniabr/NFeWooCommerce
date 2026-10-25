<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once dirname( __DIR__ ) . '/class-client-ip.php';

class NFSe {

	private string $token = '';

	public function __construct( string $token ) {

		$this->token = $token;

	}

	function emissaoNFSe( array $data ){

			$response = self::connectWebmaniaBR( 'POST', 'https://api.webmania.com.br/2/nfse/emissao', $data );
			return $response;

	}

	function consultaNotaFiscal( $uuid ){

			$data = array();
			$response = self::connectWebmaniaBR( 'GET', 'https://api.webmania.com.br/2/nfse/consulta/'.rawurlencode( (string) $uuid ), $data );
			return $response;

	}

	function cancelarNotaFiscal( $uuid, $motivo ){

			$data = array();
			$data['uuid'] = $uuid;
			$data['motivo'] = $motivo;
			$response = self::connectWebmaniaBR( 'PUT', 'https://api.webmania.com.br/2/nfse/cancelar', $data );
			return $response;

	}

	function connectWebmaniaBR( $request, $endpoint, $data ) {
		return WooCommerceNFe_HTTP::json_request( $request, $endpoint, $data, array( 'bearer_access_token' => $this->token ), 'nfse' );
	}

	function setMemoryLimit(){
		if ( ! function_exists( 'ini_set' ) || ! function_exists( 'wp_raise_memory_limit' ) ) { return false; }
		return wp_raise_memory_limit( 'admin' );
	}

	function convertMemoryToBytes($value) {

			$multipliers = ['k' => 1024, 'm' => 1024**2, 'g' => 1024**3];
			$unit = strtolower(substr(trim($value), -1));
			return $multipliers[$unit] ?? 1 * (int)$value;

	}

	/**
	 * Security: Get real user IP address safely
	 * 
	 * @return string
	 */
	private function get_user_ip() {
		return WooCommerceNFe_Client_IP::from_request();
	}

}
?>
