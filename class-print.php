<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WooCommerceNFePrint extends WooCommerceNFe {

	//Files folder path
	private $files_folder;

	/**
	 * Constructor
	 * 
	 * @return void
	 */
	public function __construct(){

		//If Temp directory is not defined, create a folder to save the files
		if (defined('WP_TEMP_DIR')) {
			
			$this->files_folder = WP_TEMP_DIR;

		}
		else {

			$this->files_folder = dirname(__FILE__) . '/danfes_pdf';
			wp_mkdir_p($this->files_folder);

		}

		

	}

	/**
	 * Print nfe pdf
	 * 
	 * @param array $order_ids
	 * @param string $type Danfe type (normal, simplificada, etiqueta)
	 *
	 * @return boolean
	 */
	public function print( $order_ids = array(), $type = 'normal' ){

		$data = array();

		foreach ($order_ids as $order_id) {

			//Get order nfe data
			$order = wc_get_order( $order_id );
			$order_nfe_data = $order->get_meta('nfe');

			if(!$order_nfe_data){
				continue;
			} 

			//Use the last nfe issued
			$nf = end($order_nfe_data);
			if ($nf['modelo'] == 'lote_rps' || ($nf['modelo'] == 'nfse' && ($type != 'normal' || empty($nf['pdf_rps'])))) continue;

			//Set Danfe's url 
			$item = array('chave' => $nf['chave_acesso']);
			if ((isset($nf['url_danfe']) || trim($nf['url_danfe']) == '') && $nf['chave_acesso']) {
				$nf['url_danfe'] = 'https://nfe.webmaniabr.com/danfe/'.$nf['chave_acesso'].'/';
			}
			if ($type == 'normal') {
				$item['url'] = ($nf['modelo'] == 'nfse') ? $nf['pdf_rps'] : $nf['url_danfe'];
			}
			else if ($type == 'simplificada') {
				$item['url'] = ($nf['url_danfe_simplificada']) ? $nf['url_danfe_simplificada'] : str_replace('/danfe/', '/danfe/simples/', $nf['url_danfe']);
			}
			else if ($type == 'etiqueta') {
				$item['url'] = ($nf['url_danfe_etiqueta']) ? $nf['url_danfe_etiqueta'] : str_replace('/danfe/', '/danfe/etiqueta/', $nf['url_danfe']);
			}
			
			$data[] = $item;

		}		

		//Creates pdf of selected orders nfe
		$result = (!empty($data)) ? $this->createPDF($data) : false;

		if ($result["result"] == true){

			$abs_path = str_replace(['\\', '/'], '/', ABSPATH);
			$file_folder = str_replace(['\\', '/'], '/', $this->files_folder);
			$temp_dir = str_replace($abs_path, '', $file_folder);
			$link_pdf = trailingslashit(get_site_url()) . trim($temp_dir, '/') . '/' . $result["file"] . ".pdf";
			
			$this->add_success( "Arquivo de impressão gerado com sucesso. <a href='$link_pdf' target='_blank'>Clique aqui</a> para acessá-lo." );

		} else {

			$this->add_error( "Erro ao gerar arquivo de impressão." );

		}

		return $result;

	}

	/**
	 * Create pdf with DANFEs
	 * 
	 * @param array $data Danfe urls
	 *
	 * @return array Result of merging files (bool) and pdf's url
	 */
	private function createPDF($data){

		$pdf = new PDFMerger();

		foreach ($data as $item) {

			try {
				// Security: Validate chave to prevent path traversal
				$chave = preg_replace('/[^a-zA-Z0-9]/', '', $item['chave']);
				if (empty($chave) || strlen($chave) > 44) {
					continue; // Invalid NFe key format
				}

				// Security: Validate URL to prevent SSRF
				if (!$this->validate_danfe_url($item['url'])) {
					continue;
				}

				$content = $this->curl_get_file_contents($item['url']);
				if ($content && (strpos($content, '%PDF-') !== false && strpos($content, '%%EOF') !== false)) {
					$safe_filename = $this->files_folder . DIRECTORY_SEPARATOR . $chave . '.pdf';
					
					// Security: Additional path traversal check
					if (strpos(realpath(dirname($safe_filename)), realpath($this->files_folder)) !== 0) {
						continue;
					}
					
					file_put_contents($safe_filename, $content);
					$pdf->addPDF($safe_filename, 'all');
				}
			} catch (Exception $e) {
				error_log('WooCommerceNFe: Error processing PDF: ' . $e->getMessage());
				continue;
			}

		}

		$filename = time()."-".random_int(1, 10000000000);

		try {
			$result = $pdf->merge('file', "{$this->files_folder}/{$filename}.pdf");
			return array("result" => $result, "file" => $filename);
		} catch (Exception $e) {
			return array("result" => false, "file" => $filename);
		}
	}

	/**
	 * Validate DANFE URL to prevent SSRF attacks
	 * 
	 * @param string $url
	 * @return boolean
	 */
	private function validate_danfe_url($url) {
		
		// Security: Only allow WebmaniaBR domains
		$allowed_domains = [
			'nfe.webmaniabr.com',
			'webmaniabr.com'
		];
		
		$parsed_url = parse_url($url);
		if (!$parsed_url || !isset($parsed_url['host'])) {
			return false;
		}
		
		// Check if domain is allowed
		$host = strtolower($parsed_url['host']);
		foreach ($allowed_domains as $allowed_domain) {
			if ($host === $allowed_domain || substr($host, -strlen('.' . $allowed_domain)) === '.' . $allowed_domain) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Get file content from URL using curl (secure version)
	 * 
	 * @param string $url
	 * @return mixed
	 */
	public static function curl_get_file_contents($url) {

		// Security: Build headers securely
		$headers = [];
		$bearer_token = get_option('wc_settings_woocommercenfe_bearer_access_token');
		$access_token = get_option('wc_settings_woocommercenfe_access_token');
		$access_token_secret = get_option('wc_settings_woocommercenfe_access_token_secret');
		$consumer_key = get_option('wc_settings_woocommercenfe_consumer_key');
		$consumer_secret = get_option('wc_settings_woocommercenfe_consumer_secret');

		if ($bearer_token) {
			$headers[] = 'Authorization: Bearer ' . $bearer_token;
		}
		if ($access_token) {
			$headers[] = 'X-Access-Token: ' . $access_token;
		}
		if ($access_token_secret) {
			$headers[] = 'X-Access-Token-Secret: ' . $access_token_secret;
		}
		if ($consumer_key) {
			$headers[] = 'X-Consumer-Key: ' . $consumer_key;
		}
		if ($consumer_secret) {
			$headers[] = 'X-Consumer-Secret: ' . $consumer_secret;
		}

		$c = curl_init();

		// Security: Secure cURL options
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_HTTPGET, true);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($c, CURLOPT_TIMEOUT, 30); // Prevent hanging
		curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($c, CURLOPT_MAXREDIRS, 3); // Limit redirects
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($c, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS); // Only HTTPS
		curl_setopt($c, CURLOPT_SSL_VERIFYPEER, true); // Verify SSL
		curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($c, CURLOPT_USERAGENT, 'WooCommerce-NFe-Plugin/' . WC_NFe()->version);

		$contents = curl_exec($c);
		$http_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		$curl_error = curl_error($c);
		
		curl_close($c);

		// Security: Validate response
		if ($curl_error || $http_code !== 200) {
			error_log('WooCommerceNFe: cURL error - ' . $curl_error . ' HTTP: ' . $http_code);
			return false;
		}

		if (!$contents || strpos($contents, 'error') !== false) {
			return false;
		}

		// Security: Limit file size to prevent memory exhaustion
		if (strlen($contents) > 10 * 1024 * 1024) { // 10MB limit
			error_log('WooCommerceNFe: File too large');
			return false;
		}

		return $contents;
	}
}