<?php
/**
* Plugin Name: Nota Fiscal Eletrônica WooCommerce
* Plugin URI: webmaniabr.com
* Description: Módulo de emissão de Nota Fiscal Eletrônica para WooCommerce através da REST API da WebmaniaBR®.
* Author: WebmaniaBR
* Author URI: https://webmaniabr.com
* Version: 2.6.2
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
		global $woocommerce;
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
		add_action( 'woocommerce_payment_complete', array($this, 'emitirNFeAutomaticamente'), 10, 1 );
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
		add_action( 'admin_enqueue_scripts', array('WooCommerceNFe_Backend', 'global_admin_scripts') );

		add_action ('product_cat_add_form_fields', array('WooCommerceNFe_Backend', 'add_category_ncm'));
		add_action ('product_cat_edit_form_fields', array('WooCommerceNFe_Backend', 'edit_category_ncm'), 10, 2);

		add_action('edited_product_cat', array('WooCommerceNFe_Backend', 'save_product_cat_ncm'), 10, 2);
		add_action('create_product_cat', array('WooCommerceNFe_Backend', 'save_product_cat_ncm'), 10, 2);

		add_action('admin_notices', array('WooCommerceNFe_Backend', 'cat_ncm_warning'));
		add_action( 'admin_enqueue_scripts', array('WooCommerceNFe_Backend', 'scripts') );

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
			add_action( 'woocommerce_api_create_order', array( 'WooCommerceNFe_Backend', 'wc_api_save_custom_shop_data' ), 10, 2 );
			add_filter( 'woocommerce_localisation_address_formats', array( 'WooCommerceNFe_Frontend', 'localisation_address_formats' ) );
		}

		add_action('init', array('WooCommerceNFe_Backend', 'listen_notification'));

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
	function validadeCertificado(){
		if (get_transient('validadeCertificado')) {
			$response = get_transient('validadeCertificado');
		} else {
			$webmaniabr = new NFe(WC_NFe()->settings);
			$response = $webmaniabr->validadeCertificado();
		}
		if (isset($response->error)){
            set_transient( 'validadeCertificado', $response, 600 );
			WC_NFe()->add_error( __('Erro: '.$response->error, $domain) );
			return false;
		} else {
            set_transient( 'validadeCertificado', $response, 24 * HOUR_IN_SECONDS );
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
			self::emitirNFe( array( $order_id ) );
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
						if(isset($response->log->aProt[0]->xMotivo)){
							$error = $response->log->aProt[0]->xMotivo;
						}else{
							$error = $response->log->xMotivo;
						}
						$mensagem .= '<li>'.$error.'</li>';
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
		$default_ean     = get_option('wc_settings_woocommercenfe_ean');
		$default_ncm     = get_option('wc_settings_woocommercenfe_ncm');
		$default_cest    = get_option('wc_settings_woocommercenfe_cest');
		$default_origem  = get_option('wc_settings_woocommercenfe_origem');
		$default_imposto = get_option('wc_settings_woocommercenfe_imposto');
		$default_weight  = '0.100';

		$transportadoras = get_option('wc_settings_woocommercenfe_transportadoras', array());

		$envio_email = get_option('wc_settings_woocommercenfe_envio_email');

		$coupons = $order->get_used_coupons();
		$coupons_percentage = array();
		$total_discount = 0;
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
					$discount = abs($item['line_total']);
					$total_discount = $discount + $total_discount;
				} else {
					$data['produtos'][] = array(
						'nome'           => $item['name'], // Nome do produto
						'sku'            => '', // Código identificador - SKU
						'ean'            => $default_ean, // Código EAN
						'ncm'            => $default_ncm, // Código NCM
						'cest'           => $default_cest, // Código CEST
						'quantidade'     => 1, // Quantidade de itens
						'unidade'        => 'UN', // Unidade de medida da quantidade de itens
						'peso'           => $default_weight, // Peso em KG. Ex: 800 gramas = 0.800 KG
						'origem'         => (int) $default_origem, // Origem do produto
						'subtotal'       => number_format($item['line_subtotal'], 2, '.', ''), // Preço unitário do produto - sem descontos
						'total'          => number_format($item['line_total'], 2, '.', ''), // Preço total (quantidade x preço unitário) - sem descontos
						'classe_imposto' => $default_imposto // Referência do imposto cadastrado
					);
				}
			}
		}
    $total_discount = $order->get_total_discount() + $total_discount;

		// Order
		$modalidade_frete = get_post_meta($post_id, '_nfe_modalidade_frete', true);
		if (!$modalidade_frete || $modalidade_frete == 'null') $modalidade_frete = 0;


		$uniq_key = get_post_meta($post_id, 'uniq_get_key', true);

		if(!$uniq_key){
			$uniq_key = md5(uniqid(rand(), true));
			update_post_meta($post_id, 'uniq_get_key', $uniq_key);
		}

		$data = array(
			'ID'                => $post_id, // Número do pedido
			'url_notificacao' => get_bloginfo('url').'?retorno_nfe='.$uniq_key.'&order_id='.$post_id,
			'operacao'          => 1, // Tipo de Operação da Nota Fiscal
			'natureza_operacao' => get_option('wc_settings_woocommercenfe_natureza_operacao'), // Natureza da Operação
			'modelo'            => 1, // Modelo da Nota Fiscal (NF-e ou NFC-e)
			'emissao'           => 1, // Tipo de Emissão da NF-e
			'finalidade'        => 1, // Finalidade de emissão da Nota Fiscal
			'ambiente'          => (int) get_option('wc_settings_woocommercenfe_ambiente') // Identificação do Ambiente do Sefaz
		);
		$data['pedido'] = array(
			'pagamento'        => 0, // Indicador da forma de pagamento
			'presenca'         => 2, // Indicador de presença do comprador no estabelecimento comercial no momento da operação
			'modalidade_frete' => (int) $modalidade_frete, // Modalidade do frete
			'frete'            => get_post_meta( $order->id, '_order_shipping', true ), // Total do frete
			'desconto'         => $total_discount, // Total do desconto
			'total'            => $order->order_total // Total do pedido - sem descontos
		);
		//Informações Complementares ao Fisco
		$fisco_inf = get_option('wc_settings_woocommercenfe_fisco_inf');
		if(!empty($fisco_inf) && strlen($fisco_inf) <= 2000){
			$data['pedido']['informacoes_fisco'] = $fisco_inf;
		}
		//Informações Complementares ao Consumidor
		$consumidor_inf = get_option('wc_settings_woocommercenfe_cons_inf');
		if(!empty($consumidor_inf) && strlen($consumidor_inf) <= 2000){
			$data['pedido']['informacoes_complementares'] = $consumidor_inf;
		}
		// Customer
		$data['cliente'] = array(
			'endereco'    => get_post_meta($post_id, '_shipping_address_1', true), // Endereço de entrega dos produtos
			'complemento' => get_post_meta($post_id, '_shipping_address_2', true), // Complemento do endereço de entrega
			'numero'      => get_post_meta($post_id, '_shipping_number', true), // Número do endereço de entrega
			'bairro'      => get_post_meta($post_id, '_shipping_neighborhood', true), // Bairro do endereço de entrega
			'cidade'      => get_post_meta($post_id, '_shipping_city', true), // Cidade do endereço de entrega
			'uf'          => get_post_meta($post_id, '_shipping_state', true), // Estado do endereço de entrega
			'cep'         => $WooCommerceNFe_Format->cep(get_post_meta($post_id, '_shipping_postcode', true)), // CEP do endereço de entrega
			'telefone'    => get_user_meta($post_id, 'billing_phone', true), // Telefone do cliente
			'email'       => ($envio_email ? get_post_meta($post_id, '_billing_email', true) : ''), // E-mail do cliente para envio da NF-e
		);
		$tipo_pessoa = get_post_meta($post_id, '_billing_persontype', true);
    if (!$tipo_pessoa) $tipo_pessoa = 1;
		if ($tipo_pessoa == 1){
			$cpf        = get_post_meta($post_id, '_billing_cpf', true);
			$first_name = get_post_meta($post_id, '_billing_first_name', true);
			$last_name  = get_post_meta($post_id, '_billing_last_name', true);
			$full_name  = $first_name.' '.$last_name;
			$data['cliente']['cpf'] = $WooCommerceNFe_Format->cpf($cpf); //Pessoa Física: Número do CPF
			$data['cliente']['nome_completo'] = $full_name; //Nome completo do cliente
		}else if($tipo_pessoa == 2){
			$data['cliente']['cnpj'] = $WooCommerceNFe_Format->cnpj(get_post_meta($post_id, '_billing_cnpj', true)); // (pessoa jurídica) Número do CNPJ
			$data['cliente']['razao_social'] = get_post_meta($post_id, '_billing_company', true); // (pessoa jurídica) Razão Social
			$data['cliente']['ie'] =  get_post_meta($post_id, '_billing_ie', true); // (pessoa jurídica) Número da Inscrição Estadual
		}
		// Products
		$bundles = array();
		if(!isset($data['produtos'])) $data['produtos'] = array();
		foreach ($order->get_items() as $key => $item){
			$product      = $order->get_product_from_item( $item );
			$product_type = $product->get_type();
			$product_id   = $item['product_id'];
			$variation_id = $item['variation_id'];
			if( $product_type == 'bundle' || $product_type == 'yith_bundle' || isset($item['bundled_by']) ){
				$bundles[] = $item;
				continue;
			}
			$product_info = self::get_product_nfe_info($item, $order);
			$ignorar_nfe = get_post_meta($product_id, '_nfe_ignorar_nfe', true);
			if($ignorar_nfe == 1 || $order->get_item_subtotal( $item, false, false ) == 0){
				$data['pedido']['total'] -= $item['line_subtotal'];
                if ($coupons_percentage){
                    foreach($coupons_percentage as $percentage){
                        $data['pedido']['total'] += ($percentage/100)*$item['line_subtotal'];
                        $data['pedido']['desconto'] -= ($percentage/100)*$item['line_subtotal'];
                    }
                }
				$data['pedido']['total']    = number_format($data['pedido']['total'], 2, '.', '' );
				$data['pedido']['desconto'] = number_format($data['pedido']['desconto'], 2, '.', '' );
				continue;
			}
			$emitir = apply_filters( 'emitir_nfe_produto', true, $product_id );
			if ($variation_id) $emitir = apply_filters( 'emitir_nfe_produto', true, $variation_id );
			if ($emitir){
				$data['produtos'][] = $product_info;
			}
		}
		$bundle_info = self::set_bundle_products_array($bundles, $order);
		$data['produtos'] = array_merge($bundle_info['products'], $data['produtos']);
		$data['pedido']['desconto'] += $bundle_info['bundle_discount'];
		$data['pedido']['desconto'] = number_format($data['pedido']['desconto'], 2, '.', '' );

		// Transporte

		//Default transportadora info
		$shipping_method = @array_shift($order->get_shipping_methods());
		$shipping_method_id = str_replace(':1', '', $shipping_method['method_id']);

		$include_shipping_info = get_option('wc_settings_woocommercenfe_transp_include');


		if($include_shipping_info == 'on' && isset($transportadoras[$shipping_method_id])){

			$transp = $transportadoras[$shipping_method_id];
			$data['transporte']['cnpj']         = $transp['cnpj'];
			$data['transporte']['razao_social'] = $transp['razao_social'];
			$data['transporte']['ie']           = $transp['ie'];
			$data['transporte']['endereco']     = $transp['address'];
			$data['transporte']['uf']           = $transp['uf'];
			$data['transporte']['cidade']       = $transp['city'];
			$data['transporte']['cep']          = $transp['cep'];

			$order_specifics = array(
				'volume' => '_nfe_transporte_volume',
				'especie' => '_nfe_transporte_especie',
				'peso_bruto' => '_nfe_transporte_peso_bruto',
				'peso_liquido' => '_nfe_transporte_peso_liquido'
			);

			foreach($order_specifics as $api_key => $meta_key){
				$value = get_post_meta($post_id, $meta_key, true);
				if($value){
					$data['transporte'][$api_key] = $value;
				}
			}

		}

		return $data;
	}

	function get_product_nfe_info($item, $order){
		global $wpdb;
		$product_id  = $item['product_id'];
		$product     = $order->get_product_from_item( $item );
		$ignorar_nfe = get_post_meta($product_id, '_nfe_ignorar_nfe', true);
		$codigo_ean  = get_post_meta($product_id, '_nfe_codigo_ean', true);
		$codigo_ncm  = get_post_meta($product_id, '_nfe_codigo_ncm', true);
		$codigo_cest = get_post_meta($product_id, '_nfe_codigo_cest', true);
		$origem      = get_post_meta($product_id, '_nfe_origem', true);
		$imposto     = get_post_meta($product_id, '_nfe_classe_imposto', true);
		$peso        = $product->get_weight();
		if (!$peso){
			$peso = '0.100';
		}
		if (!$codigo_ean){
			$codigo_ean = get_option('wc_settings_woocommercenfe_ean');
		}

		if (!$codigo_ncm){

			$product_cat = get_the_terms($product_id, 'product_cat');

			if(is_array($product_cat)){
				foreach($product_cat as $cat){
		      $ncm = get_term_meta($cat->term_id, '_ncm', true);
		      if($ncm){
						$codigo_ncm = $ncm;
						break;
					}
		    }
			}


			if(!$codigo_ncm) $codigo_ncm   = get_option('wc_settings_woocommercenfe_ncm');

		}

		if (!$codigo_cest){
			$codigo_cest = get_option('wc_settings_woocommercenfe_cest');
		}
		if (!is_numeric($origem)){
			$origem = get_option('wc_settings_woocommercenfe_origem');
		}
		if (!$imposto){
			$imposto = get_option('wc_settings_woocommercenfe_imposto');
		}
		$variacoes = ''; //Used to append variation name to product name
		foreach (array_keys($item['item_meta']) as $meta){
			if (strpos($meta,'pa_') !== false) {
				$atributo = $item[$meta];
				$nome_atributo = str_replace( 'pa_', '', $meta );
				$nome_atributo = $wpdb->get_var( "SELECT attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = '$nome_atributo'" );
				$valor = strtoupper($item[$meta]);
				$variacoes .= ' - '.strtoupper($nome_atributo).': '.$valor;
			}
		}
			$product_active_price = $order->get_item_subtotal( $item, false, false );
			$info = array(
				'nome' => $item['name'].$variacoes, // Nome do produto
				'sku' => $product->get_sku(), // Código identificador - SKU
				'ean' => $codigo_ean, // Código EAN
				'ncm' => $codigo_ncm, // Código NCM
				'cest' => $codigo_cest, // Código CEST
				'quantidade' => $item['qty'], // Quantidade de itens
				'unidade' => 'UN', // Unidade de medida da quantidade de itens
				'peso' => $peso, // Peso em KG. Ex: 800 gramas = 0.800 KG
				'origem' => (int) $origem, // Origem do produto
				'subtotal' => number_format($product_active_price, 2, '.', '' ), // Preço unitário do produto - sem descontos
				'total' => number_format($product_active_price*$item['qty'], 2, '.', '' ), // Preço total (quantidade x preço unitário) - sem descontos
				'classe_imposto' => $imposto // Referência do imposto cadastrado
			);
			return $info;
	}
	function set_bundle_products_array( $bundles, $order ){
		$total_bundle = 0;
		$total_products = 0;
		$bundle_products = array();
		foreach($bundles as $item){
			$product = $order->get_product_from_item( $item );
			$product_type = $product->get_type();
			$product_price = $product->get_price();
			if(isset($item['bundled_by'])){
				$product_total = $product_price * $item['qty'];
				$total_products += $product_total;
				if(!isset($bundle_products[$item['product_id']])){
					$bundle_products[$item['product_id']] = self::get_product_nfe_info($item, $order);
					$bundle_products[$item['product_id']]['subtotal'] = number_format($product_price, 2, '.', '' );
					$bundle_products[$item['product_id']]['total'] = number_format($product_total, 2, '.', '' );
				}else{
					$new_qty = ((int)$bundle_products[$item['product_id']]['quantidade']) + 1;
					$new_total = $new_qty * $product_price;
					$bundle_products[$item['product_id']]['quantidade'] = $new_qty;
					$bundle_products[$item['product_id']]['total'] = number_format($new_total, 2, '.', '' );
				}
			}elseif($product_type == 'yith_bundle'){
				$total_bundle += $product_price*$item['qty'];
			}
		}
		if($total_products < $total_bundle){
			end($bundle_products);
			$end_key = key($bundle_products);
			$subtotal_end = $bundle_products[$end_key]['subtotal'];
			$qty_end = $bundle_products[$end_key]['quantidade'];
			$diff = $total_bundle - $total_products;
			$add = $diff / $qty_end;
			$subtotal_end += $add;
			$bundle_products[$end_key]['subtotal'] = round($subtotal_end, 2);
			$bundle_products[$end_key]['total'] = $subtotal_end * $qty_end;
			$discount = 0;
		}else{
			$discount = abs($total_bundle - $total_products);
		}
		return array('products' => $bundle_products, 'bundle_discount' => $discount);
	}
	function default_plugin_url( $url ){
		return str_replace('inc/', '', $url);
	}
}
/**
* Active plugin
*/
add_action( 'plugins_loaded', array( WC_NFe(), 'init' ), 20);
function WC_NFe(){
	return WooCommerceNFe::instance();
}
