<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class NFSe {

	public string $token = '';

	public function __construct( string $token ) {

		$this->token = $token;

	}

	function emissaoNFSe( array $data ){

			$response = self::connectWebmaniaBR( 'POST', 'https://api.webmaniabr.com/2/nfse/emissao', $data );
			return $response;

	}

	function consultaNotaFiscal( $uuid ){

			$data = array();
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
			$memory_limit = ini_get('memory_limit');
			$memory_limit = ($memory_limit) ? preg_replace("/[^0-9]/", '', $memory_limit) : 0;
			self::setMemoryLimit();
			
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
			curl_setopt($rest, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($rest, CURLOPT_SSL_VERIFYHOST, 2);
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
			// Security: Get User IP safely 
			$ip = $this->get_user_ip();
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

	function setMemoryLimit(){

			$currentMemoryLimit = ini_get('memory_limit');
			$currentMemoryLimitBytes = self::convertMemoryToBytes($currentMemoryLimit);
			$desiredMemoryLimitBytes = self::convertMemoryToBytes('256M');
			
			if ($currentMemoryLimitBytes < $desiredMemoryLimitBytes) {
					ini_set('memory_limit', '256M');
			}

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
		
		// List of possible IP headers in order of preference
		$ip_headers = [
			'HTTP_CF_CONNECTING_IP',     // CloudFlare
			'HTTP_CLIENT_IP',            // Proxy
			'HTTP_X_FORWARDED_FOR',      // Load Balancer/Proxy
			'HTTP_X_FORWARDED',          // Proxy
			'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
			'HTTP_FORWARDED_FOR',        // Proxy
			'HTTP_FORWARDED',            // Proxy
			'REMOTE_ADDR'                // Standard
		];
		
		foreach ($ip_headers as $header) {
			if (!empty($_SERVER[$header])) {
				$ip_list = $_SERVER[$header];
				
				// Handle comma-separated IPs
				if (strpos($ip_list, ',') !== false) {
					$ip_list = explode(',', $ip_list);
					$ip = trim($ip_list[0]);
				} else {
					$ip = trim($ip_list);
				}
				
				// Validate IP address
				if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
					return $ip;
				}
			}
		}
		
		// Fallback to REMOTE_ADDR even if private/reserved
		return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
	}

}
?>
