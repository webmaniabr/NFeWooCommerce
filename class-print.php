<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WooCommerceNFePrint extends WooCommerceNFe {

	function __construct(){}

	/**
	 * Validate Plugin before Load
	 *
	 * @return boolean
	 */
	function print( $order_ids = array(), $is_massa = false ){

		$data = [];

		foreach ($order_ids as $order_id) {

			$order_nfe_data = get_post_meta($order_id, 'nfe', true);

			if(!$order_nfe_data){
				continue;
			} else {
				array_push($data, $order_nfe_data[0]["url_danfe"]);
			}

		}

		$result = ($data) ? $this->createPDF($data) : false;

		if ($result["result"] == true){
			$this->add_success( 'Arquivo de impressão gerado com sucesso.' );
			$link = get_site_url()."/wp-content/plugins/nota-fiscal-eletronica-woocommerce/uploads/".$result["file"].".pdf";
			wp_redirect($link);
			exit();
		} else {
			$this->add_error( "Erro ao gerar arquivo de impressão." );
		}

		return $result;

	}

	function createPDF($data){

		$pdf = new PDFMerger();

		foreach ($data as $value) {
			$name = explode("/", $value);
			file_put_contents( WP_PLUGIN_DIR . '/nota-fiscal-eletronica-woocommerce/uploads/'.$name[4].".pdf", file_get_contents($value));
			$pdf->addPDF( WP_PLUGIN_DIR . '/nota-fiscal-eletronica-woocommerce/uploads/'.$name[4].".pdf", 'all');
		}

		$filename = time()."-".random_int(1, 10000000000);

		$result = $pdf->merge('file', WP_PLUGIN_DIR . '/nota-fiscal-eletronica-woocommerce/uploads/'.$filename.'.pdf');

		return array("result" => $result, "file" => $filename);

	}

}