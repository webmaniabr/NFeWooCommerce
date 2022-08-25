<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class NFSe {

	function __construct( string $token ){

		$this->token = $token;

	}

	function emissaoNFSe( array $data ){

			$response = self::connectWebmaniaBR( 'POST', 'https://api.webmaniabr.com/2/nfse/emissao', $data );
			return $response;

	}

	function consultaNotaFiscal( $uuid ){

			$response = self::connectWebmaniaBR( 'GET', 'https://api.webmaniabr.com/2/nfse/consulta/'.$uuid, $data );
			return $response;

	}

	function cancelarNotaFiscal( $uuid, $motivo ){

			$data = array();
			$data['uuid'] = $uuid;
			$data['motivo'] = $motivo;
			$response = self::connectWebmaniaBR( 'PUT', 'https://api.webmaniabr.com/2/nfse/cancelar', $data );
			return $response;

	}

	function connectWebmaniaBR( $request, $endpoint, $data ){

			// Verify cURL
			if (!function_exists('curl_version')){
				$curl_error = new StdClass;
				$curl_error->error = 'cURL não localizado! Não é possível obter conexão na API da WebmaniaBR®. Verifique junto ao programador e a sua hospedagem. (PHP: '.phpversion().')';
				return $curl_error;
			}

			// Set limits
			@set_time_limit( 300 );
			ini_set('max_execution_time', 300);
			ini_set('max_input_time', 300);
			ini_set('memory_limit', '256M');
			if (
					strpos($endpoint, '/sefaz/') !== false ||
					strpos($endpoint, '/certificado/') !== false
			){
					$timeout = 15;
			} else {
					$timeout = 300;
			}

			// Header
			$headers = array(
				'Cache-Control: no-cache',
				'Content-Type:application/json',
                'Accept: application/json',
				'Authorization: Bearer '.$this->token
			);

			// Init connection
			$rest = curl_init();
			curl_setopt($rest, CURLOPT_CONNECTTIMEOUT , 10);
			curl_setopt($rest, CURLOPT_TIMEOUT, $timeout);
			curl_setopt($rest, CURLOPT_URL, $endpoint);
			curl_setopt($rest, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($rest, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($rest, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($rest, CURLOPT_CUSTOMREQUEST, $request);
			curl_setopt($rest, CURLOPT_POSTFIELDS, json_encode( $data ));
			curl_setopt($rest, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($rest, CURLOPT_FRESH_CONNECT, true);
			curl_setopt($rest, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($rest, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			curl_setopt($rest, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_NONE);
			curl_setopt($rest, CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT);

			// Connect to API
			$response = curl_exec($rest);
			$http_status = curl_getinfo($rest, CURLINFO_HTTP_CODE);
			$curl_errno = (int) curl_errno($rest);
			if ($curl_errno){
					$curl_strerror = curl_strerror($curl_errno);
			}

			curl_close($rest);

			// Get cURL errors
			$curl_error = new StdClass;
			// Get User IP
			$ip = $_SERVER['CF-Connecting-IP']; // CloudFlare
			if (!$ip){
				$ip = $_SERVER['REMOTE_ADDR']; // Standard
			}
			if (is_array($ip)){
				$ip = $ip[0];
			}
			// cURL errors
			if (!$http_status){
				$curl_error->error = 'Não foi possível obter conexão na API da WebmaniaBR®, possível relação com bloqueio no Firewall ou versão antiga do PHP. Verifique junto ao programador e a sua hospedagem a comunicação na URL: '.$endpoint.'. (cURL: '.$curl_strerror.' | PHP: '.phpversion().' | cURL: '.curl_version()['version'].')';
			} elseif ($http_status == 500) {
				$curl_error->error = 'Ocorreu um erro ao processar a sua requisição. A nossa equipe já foi notificada, em caso de dúvidas entre em contato com o suporte da WebmaniaBR®. (cURL: '.$curl_strerror.' | HTTP Code: '.$http_status.' | IP: '.$ip.')';
			} elseif (in_array($http_status, array(401, 403))) {
				$curl_error->error = 'Não foi possível se conectar na API da WebmaniaBR®. Em caso de dúvidas entre em contato com o suporte da WebmaniaBR®. (cURL: '.$curl_strerror.' | HTTP Code: '.$http_status.' | IP: '.$ip.')';
			}

			// Return
			if ( isset($curl_error->error) ) {
					return $curl_error;
			} else {
					return json_decode($response);
			}

	}

}
?>
