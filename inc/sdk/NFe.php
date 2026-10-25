<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once dirname( __DIR__ ) . '/class-client-ip.php';

class NFe {

	private string $consumerKey = '';
	private string $consumerSecret = '';
	private string $accessToken = '';
	private string $accessTokenSecret = '';

	public function __construct( array $vars ) {

			$this->consumerKey = isset($vars['consumer_key']) && is_scalar($vars['consumer_key']) ? (string) $vars['consumer_key'] : '';
			$this->consumerSecret = isset($vars['consumer_secret']) && is_scalar($vars['consumer_secret']) ? (string) $vars['consumer_secret'] : '';
			$this->accessToken = isset($vars['oauth_access_token']) && is_scalar($vars['oauth_access_token']) ? (string) $vars['oauth_access_token'] : '';
			$this->accessTokenSecret = isset($vars['oauth_access_token_secret']) && is_scalar($vars['oauth_access_token_secret']) ? (string) $vars['oauth_access_token_secret'] : '';

	}

	function statusSefaz( $data = null ){

		$data = array();
		$response = self::connectWebmaniaBR( 'GET', 'https://webmania.com.br/api/1/nfe/sefaz/', $data );
		if (isset($response->error)) return $response;
		if (isset($response->status) && $response->status == 'online') return true;
		else return false;

	}

	function validadeCertificado( $data = null ){

			$data = array();
			$response = self::connectWebmaniaBR( 'GET', 'https://webmania.com.br/api/1/nfe/certificado/', $data );
			if (isset($response->error)) return $response;
			return isset($response->expiration) && is_scalar($response->expiration) ? $response->expiration : '';

	}

	function emissaoNotaFiscal( array $data ){

			$response = self::connectWebmaniaBR( 'POST', 'https://webmania.com.br/api/1/nfe/emissao/', $data );
			return $response;

	}

	function consultaNotaFiscal( $chave ){

			$data = array();
			$data[ preg_match( '/^[a-f0-9-]{36}$/iD', (string) $chave ) ? 'uuid' : 'chave' ] = $chave;
			$response = self::connectWebmaniaBR( 'GET', 'https://webmania.com.br/api/1/nfe/consulta/', $data );
			return $response;

	}

	function cancelarNotaFiscal( $chave, $motivo ){

			$data = array();
			$data['chave'] = $chave;
			$data['motivo'] = $motivo;
			$response = self::connectWebmaniaBR( 'PUT', 'https://webmania.com.br/api/1/nfe/cancelar/', $data );
			return $response;

	}

	function inutilizarNumeracao( $sequencia, $motivo, $ambiente ){

			$data = array();
			$data['sequencia'] = $sequencia;
			$data['motivo'] = $motivo;
			$data['ambiente'] = $ambiente;
			$response = self::connectWebmaniaBR( 'PUT', 'https://webmania.com.br/api/1/nfe/inutilizar/', $data );
			return $response;

	}

	function cartaCorrecao( $chave, $correcao ){

			$data = array();
			$data['chave'] = $chave;
			$data['correcao'] = $correcao;
			$response = self::connectWebmaniaBR( 'POST', 'https://webmania.com.br/api/1/nfe/cartacorrecao/', $data );
			return $response;

	}

	function devolucaoNotaFiscal( $chave, $natureza_operacao, $ambiente, $codigo_cfop = null, $classe_imposto = null, $produtos = null ){

			$data = array();
			$data['chave'] = $chave;
			$data['natureza_operacao'] = $natureza_operacao;
			$data['ambiente'] = $ambiente;
			$data['codigo_cfop'] = $codigo_cfop;
			$data['classe_imposto'] = $classe_imposto;
			$data['produtos'] = $produtos;
			$response = self::connectWebmaniaBR( 'POST', 'https://webmania.com.br/api/1/nfe/devolucao/', $data );
			return $response;

	}

	function ajusteNotaFiscal( $data ){

			$response = self::connectWebmaniaBR( 'POST', 'https://webmania.com.br/api/1/nfe/ajuste/', $data );
			return $response;

	}

	function complementarNotaFiscal( $data ) {

			$response = self::connectWebmaniaBR( 'POST', 'https://webmania.com.br/api/1/nfe/complementar/', $data);
			return $response;

	}

	function connectWebmaniaBR( $request, $endpoint, $data ) {
		return WooCommerceNFe_HTTP::json_request( $request, $endpoint, $data, array( 'consumer_key' => $this->consumerKey, 'consumer_secret' => $this->consumerSecret, 'oauth_access_token' => $this->accessToken, 'oauth_access_token_secret' => $this->accessTokenSecret ), 'nfe' );
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
