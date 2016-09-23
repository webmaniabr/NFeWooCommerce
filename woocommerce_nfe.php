<?php
/**
* Plugin Name: Nota Fiscal Eletrônica WooCommerce
* Plugin URI: webmaniabr.com
* Description: Módulo de emissão de Nota Fiscal Eletrônica para WooCommerce através da REST API da WebmaniaBR®.
* Author: WebmaniaBR
* Author URI: https://webmaniabr.com
* Version: 2.0.1.1
* Copyright: © 2009-2016 WebmaniaBR.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WooCommerceNFe {

	public $domain = 'WooCommerceNFe';
	protected static $_instance = NULL;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	function init(){

		global $domain;

		add_action( 'admin_notices', array($this, 'display_messages') );

		// Verify WooCommerce Plugin
		if ( !class_exists( 'WooCommerce' ) ) {

			WC_NFe()->add_error( __('<strong>WooCommerce NF-e:</strong> Para a emissão de Nota Fiscal Eletrônica é necessário ativar o plugin WooCommerce.', $domain) );
			return false;

		}

		// Verify if curl command exist
		if (!function_exists('curl_version')){

			WC_NFe()->add_error( __('<strong>WooCommerce NF-e:</strong> Necessário instalar o comando cURL no servidor, entre em contato com a sua hospedagem ou administrador do servidor.', $domain) );
			return false;

		}

		$woocommerce = new WooCommerce;
		$vars = get_object_vars($woocommerce);

		// Verify WooCommerce Version
		if ($vars['version'] < '2.0.0'){

			WC_NFe()->add_error( __('<strong>WooCommerce NF-e:</strong> Para o funcionamento correto do plugin atualize o WooCommerce na versão mais recente.', $domain) );
			return false;

		}

		// Init Back-end and Fron-end
		$this->includes();
		$this->init_backend();
		$this->init_frontend();
		$WooCommerceNFe_Api = new WooCommerceNFe_Api;
		$WooCommerceNFe_Api->init();

		// Set Global Vars
		$oauth_access_token = get_option('wc_settings_woocommercenfe_access_token');
		$oauth_access_token_secret = get_option('wc_settings_woocommercenfe_access_token_secret');
		$consumer_key = get_option('wc_settings_woocommercenfe_consumer_key');
		$consumer_secret = get_option('wc_settings_woocommercenfe_consumer_secret');

		if (!$oauth_access_token ||
		!$oauth_access_token_secret ||
		!$consumer_key ||
		!$consumer_secret
		) {

			WC_NFe()->add_error( __('<strong>WooCommerce NF-e:</strong> Informe as credenciais de acesso da aplicação em WooCommerce > Configurações > Nota Fiscal.', $domain) );
			return false;

		}

		// Set Settings
		$this->oauth_access_token = $oauth_access_token;
		$this->oauth_access_token_secret = $oauth_access_token_secret;
		$this->consumer_key = $consumer_key;
		$this->consumer_secret = $consumer_secret;
		$this->ambiente = get_option('wc_settings_woocommercenfe_ambiente');
		$this->settings = array(
			'oauth_access_token' => $this->oauth_access_token,
			'oauth_access_token_secret' => $this->oauth_access_token_secret,
			'consumer_key' => $this->consumer_key,
			'consumer_secret' => $this->consumer_secret,
		);

		// Init Plugin
		$this->init_hooks();

		do_action('woocommercenfe_loaded');

	}

	function init_backend(){

		add_filter( 'woocommercenfe_plugins_url', array($this, 'default_plugin_url') );
		add_action( 'woocommerce_payment_complete', array($this, 'emitirNFeAutomaticamente') );
		add_action( 'add_meta_boxes', array('WooCommerceNFe_Backend', 'register_metabox_listar_nfe') );
		add_action( 'add_meta_boxes', array('WooCommerceNFe_Backend', 'register_metabox_nfe_emitida') );
		add_action( 'init', array('WooCommerceNFe_Backend', 'atualizar_status_nota'), 100 );
		add_action( 'save_post', array('WooCommerceNFe_Backend', 'save_informacoes_fiscais'), 10, 2);
		add_action( 'admin_head', array('WooCommerceNFe_Backend', 'style') );
		add_filter( 'manage_edit-shop_order_columns', array( 'WooCommerceNFe_Backend', 'add_order_status_column_header' ), 20 );
		add_action( 'manage_shop_order_posts_custom_column', array( 'WooCommerceNFe_Backend', 'add_order_status_column_content' ) );
		add_action( 'woocommerce_order_actions', array( 'WooCommerceNFe_Backend', 'add_order_meta_box_actions' ) );
		add_action( 'woocommerce_order_action_wc_nfe_emitir', array( 'WooCommerceNFe_Backend', 'process_order_meta_box_actions' ) );
		add_action( 'admin_footer-edit.php', array( 'WooCommerceNFe_Backend', 'add_order_bulk_actions' ) );
		add_action( 'load-edit.php', array( 'WooCommerceNFe_Backend', 'process_order_bulk_actions' ) );
		add_filter( 'woocommerce_settings_tabs_array', array('WooCommerceNFe_Backend', 'add_settings_tab'), 100 );
		add_action( 'woocommerce_settings_tabs_woocommercenfe_tab', array('WooCommerceNFe_Backend', 'settings_tab'));
		add_action( 'woocommerce_update_options_woocommercenfe_tab', array('WooCommerceNFe_Backend', 'update_settings' ));

		if (get_option('wc_settings_woocommercenfe_tipo_pessoa') == 'yes'){

			/*
			Based of the plugin: WooCommerce Extra Checkout Fields for Brazil
			@author Claudio Sanches
			@link https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil
			*/

			add_action( 'admin_enqueue_scripts', array('WooCommerceNFe_Backend', 'scripts') );
			add_filter( 'woocommerce_customer_meta_fields', array( 'WooCommerceNFe_Backend', 'customer_meta_fields' ) );
			add_filter( 'woocommerce_user_column_billing_address', array( 'WooCommerceNFe_Backend', 'user_column_billing_address' ), 1, 2 );
			add_filter( 'woocommerce_user_column_shipping_address', array( 'WooCommerceNFe_Backend', 'user_column_shipping_address' ), 1, 2 );
			add_filter( 'woocommerce_admin_billing_fields', array( 'WooCommerceNFe_Backend', 'shop_order_billing_fields' ) );
			add_filter( 'woocommerce_admin_shipping_fields', array( 'WooCommerceNFe_Backend', 'shop_order_shipping_fields' ) );
			add_filter( 'woocommerce_found_customer_details', array( 'WooCommerceNFe_Backend', 'customer_details_ajax' ) );
			add_action( 'woocommerce_process_shop_order_meta', array( 'WooCommerceNFe_Backend', 'save_custom_shop_data' ) );

		}

	}

	function init_frontend(){

		add_action( 'wp_enqueue_scripts', array('WooCommerceNFe_Frontend', 'scripts') );

		if (get_option('wc_settings_woocommercenfe_tipo_pessoa') == 'yes'){

			/*
			Based of the plugin: WooCommerce Extra Checkout Fields for Brazil
			@author Claudio Sanches
			@link https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil
			*/

			add_filter( 'woocommerce_billing_fields', array('WooCommerceNFe_Frontend', 'billing_fields') );
			add_filter( 'woocommerce_shipping_fields', array('WooCommerceNFe_Frontend', 'shipping_fields') );
			add_action( 'woocommerce_checkout_process', array('WooCommerceNFe_Frontend', 'valide_checkout_fields') );
			add_filter( 'woocommerce_localisation_address_formats', array( 'WooCommerceNFe_Frontend', 'localisation_address_formats' ) );
			add_filter( 'woocommerce_formatted_address_replacements', array( 'WooCommerceNFe_Frontend', 'formatted_address_replacements' ), 1, 2 );
			add_filter( 'woocommerce_order_formatted_billing_address', array( 'WooCommerceNFe_Frontend', 'order_formatted_billing_address' ), 1, 2 );
			add_filter( 'woocommerce_order_formatted_shipping_address', array( 'WooCommerceNFe_Frontend', 'order_formatted_shipping_address' ), 1, 2 );
			add_filter( 'woocommerce_my_account_my_address_formatted_address', array( 'WooCommerceNFe_Frontend', 'my_account_my_address_formatted_address' ), 1, 3 );

		}

	}

	function includes(){

		include_once( 'sdk/NFe.php' );
		include_once( 'inc/custom_backend.php' );
		include_once( 'inc/custom_frontend.php' );
		include_once( 'inc/format.php' );
		include_once( 'inc/api.php' );

	}

	function init_hooks(){

		// WooCommerceNFe
		add_action( 'admin_notices', array($this, 'statusSefaz') );
		add_action( 'admin_notices', array($this, 'validadeCertificado') );

	}

	function display_messages(){

		if (get_option('woocommercenfe_error_messages')){

			?>
			<div class="error">
				<?php foreach (get_option('woocommercenfe_error_messages') as $message) { echo '<p>'.$message.'</p>'; } ?>
			</div>
			<?php

			delete_option('woocommercenfe_error_messages');

		}

		if (get_option('woocommercenfe_success_messages')){

			?>
			<div class="updated notice notice-success">
				<?php foreach (get_option('woocommercenfe_success_messages') as $message) { echo '<p>'.$message.'</p>'; } ?>
			</div>
			<?php

			delete_option('woocommercenfe_success_messages');

		}

	}

	function add_error( $message ){

		$messages = get_option('woocommercenfe_error_messages');
		if (!$messages) $messages = array();
		if ($messages && count($messages) > 0) { foreach ($messages as $msg){ if ($msg == $message) return false; } }
		$messages[] = $message;
		update_option('woocommercenfe_error_messages', $messages);

	}

	function add_success( $message ){

		$messages = get_option('woocommercenfe_success_messages');
		if (!$messages) $messages = array();
		if ($messages && count($messages) > 0) { foreach ($messages as $msg){ if ($msg == $message) return false; } }
		$messages[] = $message;
		update_option('woocommercenfe_success_messages', $messages);

	}

	function statusSefaz(){

		if (get_transient('statusSefaz')){

			$response = get_transient('statusSefaz');

		} else {

			$webmaniabr = new NFe(WC_NFe()->settings);
			$response = $webmaniabr->statusSefaz();
			set_transient( 'statusSefaz', $response, 1 * HOUR_IN_SECONDS );

		}

		if (isset($response->error)){

			WC_NFe()->add_error( __('Erro: '.$response->error, $domain) );
			return false;

		} else {

			if (!$response){

				WC_NFe()->add_error( __('<strong>Sefaz Offline:</strong> A emissão de NF-e encontra-se temporariamente desativada.', $domain) );
				return false;

			}

		}

	}

	function validadeCertificado(){

		if (get_transient('validadeCertificado')) {

			$response = get_transient('validadeCertificado');

		} else {

			$webmaniabr = new NFe(WC_NFe()->settings);
			$response = $webmaniabr->validadeCertificado();
			set_transient( 'validadeCertificado', $response, 24 * HOUR_IN_SECONDS );

		}

		if (isset($response->error)){

			WC_NFe()->add_error( __('Erro: '.$response->error, $domain) );
			return false;

		} else {

			if ($response < 45 && $response >= 1){

				WC_NFe()->add_error( __('<strong>WooCommerce NF-e:</strong> Emita um novo Certificado Digital A1 - vencerá em '.$response.' dias.', $domain) );
				return false;

			}

			if (!$response) {

				WC_NFe()->add_error( __('<strong>WooCommerce NF-e:</strong> Certificado Digital A1 vencido. Emita um novo para continuar operando.', $domain) );
				return false;

			}

		}

	}

	function emitirNFeAutomaticamente( $order_id ){

		$option = get_option('wc_settings_woocommercenfe_emissao_automatica');

		if ($option == 'yes'){

			self::emitirNFe( array( $post_id ) );

		}

	}

	function emitirNFe( $order_ids = array() ){

		foreach ($order_ids as $order_id) {

			$data = self::order_data( $order_id );
			$webmaniabr = new NFe(WC_NFe()->settings);
			$response = $webmaniabr->emissaoNotaFiscal( $data );

			if (isset($response->error) || $response->status == 'reprovado'){

				$mensagem = 'Erro ao emitir a NF-e do Pedido #'.$order_id.':';

				$mensagem .= '<ul style="padding-left:20px;">';
				$mensagem .= '<li>'.$response->error.'</li>';

				if (isset($response->log)){

					if ($response->log->xMotivo){

						$mensagem .= '<li>'.$response->log->xMotivo.'</li>';

					} else {

						foreach ($response->log as $erros){
							foreach ($erros as $erro) {
								$mensagem .= '<li>'.$erro.'</li>';
							}
						}

					}

				}

				$mensagem .= '</ul>';

				WC_NFe()->add_error( $mensagem );

			} else {

				$nfe = get_post_meta( $order_id, 'nfe', true );
				if (!$nfe) $nfe = array();

				$nfe[] = array(
					'status' => (string) $response->status,
					'chave_acesso' => $response->chave,
					'n_recibo' => (int) $response->recibo,
					'n_nfe' => (int) $response->nfe,
					'n_serie' => (int) $response->serie,
					'url_xml' => (string) $response->xml,
					'url_danfe' => (string) $response->danfe,
					'data' => date_i18n('d/m/Y'),
				);

				update_post_meta( $order_id, 'nfe', $nfe );

				WC_NFe()->add_success( 'NF-e emitida com sucesso do Pedido #'.$order_id );

			}

		}

	}

	function order_data( $post_id ){

		global $wpdb;

		$WooCommerceNFe_Format = new WooCommerceNFe_Format;
		$order = new WC_Order( $post_id );
		$coupons = $order->get_used_coupons();
		$coupons_percentage = array();
        $total_discount = 0;
        $data = array();

        if ($coupons){
            
            foreach($coupons as $coupon_code){
                $coupon_obj = new WC_Coupon($coupon_code);
                if($coupon_obj->discount_type == 'percent'){
                    $coupons_percentage[] = $coupon_obj->coupon_amount;
                }
            }
            
        }
        
		if ($order->get_fees()){
            
            foreach ($order->get_fees() as $key => $item){
            
                if ($item['line_total'] < 0){
                    
                    $discount = $item['line_total']*-1;
                    $total_discount = $discount + $total_discount;
                    
                } else {
                    
                    $codigo_ean = get_option('wc_settings_woocommercenfe_ean');
                    $codigo_ncm = get_option('wc_settings_woocommercenfe_ncm');
                    $codigo_cest = get_option('wc_settings_woocommercenfe_cest');
                    $origem = get_option('wc_settings_woocommercenfe_origem');
                    $imposto = get_option('wc_settings_woocommercenfe_imposto');
                    
                    $data['produtos'][] = array(
                        'nome' => $item['name'], // Nome do produto
                        'sku' => '', // Código identificador - SKU
                        'ean' => $codigo_ean, // Código EAN
                        'ncm' => $codigo_ncm, // Código NCM
                        'cest' => $codigo_cest, // Código CEST
                        'quantidade' => 1, // Quantidade de itens
                        'unidade' => 'UN', // Unidade de medida da quantidade de itens
                        'peso' => '0.100', // Peso em KG. Ex: 800 gramas = 0.800 KG
                        'origem' => (int) $origem, // Origem do produto
                        'subtotal' => number_format($item['line_subtotal'], 2), // Preço unitário do produto - sem descontos
                        'total' => number_format($item['line_total'], 2), // Preço total (quantidade x preço unitário) - sem descontos
                        'classe_imposto' => $imposto // Referência do imposto cadastrado
                    );
                    
                }
            
            }
            
        }
        
        $total_discount = $order->get_total_discount() + $total_discount;
        
		// Order
		$data = array(
			'ID' => $post_id, // Número do pedido
			'operacao' => 1, // Tipo de Operação da Nota Fiscal
			'natureza_operacao' => get_option('wc_settings_woocommercenfe_natureza_operacao'), // Natureza da Operação
			'modelo' => 1, // Modelo da Nota Fiscal (NF-e ou NFC-e)
			'emissao' => 1, // Tipo de Emissão da NF-e
			'finalidade' => 1, // Finalidade de emissão da Nota Fiscal
			'ambiente' => (int) get_option('wc_settings_woocommercenfe_ambiente') // Identificação do Ambiente do Sefaz
		);

		$data['pedido'] = array(
			'pagamento' => 0, // Indicador da forma de pagamento
			'presenca' => 2, // Indicador de presença do comprador no estabelecimento comercial no momento da operação
			'modalidade_frete' => 0, // Modalidade do frete
			'frete' => get_post_meta( $order->id, '_order_shipping', true ), // Total do frete
			'desconto' => $total_discount, // Total do desconto
			'total' => $order->order_total // Total do pedido - sem descontos
		);

		//Informações COmplementares ao Fisco
		$fiscoinf = get_option('wc_settings_woocommercenfe_fisco_inf');

		if(!empty($fiscoinf) && strlen($fiscoinf) <= 2000){
			$data['pedido']['informacoes_fisco'] = $fiscoinf;
		}

		//Informações Complementares ao Consumidor
		$consumidorinf = get_option('wc_settings_woocommercenfe_cons_inf');

		if(!empty($consumidorinf) && strlen($consumidorinf) <= 2000){
			$data['pedido']['informacoes_complementares'] = $consumidorinf;
		}

		// Customer
		$tipo_pessoa = get_post_meta($post_id, '_billing_persontype', true);
        if (!$tipo_pessoa) $tipo_pessoa = 1;

		if ($tipo_pessoa == 1){

			$data['cliente'] = array(
				'cpf' => $WooCommerceNFe_Format->cpf(get_post_meta($post_id, '_billing_cpf', true)), // (pessoa fisica) Número do CPF
				'nome_completo' => get_post_meta($post_id, '_billing_first_name', true).' '.get_post_meta($post_id, '_billing_last_name', true), // (pessoa fisica) Nome completo
				'endereco' => get_post_meta($post_id, '_shipping_address_1', true), // Endereço de entrega dos produtos
				'complemento' => get_post_meta($post_id, '_shipping_address_2', true), // Complemento do endereço de entrega
				'numero' => get_post_meta($post_id, '_shipping_number', true), // Número do endereço de entrega
				'bairro' => get_post_meta($post_id, '_shipping_neighborhood', true), // Bairro do endereço de entrega
				'cidade' => get_post_meta($post_id, '_shipping_city', true), // Cidade do endereço de entrega
				'uf' => get_post_meta($post_id, '_shipping_state', true), // Estado do endereço de entrega
				'cep' => $WooCommerceNFe_Format->cep(get_post_meta($post_id, '_shipping_postcode', true)), // CEP do endereço de entrega
				'telefone' => get_user_meta($post_id, 'billing_phone', true), // Telefone do cliente
				'email' => get_post_meta($post_id, '_billing_email', true) // E-mail do cliente para envio da NF-e
			);

		}

		if ($tipo_pessoa == 2){

			$data['cliente'] = array(
				'cnpj' => $WooCommerceNFe_Format->cnpj(get_post_meta($post_id, '_billing_cnpj', true)), // (pessoa jurídica) Número do CNPJ
				'razao_social' => get_post_meta($post_id, '_billing_company', true), // (pessoa jurídica) Razão Social
				'ie' => get_post_meta($post_id, '_billing_ie', true), // (pessoa jurídica) Número da Inscrição Estadual
				'endereco' => get_post_meta($post_id, '_shipping_address_1', true), // Endereço de entrega dos produtos
				'complemento' => get_post_meta($post_id, '_shipping_address_2', true), // Complemento do endereço de entrega
				'numero' => get_post_meta($post_id, '_shipping_number', true), // Número do endereço de entrega
				'bairro' => get_post_meta($post_id, '_shipping_neighborhood', true), // Bairro do endereço de entrega
				'cidade' => get_post_meta($post_id, '_shipping_city', true), // Cidade do endereço de entrega
				'uf' => get_post_meta($post_id, '_shipping_state', true), // Estado do endereço de entrega
				'cep' => $WooCommerceNFe_Format->cep(get_post_meta($post_id, '_shipping_postcode', true)), // CEP do endereço de entrega
				'telefone' => get_user_meta($post_id, 'billing_phone', true), // Telefone do cliente
				'email' => get_post_meta($post_id, '_billing_email', true) // E-mail do cliente para envio da NF-e
			);

		}

		// Products
		foreach ($order->get_items() as $key => $item){

			$product_id = $item['product_id'];
			$variation_id = $item['variation_id'];

			$ignorar_nfe = get_post_meta($product_id, '_nfe_ignorar_nfe', true);

			if($ignorar_nfe == 1 || $order->get_item_subtotal( $item, false, false ) == 0){

				$data['pedido']['total'] -= $item['line_subtotal'];
                
                if ($coupons_percentage){
                    
                    foreach($coupons_percentage as $percentage){
                        $data['pedido']['total'] += ($percentage/100)*$item['line_subtotal'];
                        $data['pedido']['desconto'] -= ($percentage/100)*$item['line_subtotal'];
                    }
                    
                }

				$data['pedido']['total'] = number_format($data['pedido']['total'], 2);
				$data['pedido']['desconto'] = number_format($data['pedido']['desconto'], 2);
				continue;
			}

			$emitir = apply_filters( 'emitir_nfe_produto', true, $product_id );
			if ($variation_id) $emitir = apply_filters( 'emitir_nfe_produto', true, $variation_id );

			if ($emitir){

				$product = $order->get_product_from_item( $item );

				// Vars
				$codigo_ean = get_post_meta($product_id, '_nfe_codigo_ean', true);
				$codigo_ncm = get_post_meta($product_id, '_nfe_codigo_ncm', true);
				$codigo_cest = get_post_meta($product_id, '_nfe_codigo_cest', true);
				$origem = get_post_meta($product_id, '_nfe_origem', true);
				$imposto = get_post_meta($product_id, '_nfe_classe_imposto', true);
				$peso = $product->get_weight();
				if (!$peso) $peso = '0.100';

				if (!$codigo_ean) $codigo_ean = get_option('wc_settings_woocommercenfe_ean');
				if (!$codigo_ncm) $codigo_ncm = get_option('wc_settings_woocommercenfe_ncm');
				if (!$codigo_cest) $codigo_cest = get_option('wc_settings_woocommercenfe_cest');
				if (!is_numeric($origem)) $origem = get_option('wc_settings_woocommercenfe_origem');
				if (!$imposto) $imposto = get_option('wc_settings_woocommercenfe_imposto');

				// Attributes
				$variacoes = '';
				foreach (array_keys($item['item_meta']) as $meta){

					if (strpos($meta,'pa_') !== false) {

						$atributo = $item[$meta];
						$nome_atributo = str_replace( 'pa_', '', $meta );
						$nome_atributo = $wpdb->get_var( "SELECT attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = '$nome_atributo'" );
						$valor = strtoupper($item[$meta]);
						$variacoes .= ' - '.strtoupper($nome_atributo).': '.$valor;

					}

				}

				$data['produtos'][] = array(
					'nome' => $item['name'].$variacoes, // Nome do produto
					'sku' => $product->get_sku(), // Código identificador - SKU
					'ean' => $codigo_ean, // Código EAN
					'ncm' => $codigo_ncm, // Código NCM
					'cest' => $codigo_cest, // Código CEST
					'quantidade' => $item['qty'], // Quantidade de itens
					'unidade' => 'UN', // Unidade de medida da quantidade de itens
					'peso' => $peso, // Peso em KG. Ex: 800 gramas = 0.800 KG
					'origem' => (int) $origem, // Origem do produto
					'subtotal' => number_format($order->get_item_subtotal( $item, false, false ), 2), // Preço unitário do produto - sem descontos
					'total' => number_format($order->get_line_total( $item, false, false ), 2), // Preço total (quantidade x preço unitário) - sem descontos
					'classe_imposto' => $imposto // Referência do imposto cadastrado
				);

			}

		}

		return $data;

	}

	function default_plugin_url( $url ){

		return str_replace('inc/', '', $url);

	}

}

/**
* Active plugin
*/
add_action( 'init', array( WC_NFe(), 'init' ), 20);

function WC_NFe(){

	return WooCommerceNFe::instance();

}
