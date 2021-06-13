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
			$order_nfe_data = get_post_meta($order_id, 'nfe', true);

			if(!$order_nfe_data){
				continue;
			} 

			//Use the last nfe issued
			$nf = end($order_nfe_data);

			//Set Danfe's url 
			$item = array('chave' => $nf['chave_acesso']);
			if ($type == 'normal') {
				$item['url'] = $nf['url_danfe'];
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

			$this->add_success( 'Arquivo de impressão gerado com sucesso.' );

			$temp_dir = str_replace(ABSPATH, '', $this->files_folder);
			$link_pdf = get_site_url().'/'.$temp_dir.'/'.$result["file"].".pdf";
			//$redirect = get_site_url().'/wp-admin/edit.php?post_type=shop_order';

			//echo "<script> window.open('{$link_pdf}', '_blank') </script>";
			//echo "<script> window.location.href = '{$redirect}'; </script>";
			//wp_redirect($link_pdf);
			//die();
			
			return $link_pdf;

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

			file_put_contents("{$this->files_folder}/{$item['chave']}.pdf", file_get_contents($item['url']));
			$pdf->addPDF("{$this->files_folder}/{$item['chave']}.pdf", 'all');

		}

		$filename = time()."-".random_int(1, 10000000000);

		$result = $pdf->merge('file', "{$this->files_folder}/{$filename}.pdf");

		return array("result" => $result, "file" => $filename);

	}

}
