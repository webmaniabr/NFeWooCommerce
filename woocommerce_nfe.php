<?php
/**
* Plugin Name: Nota Fiscal Eletrônica WooCommerce - Update por Shirkit
* Plugin URI: shirkit.webmaniabr.com
* Description: Módulo de emissão de Nota Fiscal Eletrônica para WooCommerce através da REST API da WebmaniaBR®.
* Author: WebmaniaBR
* Author URI: https://github.com/shirkit
* Version: 3.0.7
* Copyright: © 2009-2019 WebmaniaBR.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class WooCommerceNFe {
	public $domain = 'WooCommerceNFe';
	public static $version = '3.0.6';
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
			WC_NFe()->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> Para a emissão de Nota Fiscal Eletrônica é necessário ativar o plugin WooCommerce.', $domain) );
			return false;
		}
		// Verify if curl command exist
		if (!function_exists('curl_version')){
			WC_NFe()->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> Necessário instalar o comando cURL no servidor, entre em contato com a sua hospedagem ou administrador do servidor.', $domain) );
			return false;
		}
		global $woocommerce;
		$vars = get_object_vars($woocommerce);
		// Verify WooCommerce Version
		if ($vars['version'] < '2.0.0'){
			WC_NFe()->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> Para o funcionamento correto do plugin atualize o WooCommerce na versão mais recente.', $domain) );
			return false;
		}
		// Init Back-end and Front-end
		$this->includes();
		$this->init_backend();
		$this->init_frontend();
		add_action( 'admin_init', array($this, 'wmbr_compatibility_issues') );
		add_action( 'wp_ajax_force_digital_certificate_update', array($this, 'force_digital_certificate_update') );
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
			WC_NFe()->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> Informe as credenciais de acesso da aplicação em WooCommerce > Configurações > Nota Fiscal.', $domain) );
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
		$WC_NFe_Backend = new WooCommerceNFe_Backend();
		add_filter( 'woocommercenfe_plugins_url', array($this, 'default_plugin_url') );
		add_action( 'add_meta_boxes', array($WC_NFe_Backend, 'register_metabox_listar_nfe') );
		add_action( 'add_meta_boxes', array($WC_NFe_Backend, 'register_metabox_nfe_emitida') );
		add_action( 'woocommerce_product_after_variable_attributes', array($WC_NFe_Backend, 'variation_field_nfe'), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array($WC_NFe_Backend, 'save_variation_data'), 10, 2 );
		add_action( 'woocommerce_product_bulk_edit_start', array($WC_NFe_Backend, 'nfe_custom_field_bulk_edit_input') );
		add_action( 'woocommerce_product_bulk_edit_save', array($WC_NFe_Backend, 'nfe_custom_field_bulk_edit_save') );
		add_action( 'init', array($WC_NFe_Backend, 'atualizar_status_nota'), 100 );
		add_action( 'woocommerce_api_nfe_callback', array($WC_NFe_Backend, 'nfe_callback') );
		add_action( 'save_post', array($WC_NFe_Backend, 'save_informacoes_fiscais'), 10, 2);
		add_action( 'admin_head', array($WC_NFe_Backend, 'style') );
		add_filter( 'manage_edit-shop_order_columns', array( $WC_NFe_Backend, 'add_order_status_column_header' ), 20 );
		add_action( 'manage_shop_order_posts_custom_column', array( $WC_NFe_Backend, 'add_order_status_column_content' ) );
		add_action( 'woocommerce_order_actions', array( $WC_NFe_Backend, 'add_order_meta_box_actions' ) );
		add_action( 'woocommerce_order_action_wc_nfe_emitir', array( $WC_NFe_Backend, 'process_order_meta_box_actions' ) );
		add_action( 'woocommerce_order_action_wc_nfce_emitir', array( $WC_NFe_Backend, 'process_order_meta_box_actions2' ) );
		add_action( 'admin_footer-edit.php', array( $WC_NFe_Backend, 'add_order_bulk_actions' ) );
		add_action( 'load-edit.php', array( $WC_NFe_Backend, 'process_order_bulk_actions' ) );
		add_filter( 'woocommerce_settings_tabs_array', array($WC_NFe_Backend, 'add_settings_tab'), 100 );
		add_action( 'woocommerce_settings_tabs_woocommercenfe_tab', array($WC_NFe_Backend, 'settings_tab'));
		add_action( 'woocommerce_update_options_woocommercenfe_tab', array($WC_NFe_Backend, 'update_settings' ));
		add_action( 'admin_enqueue_scripts', array($WC_NFe_Backend, 'global_admin_scripts') );
		add_action( 'product_cat_add_form_fields', array($WC_NFe_Backend, 'add_category_ncm'));
		add_action( 'product_cat_edit_form_fields', array($WC_NFe_Backend, 'edit_category_ncm'), 10, 2);
		add_action( 'edited_product_cat', array($WC_NFe_Backend, 'save_product_cat_ncm'), 10, 2);
		add_action( 'create_product_cat', array($WC_NFe_Backend, 'save_product_cat_ncm'), 10, 2);
		add_action( 'admin_notices', array($WC_NFe_Backend, 'cat_ncm_warning'));
		add_filter( "plugin_action_links_".plugin_basename( __FILE__ ), array($this, 'plugin_add_settings_link') );
		add_action( 'admin_menu', array($WC_NFe_Backend, 'add_admin_menu_item'));
		add_action( 'admin_init', array($WC_NFe_Backend, 'alert_auto_invoice_errors'));
		add_action( 'wp_ajax_wmbr_remove_order_id_auto_invoice', array($WC_NFe_Backend, 'wmbr_remove_order_id_auto_invoice'));
		// NFe autommatic
		$option = get_option('wc_settings_woocommercenfe_emissao_automatica');
		if ($option == 1 || $option == 'yes') {
			foreach (get_option('wc_settings_woocommercenfe_emissao_automatica_status') as $status ) {
				add_action( 'woocommerce_order_status_' . ((strpos($status, 'wc-', 0) === 0) ? substr($status, 3) : $status), array($this, 'emitirNFeAutomaticamenteOnStatusChange'), 1000, 1 );
			}
		}
		add_filter( 'woocommerce_admin_shipping_fields', array($WC_NFe_Backend, 'extra_shipping_fields') );
		add_action( 'admin_enqueue_scripts', array($WC_NFe_Backend, 'scripts') );
	}
	function init_frontend(){
		global $pagenow;

		// Compatibility with WooCommerce Admin 0.20.0 or higher
		if (
				$this->wmbr_is_plugin_active('woocommerce-admin/woocommerce-admin.php') &&
				$pagenow != 'admin.php' &&
				( isset($_GET['page']) && $_GET['page'] != 'wc-admin' )
			) {
			remove_action( 'admin_notices', array( 'Automattic\WooCommerce\Admin\Loader', 'inject_before_notices' ), -9999 );
			remove_action( 'admin_notices', array( 'Automattic\WooCommerce\Admin\Loader', 'inject_after_notices' ), PHP_INT_MAX );
		}

		/**
		 * Plugin: Brazilian Market on WooCommerce (Customized)
		 * @author Claudio Sanches
		 * @link https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil
		**/
		if (
			get_option('wc_settings_woocommercenfe_tipo_pessoa') == 'yes' &&
			!$this->wmbr_is_plugin_active('woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php')
		){
			$WC_NFe_Frontend = new WooCommerceNFe_Frontend();
			$WC_NFe_Backend = new WooCommerceNFe_Backend();
			// Frontend
			add_action( 'wp_enqueue_scripts', array($WC_NFe_Frontend, 'scripts') );
			add_filter( 'woocommerce_billing_fields', array($WC_NFe_Frontend, 'billing_fields') );
			add_filter( 'woocommerce_shipping_fields', array($WC_NFe_Frontend, 'shipping_fields') );
			add_action( 'woocommerce_checkout_process', array($WC_NFe_Frontend, 'valide_checkout_fields') );
			add_filter( 'woocommerce_localisation_address_formats', array( $WC_NFe_Frontend, 'localisation_address_formats' ) );
			add_filter( 'woocommerce_formatted_address_replacements', array( $WC_NFe_Frontend, 'formatted_address_replacements' ), 1, 2 );
			add_filter( 'woocommerce_order_formatted_billing_address', array( $WC_NFe_Frontend, 'order_formatted_billing_address' ), 1, 2 );
			add_filter( 'woocommerce_order_formatted_shipping_address', array( $WC_NFe_Frontend, 'order_formatted_shipping_address' ), 1, 2 );
			add_filter( 'woocommerce_my_account_my_address_formatted_address', array($WC_NFe_Frontend, 'my_account_my_address_formatted_address' ), 1, 3 );
			// Backend
			add_filter( 'woocommerce_customer_meta_fields', array( $WC_NFe_Backend, 'customer_meta_fields' ) );
			add_filter( 'woocommerce_user_column_billing_address', array( $WC_NFe_Backend, 'user_column_billing_address' ), 1, 2 );
			add_filter( 'woocommerce_user_column_shipping_address', array( $WC_NFe_Backend, 'user_column_shipping_address' ), 1, 2 );
			add_filter( 'woocommerce_admin_billing_fields', array( $WC_NFe_Backend, 'shop_order_billing_fields' ) );
			add_filter( 'woocommerce_admin_shipping_fields', array( $WC_NFe_Backend, 'shop_order_shipping_fields' ) );
			add_filter( 'woocommerce_found_customer_details', array( $WC_NFe_Backend, 'customer_details_ajax' ) );
			add_action( 'woocommerce_process_shop_order_meta', array( $WC_NFe_Backend, 'save_custom_shop_data' ) );
			add_action( 'woocommerce_api_create_order', array( $WC_NFe_Backend, 'wc_api_save_custom_shop_data' ), 10, 2 );
  		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $WC_NFe_Backend, 'order_data_after_billing_address' ) );
			add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $WC_NFe_Backend, 'order_data_after_shipping_address' ) );
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
	function validadeCertificado( $force_update = false, $return_ajax = false ){
		if (get_transient('validadeCertificado') && !$force_update ) {
			$response = get_transient('validadeCertificado');
		} else {
			if ( !isset(WC_NFe()->settings) ) {
				if ($return_ajax) return json_encode( array( 'status' => 'null_credentials', 'msg' => 'Por favor, informe as credenciais de acesso para obter a validade do Certificado Digital A1.' ), JSON_UNESCAPED_UNICODE );
				return false;
			}
			$webmaniabr = new NFe(WC_NFe()->settings);
			$response = $webmaniabr->validadeCertificado();
		}
		if (isset($response->error)){
            set_transient( 'validadeCertificado', $response, 600 );
			WC_NFe()->add_error( __('Erro: '.$response->error, $this->domain) );
			if ($return_ajax) return json_encode( array( 'status' => 'error', 'msg' => $response->error ), JSON_UNESCAPED_UNICODE );
			return false;
		} else {
            set_transient( 'validadeCertificado', $response, 24 * HOUR_IN_SECONDS );
            if ($return_ajax) return json_encode( array( 'status' => 'success', 'msg' => $response ), JSON_UNESCAPED_UNICODE );
			if ($response < 45 && $response >= 1){
				WC_NFe()->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> Emita um novo Certificado Digital A1 - vencerá em '.$response.' dias.', $this->domain) );
				return false;
			}
			if (!$response) {
				WC_NFe()->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> Certificado Digital A1 vencido. Emita um novo para continuar operando.', $this->domain) );
				return false;
			}
		}
	}
	// Depreciated
	function emitirNFeAutomaticamente( $order_id ){
		$option = get_option('wc_settings_woocommercenfe_emissao_automatica');
		if ( $option == 1 || $option == 'yes' ) {
			$nfe = get_post_meta( $order_id, 'nfe', true );
			$nfce = get_post_meta( $order_id, 'nfce', true );

			if((is_array($nfe) && !empty($nfe)) || (is_array($nfce) && !empty($nfce))){
				return false;
			}

			$tipo = apply_filters('webmaniabr_modelo_nota', 'nfe', $order_id);
			if ($tipo == 'nfe') {
			  return self::emitirNFe( array( $order_id ) );
		  } else if ($tipo == 'nfce') {
				return self::emitirNFCe( array( $order_id ) );
			}
			return false;
		}
	}
	function emitirNFeAutomaticamenteOnStatusChange( $order_id ) {

		do_action( 'before_emitirNFeAutomaticamenteOnStatusChange', $order_id );

		$response = false;
		$option = get_option('wc_settings_woocommercenfe_emissao_automatica');
		$force = apply_filters('webmaniabr_emissao_automatica', $force,  $option, $order_id);

		// If the option "Emitir Automaticamente" is enabled and
		// the post type is equal to 'shop_order'
		if (get_post_type( $order_id ) == 'shop_order' ) {

			$order = wc_get_order( $order_id );
				// Get the field with all "NF-e" informations
				$nfes = get_post_meta( $order_id, 'nfe', true );
				$nfces = get_post_meta( $order_id, 'nfce', true );

				// Check if is empty or invalid
				if( !empty($nfes) && is_array($nfes) ) {
					// If exists, find for any approved document
					foreach ( $nfes as $nfe ) {
						if ( !empty($nfe['status'])) {
								return false;
						}
					}
				}

				if( !empty($nfces) && is_array($nfces) ) {
					// If exists, find for any approved document
					foreach ( $nfces as $nfce ) {
						if ( !empty($nfce['status'])) {
								return false;
						}
					}
				}

				$nao_emitir = get_post_meta($order_id, '_nfe_nao_emitir', true);
				if (!$nao_emitir) {
					// If all conditions was match, call function
					$tipo = apply_filters('webmaniabr_modelo_nota', 'nfe', $order_id);
					if ($tipo == 'nfe') {
					  $response = self::emitirNFe( array( $order_id ) );
				  } else if ($tipo == 'nfce') {
						$response = self::emitirNFCe( array( $order_id ) );
					}
				}
		}

		do_action( 'after_emitirNFeAutomaticamenteOnStatusChange', $order_id, $response );

		return $response;

	}
	/**
	 * Send e-mail notification to user when auto invoice fail
	 **/
	function send_error_email( $message, $order_id ) {

		$email = get_option('wc_settings_woocommercenfe_email_notification');

		if ( !isset($email) ) return;

		$subject = 'Erro ao emitir NF-e - Pedido #'.$order_id;
		$message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		  <html xmlns="http://www.w3.org/1999/xhtml">
		  <head><meta charset="UTF-8"></head>
		  	<body>
		  		<p>Houve um erro de emissão automatica no Pedido #'.$order_id.': <a target="_blank" href="'.get_admin_url().'/post.php?post='.$order_id.'&action=edit">Acesse o pedido</a></p>
		  		'.$message.'
			</body>
		</html>';

		$headers = array(
		  	'Content-Type: text/html; charset=UTF-8'
		);
		$emails = array($email);
		$enviar_email = wp_mail($emails, $subject, $message, $headers);
	}
	/**
	 * Add order id to list of automatic invoice errors
	 **/
	function add_id_to_invoice_errors( $message, $order_id ) {

		$ids_db = get_option('wmbr_auto_invoice_errors');

		$nfes = get_post_meta( $order_id, 'nfe', true );
		if( !empty($nfes) && is_array($nfes) ) {
			foreach ( $nfes as $nfe ) {
				if ( $nfe['status'] == 'aprovado' ) {
						return false;
				}
			}
		}
		$ids_db[$order_id] = array(
			'datetime' => current_time("d-m-Y H:i:s"),
			'error' => $message
		);
		update_option( 'wmbr_auto_invoice_errors', $ids_db );
	}
	/**
	 * Remove order id to list of automatic invoice errors
	 **/
	function remove_id_to_invoice_errors( $order_id ) {

		$ids_db = get_option('wmbr_auto_invoice_errors');
		if ( is_array($ids_db) ) {
			if ( !array_key_exists($order_id, $ids_db) ) return false;

			unset($ids_db[$order_id]);
			update_option( 'wmbr_auto_invoice_errors', $ids_db );
		}
	}
	function emitirNFe( $order_ids = array(), $is_massa = false ){

		$result = array();

		foreach ($order_ids as $order_id) {

			$data = self::order_data( $order_id );
			if ($is_massa) {
				$data['assincrono'] = 1;
			}

			do_action('nfe_before_response', $data, $order_id);

			$webmaniabr = new NFe(WC_NFe()->settings);
			$response = $webmaniabr->emissaoNotaFiscal( $data );
			$result[] = $response;

			do_action('nfe_after_response', $response, $order_id);

			if (isset($response->error) || $response->status == 'reprovado') {
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

				$invoice_error = isset($response->error) ? $response->error : $error;
				$this->send_error_email( $mensagem, $order_id );
				$this->add_id_to_invoice_errors( $invoice_error, $order_id );
				WC_NFe()->add_error( $mensagem );
				return false;
			} else {
				WC_NFe()->add_success( 'NF-e emitida com sucesso do Pedido #'.$order_id );
				$this->remove_id_to_invoice_errors($order_id);
			}
			// If API respond with status, register 'NF-e'
			if ( is_object($response) && $response->status ) {
				$nfe = get_post_meta( $order_id, 'nfe', true );
				if (!$nfe) $nfe = array();
				$nfe[] = array(
					'uuid'   => (string) $response->uuid,
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
				return $nfe;
			}
		}

		return $result;

	}

	function emitirNFCe( $order_ids = array() ){
		foreach ($order_ids as $order_id) {
			$data = self::order_data( $order_id, 2 );

			$webmaniabr = new NFe(WC_NFe()->settings);
			$response = $webmaniabr->emissaoNotaFiscal( $data );

			if (isset($response->error) || $response->status == 'reprovado'){
				$mensagem = 'Erro ao emitir a NFC-e do Pedido #'.$order_id.':';
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
				return false;
			} else {
				WC_NFe()->add_success( 'NFC-e emitida com sucesso do Pedido #'.$order_id );
			}

			if ( is_object($response) && $response->status ) {
				$nfce = get_post_meta( $order_id, 'nfce', true );
				if (!$nfce) $nfce = array();
				$nfce[] = array(
					'uuid'   => (string) $response->uuid,
					'status' => (string) $response->status,
					'chave_acesso' => $response->chave,
					'n_recibo' => (int) $response->recibo,
					'n_nfe' => (int) $response->nfe,
					'n_serie' => (int) $response->serie,
					'url_xml' => (string) $response->xml,
					'url_danfe' => (string) $response->danfe,
					'data' => date_i18n('d/m/Y'),
				);
				update_post_meta( $order_id, 'nfce', $nfce );
				WC_NFe()->add_success( 'NFC-e emitida com sucesso do Pedido #'.$order_id );
				return $nfce;
			}
		}
	}
	function order_data( $post_id, $modelo = 1 ){
		global $wpdb;
		$WooCommerceNFe_Format = new WooCommerceNFe_Format;
		$payment_methods       = get_option('wc_settings_woocommercenfe_payment_methods', array());
		$payment_keys = array_keys($payment_methods);
		$order = new WC_Order( $post_id );
		//Antigo código EAN
		$default_gtin            = get_option('wc_settings_woocommercenfe_ean');
		$default_gtin_tributavel = get_option('wc_settings_woocommercenfe_gtin_tributavel');
		$default_ncm     = get_option('wc_settings_woocommercenfe_ncm');
		$default_cest    = get_option('wc_settings_woocommercenfe_cest');
		$default_origem  = get_option('wc_settings_woocommercenfe_origem');
		$default_imposto = get_option('wc_settings_woocommercenfe_imposto');
		$default_weight  = '0.100';
		$transportadoras = get_option('wc_settings_woocommercenfe_transportadoras', array());
		$envio_email = get_option('wc_settings_woocommercenfe_envio_email');
		$coupons = $order->get_used_coupons();
		$coupons_percentage = array();
		$total_discount = $total_fee = 0;
		$fee_aditional_informations = '';
		if ($coupons){
			foreach($coupons as $coupon_code){
				$coupon_obj = new WC_Coupon($coupon_code);
				if($coupon_obj->discount_type == 'percent'){
					$coupons_percentage[] = $coupon_obj->coupon_amount;
				}
			}
		}

		// Order
		$modalidade_frete = $_POST['modalidade_frete'];
		if (!isset($modalidade_frete)) $modalidade_frete = get_post_meta($post_id, '_nfe_modalidade_frete', true);
		if (!$modalidade_frete || $modalidade_frete == 'null' || empty($modalidade_frete)) $modalidade_frete = apply_filters('webmaniabr_pedido_modalidade_frete', $modelo == 1 ? 0 : 9, $modalidade_frete, $modelo, $post_id, $order );
		$order_key = $order->order_key;

		$natureza_operacao_pedido = get_post_meta($order->id, '_nfe_natureza_operacao_pedido', true);
		if ( $natureza_operacao_pedido ) {
			$natureza_operacao = $natureza_operacao_pedido;
		} else {
			$natureza_operacao = get_option('wc_settings_woocommercenfe_natureza_operacao');
		}

		if ( isset($_POST['natureza_operacao_pedido']) && $_POST['natureza_operacao_pedido'] != '' && $_POST['natureza_operacao_pedido'] != $natureza_operacao ) {
			$natureza_operacao = $_POST['natureza_operacao_pedido'];
		}

		$data = array(
			'ID'                => $post_id, // Número do pedido
			'origem'					  => 'woocommerce',
			'url_notificacao'   => get_bloginfo('url').'/wc-api/nfe_callback?order_key='.$order_key.'&order_id='.$post_id,
			'operacao'          => 1, // Tipo de Operação da Nota Fiscal
			'natureza_operacao' => $natureza_operacao, // Natureza da Operação
			'modelo'            => $modelo, // Modelo da Nota Fiscal (NF-e ou NFC-e)
			'emissao'           => 1, // Tipo de Emissão da NF-e
			'finalidade'        => 1, // Finalidade de emissão da Nota Fiscal
			'ambiente'          => ( isset($_POST['emitir_homologacao']) && $_POST['emitir_homologacao'] ? '2' : (get_post_meta($order->id, '_nfe_emitir_homologacao', true) ? '2' : (int) get_option('wc_settings_woocommercenfe_ambiente')) ) // Identificação do Ambiente do Sefaz
		);

		if ($order->get_fees()){
			foreach ($order->get_fees() as $key => $item){
				if ($item['line_total'] < 0){
					$discount = abs($item['line_total']);
					$total_discount = $discount + $total_discount;
				} else {
					if ( $fee_aditional_informations != '' ) $fee_aditional_informations .= ' / ';
					$fee_aditional_informations .= $item['name'] . ': R$' . number_format($item['line_total'], 2, ',', '');
					$fee = $item['line_total'];
					$total_fee = $fee + $total_fee;
				}
			}
		}

		$total_discount = $order->get_total_discount() + $total_discount;

		$data_emissao = get_option('wc_settings_woocommercenfe_data_emissao');
		if ( isset($data_emissao) && $data_emissao == 'yes' ) {
			$data['data_emissao'] = get_the_time('Y-m-d H:i:s', $post_id);
			$data['data_entrada_saida'] = get_the_time('Y-m-d H:i:s', $post_id);
		}

		$data['pedido'] = array(
			'presenca'         => apply_filters('webmaniabr_pedido_presenca', $modelo == 1 ? 2 : 1, $post_id, $modelo, $order), // Indicador de presença do comprador no estabelecimento comercial no momento da operação
			'modalidade_frete' => (int) $modalidade_frete, // Modalidade do frete
			'frete'            => get_post_meta( $order->id, '_order_shipping', true ), // Total do frete
			'desconto'         => $total_discount, // Total do desconto
			'total'            => $order->order_total // Total do pedido - sem descontos
		);

		if ( $total_fee && $total_fee > 0 ) {
			$data['pedido']['despesas_acessorias'] = number_format($total_fee, 2, '.', '');
		}

		/** Check before create installments informations
		 * - Plugin "EBANX Local Payment Gateway for WooCommerce" is active
		 * - Option in "Nota Fiscal" configuration oage is enabled
		 * - The order has a credit card payment
		 */
		if (
			self::wmbr_is_plugin_active('ebanx-local-payment-gateway-for-woocommerce/woocommerce-gateway-ebanx.php') &&
			get_option('wc_settings_parcelas_ebanx') == 'yes' &&
			get_post_meta( $post_id, '_cards_brand_name', true) != ''
		) {

			$parcelas = get_post_meta( $post_id, '_instalments_number', true);
			// Check if the order has installments or if is greater than 1
			if ( isset($parcelas) || $parcelas > 1 ) {

				$valor_total = $order->order_total;

				// Create 'fatura' array
				$data['fatura'] =  array(
					'numero'		=> '000001',
					'valor'		 	=> $valor_total + $total_discount,
					'desconto'		=> $total_discount,
					'valor_liquido' => $valor_total
				);

				// Declare vars
				$data['parcelas'] = array();
				$valor_parcela = round($valor_total / $parcelas, 2);
				$valor_somatorio = 0;
				$data_pedido = get_the_time('Y-m-d', $post_id);

				for ( $i = 1; $i <= $parcelas; $i++ ) {

					// When reach the last intallment, calculate the total
					if ( $i == $parcelas ) {
						$valor_parcela = $valor_total - $valor_somatorio;
					} else {
						$valor_somatorio += $valor_parcela;
					}

					// Add installment to NF-e invoice
					$data['parcelas'][] = array(
						'vencimento' => $data_pedido,
						'valor' => $valor_parcela
					);

					// Add 30 days to next installment
					$data_pedido = date('Y-m-d', strtotime("+1 month", strtotime($data_pedido)));

				}

			}

		}

		//Define forma de pagamento (obrigatório NFe 4.0)
		if( in_array($order->payment_method, $payment_keys) ){
			//Caso pagseguro, verificar post meta para método de pagamento
			//Senão, pegar valor salvo nas configurações
			if($order->payment_method == 'pagseguro'){
				$payment_type = get_post_meta($post_id, __( 'Payment type', 'woocommerce-pagseguro' ), true);
				if( strtolower($payment_type) == 'boleto'){
					$data['pedido']['forma_pagamento'] = '15';
				}elseif($payment_type == 'Cartão de Crédito'){
					$data['pedido']['forma_pagamento'] = '03';
				}
			}else{
				$data['pedido']['forma_pagamento'] = $payment_methods[$order->payment_method];
			}
		}

		if ($modelo == 2) {
			$data['pedido']['pagamento'] = apply_filters('webmaniabr_pedido_pagamento', 0, $post_id, $order);
			$data['pedido']['tipo_integracao'] = apply_filters('webmaniabr_pedido_tipo_integracao', 2, $post_id, $order);

			if ($data['pedido']['forma_pagamento'] == '01' ) {
				// TODO: colocar o valor padrão exato do pedido, fingindo que não tem troco.
				$data['pedido']['valor_pagamento'] = apply_filters('webmaniabr_pedido_valor_pagamento', 0, $post_id, $order);
			} else if ($data['pedido']['forma_pagamento'] == '03' || $data['pedido']['forma_pagamento'] == '04') {
				$data['pedido']['cnpj_credenciadora'] = apply_filters('webmaniabr_pedido_cnpj_credenciadora', get_option('wc_settings_woocommercenfe_cnpj_fabricante'), $post_id, $order);
				$data['pedido']['bandeira'] = apply_filters('webmaniabr_pedido_bandeira', '', $post_id, $order);
				$data['pedido']['autorizacao'] = apply_filters('webmaniabr_pedido_autorizacao', '', $post_id, $order);
			}
		}


		//Informações Complementares ao Fisco
		$fisco_inf = get_option('wc_settings_woocommercenfe_fisco_inf');
		if(!empty($fisco_inf) && strlen($fisco_inf) <= 2000){
			$data['pedido']['informacoes_fisco'] = $fisco_inf;
		}
		//Informações Complementares ao Consumidor
		$consumidor_inf = get_option('wc_settings_woocommercenfe_cons_inf');
		if ( $fee_aditional_informations != '' ) {
			$consumidor_inf .= $fee_aditional_informations;
		}
		if(!empty($consumidor_inf) && strlen($consumidor_inf) <= 2000){
			$data['pedido']['informacoes_complementares'] = $consumidor_inf;
		}
		if ($order->get_user() !== false || $modelo == 1) {
			// Customer
			$compare_addresses = self::compare_addresses($order->id, $envio_email);
			$data['cliente'] = $compare_addresses['cliente'];

			if ( isset($compare_addresses['transporte']['entrega']) ) {
				$data['transporte']['entrega'] = $compare_addresses['transporte']['entrega'];
			}
		}
		$cellphone = get_user_meta($post_id, 'billing_cellphone', true) ? get_user_meta($post_id, 'billing_cellphone', true) : get_user_meta($post_id, '_billing_cellphone', true);
		if ( $data['cliente']['telefone'] && $cellphone ) {
			$data['pedido']['informacoes_complementares'] .= ' / Celular: ' . $cellphone;
		}

		// Products
		$bundles = array();
		if(!isset($data['produtos'])) $data['produtos'] = array();
		foreach ($order->get_items() as $key => $item){
			$product      = $order->get_product_from_item( $item );
			$product_type = $product->get_type();
			$product_id   = $item['product_id'];
			$bundled_by = isset($item['bundled_by']);
			if(!$bundled_by && is_a($item, 'WC_Order_Item_Product')){
				$bundled_by = $item->meta_exists('_bundled_by');
			}
			$variation_id = $item['variation_id'];
			if( $product_type == 'bundle' || $product_type == 'yith_bundle' || $product_type == 'mix-and-match' || $bundled_by ){
				$bundles[] = $item;
				continue;
			}
			$product_info = self::get_product_nfe_info($item, $order);
			$ignorar_nfe = get_post_meta($product_id, '_nfe_ignorar_nfe', true);
			if($ignorar_nfe == 1){
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
	  //Default transportadora info
		$shipping_method = @array_shift($order->get_shipping_methods());
		$shipping_method_id = $shipping_method['method_id'];
		if(strpos($shipping_method_id, ':')){
			$shipping_method_id = substr($shipping_method['method_id'], 0, strpos($shipping_method['method_id'], ":"));
		}
		$include_shipping_info = get_option('wc_settings_woocommercenfe_transp_include');
		if($include_shipping_info == 'on' && isset($transportadoras[$shipping_method_id])){
			$transp = $transportadoras[$shipping_method_id];
			$data['transporte']['cnpj']         = $transp['cnpj'];
			$data['transporte']['razao_social'] = $transp['razao_social'];
			$data['transporte']['ie']           = $transp['ie'];
			$data['transporte']['cpf']          = $transp['cpf'];
			$data['transporte']['nome_completo']= $transp['nome'];
			$data['transporte']['endereco']     = $transp['address'];
			$data['transporte']['uf']           = $transp['uf'];
			$data['transporte']['cidade']       = $transp['city'];
			$data['transporte']['cep']          = $transp['cep'];
			$data['transporte']['placa']        = $transp['placa'];
			$data['transporte']['uf_veiculo']   = $transp['uf_veiculo'];
		}
		$order_specifics = array(
			'volume' => '_nfe_transporte_volume',
			'especie' => '_nfe_transporte_especie',
			'peso_bruto' => '_nfe_transporte_peso_bruto',
			'peso_liquido' => '_nfe_transporte_peso_liquido'
		);
		foreach($order_specifics as $api_key => $meta_key){
			$value = $_POST[str_replace('_nfe_', '', $meta_key)];
			if (!isset($value)) $value = get_post_meta($post_id, $meta_key, true);
			if ($value){
				$data['transporte'][$api_key] = $value;
			}
		}
		return apply_filters('nfe_order_data', $data, $post_id);
	}
	function get_product_nfe_info($item, $order){
		global $wpdb;
		$product_id  = $item['product_id'];
		$product     = $order->get_product_from_item( $item );
		$ignorar_nfe = get_post_meta($product_id, '_nfe_ignorar_nfe', true);
		//Antigo código ean
		$codigo_gtin  = get_post_meta($product_id, '_nfe_codigo_ean', true);
		$gtin_tributavel = get_post_meta($product_id, '_nfe_gtin_tributavel', true);
		$codigo_ncm  = get_post_meta($product_id, '_nfe_codigo_ncm', true);
		$codigo_cest = get_post_meta($product_id, '_nfe_codigo_cest', true);
		$origem      = get_post_meta($product_id, '_nfe_origem', true);
		$unidade     = get_post_meta($product_id, '_nfe_unidade', true);
		$imposto     = get_post_meta($product_id, '_nfe_classe_imposto', true);
		$ind_escala  = get_post_meta($product_id, '_nfe_ind_escala', true);
		$cnpj_fabricante = get_post_meta($product_id, '_nfe_cnpj_fabricante', true);
		$peso        = $product->get_weight();
		$informacoes_adicionais = '';
		$informacoes_adicionais = get_post_meta($product_id, '_nfe_produto_informacoes_adicionais', true);

		if (!$peso){
			$peso = '0.100';
		}
		if (!$codigo_gtin){
			$codigo_gtin = get_option('wc_settings_woocommercenfe_ean');
		}
		if(!$gtin_tributavel){
			$gtin_tributavel = get_option('wc_settings_woocommercenfe_gtin_tributavel');
		}
		if (!$codigo_ncm){
			$product_cat = get_the_terms($product_id, 'product_cat');
			if(is_array($product_cat)){
				foreach($product_cat as $cat){
					if(function_exists('get_term_meta')){
						$ncm = get_term_meta($cat->term_id, '_ncm', true);
					}
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
		if(!$ind_escala){
			$ind_escala = get_option('wc_settings_woocommercenfe_ind_escala');
			if($ind_escala == 'null') $ind_escala = '';
		}
		if(!$cnpj_fabricante){
			$cnpj_fabricante = get_option('wc_settings_woocommercenfe_cnpj_fabricante');
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
				'gtin' => $codigo_gtin, // Código GTIN
				'gtin_tributavel' => $gtin_tributavel,
				'ncm' => $codigo_ncm, // Código NCM
				'cest' => $codigo_cest, // Código CEST
				'ind_escala' => $ind_escala,
				'cnpj_fabricante' => $cnpj_fabricante,
				'quantidade' => $item['qty'], // Quantidade de itens
				'unidade' => $unidade ? $unidade : 'UN', // Unidade de medida da quantidade de itens
				'peso' => $peso, // Peso em KG. Ex: 800 gramas = 0.800 KG
				'origem' => (int) $origem, // Origem do produto
				'subtotal' => number_format($product_active_price, 2, '.', '' ), // Preço unitário do produto - sem descontos
				'total' => number_format($product_active_price*$item['qty'], 2, '.', '' ), // Preço total (quantidade x preço unitário) - sem descontos
				'classe_imposto' => $imposto // Referência do imposto cadastrado
			);

			if ( $informacoes_adicionais != '' ) {
				$info['informacoes_adicionais'] = $informacoes_adicionais;
			}

			return apply_filters('nfe_order_data_product', $info, $order->id);
	}
	function set_bundle_products_array( $bundles, $order){
		$total_bundle = 0;
		$total_products = 0;
		$bundle_products = array();
		foreach($bundles as $item){
			$product = $order->get_product_from_item( $item );
			$product_type = $product->get_type();
			$product_price = $product->get_price();
			$bundled_by = isset($item['bundled_by']);
			if(!$bundled_by && is_a($item, 'WC_Order_Item_Product')){
				$bundled_by = $item->meta_exists('_bundled_by');
			}
			$product_total = $product_price * $item['qty'];
			if($bundled_by){
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
			}elseif($product_type == 'mix-and-match'){
				$total_products_bundle = 0;
				$mnm_products_price = 0;
				$mnm_qty = $item['qty'];
				foreach ( $order->get_items() as $key => $item ) {
					// Get if the product belongs to Mix and Match to calculate discount
					$mnm_configs = wc_get_order_item_meta( $key, '_mnm_config', true);
					if ( $mnm_configs ) {
						// Get the total and quantity from Mix and Match bundle
						$mnm_product_price_total = wc_get_order_item_meta( $key, '_line_subtotal', true); // Get subtotal to aply coupon discount after, line_total aplies this discount before needed
						$mnm_product_price_qty = wc_get_order_item_meta( $key, '_qty', true);
						// Use the products inside the bundle
						foreach ( $mnm_configs as $mnm_config ) {
							// Load the product
							$product_mnm = wc_get_product( $mnm_config['product_id'] );
							// Store the discount from bundle
							$mnm_products_price += $product_mnm->get_price() * ( $mnm_config['quantity'] * $mnm_product_price_qty );
						}
						// Update total discount
						$mnm_products_price -= $mnm_product_price_total;
					}
				}
				$total_discount = $mnm_products_price;
			}
		}
		if($total_products < $total_bundle && $product_type != 'mix-and-match'){
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
		}else if($product_type == 'mix-and-match'){
			$discount = abs($total_discount);
		}else{
			$discount = abs($total_bundle - $total_products);
		}
		return array('products' => $bundle_products, 'bundle_discount' => $discount);
	}
	function default_plugin_url( $url ){
		return str_replace('inc/', '', $url);
	}
	public function get_pagseguro_bandeira($order_id){
		$payment_type = get_post_meta($order_id, __( 'Payment method', 'woocommerce-pagseguro' ), true);
		$payment_code = '99';
		$bandeiras = $this->get_bandeiras_list();
		$payment_type = str_replace(array('Cartão de crédito', 'Cartão de débito'), '', $payment_type);
		$payment_type = trim($payment_type);
		foreach($bandeiras as $code => $brand){
			if(stripos($brand, $payment_type) !== false){
				$payment_code = $code;
				break;
			}
		}
		return $payment_code;
	}
	public function get_bandeiras_list(){
		$bandeiras = array(
			'01' => 'Visa / Visa Electron',
			'02' => 'Mastercard / Maestro',
			'03' => 'American Express',
			'04' => 'Sorocred',
			'05' => 'Diners Club',
			'06' => 'Elo',
			'07' => 'Hipercard',
			'08' => 'Aura',
			'09' => 'Cabal'
		);
		return $bandeiras;
	}
	/**
	 * Config button
	**/
	public static function plugin_add_settings_link( $links ) {
	    $action_links = array(
	      'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=woocommercenfe_tab' ) . '" aria-label="Visualizar Configurações">Configurações</a>',
	    );
	    return array_merge( $action_links, $links );
	}
	/**
	 * Return alerts to users from plugins that has incompatibility
	**/
	public function wmbr_compatibility_issues() {
		if ( isset($_POST['action']) ) return;
		$plugins_list = array(
			'redis-cache/redis-cache.php' => 'Redis Object Cache'
		);
		foreach ( $plugins_list as $plugin_path => $plugin_name ) {
			if ( $this->wmbr_is_plugin_active($plugin_path) ) {
				echo '<div class="error">
						<p>O plugin <b>'.$plugin_name.'</b> não possui compatibilidade com os plugins <b>WooCommerce</b> e <b>Nota Fiscal Eletrônica WooCommerce</b>.</p>
						<p>Por favor, desative-o para prosseguir com as emissões de Nota Fiscal.</p>
					</div>';
			}
		}
	}
	/**
	 * Function to handle ajax requisistion for force digital certificate update
	**/
	public function force_digital_certificate_update() {
		echo $this->validadeCertificado( true, true );
		wp_die();
	}
	/**
	 * Custom function to verify if plugin is active
	**/
	public function wmbr_is_plugin_active( $plugin ) {
		return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
	}
	/**
	 * Verify if shipping and billing informations are different
	**/
	public function compare_addresses($post_id, $envio_email) {

		$WooCommerceNFe_Format = new WooCommerceNFe_Format;

		$phone = (get_user_meta($post_id, 'billing_phone', true) ? get_user_meta($post_id, 'billing_phone', true) : get_post_meta($post_id, '_billing_phone', true));
		if (empty($phone))
			$phone = (get_user_meta($post_id, 'billing_cellphone', true) ? get_user_meta($post_id, 'billing_cellphone', true) : get_post_meta($post_id, '_billing_cellphone', true));
		$email = ($envio_email && $envio_email == 'yes' ? get_post_meta($post_id, '_billing_email', true) : '');

		$billing = array(
			'endereco'    => get_post_meta($post_id, '_billing_address_1', true),
			'complemento' => get_post_meta($post_id, '_billing_address_2', true),
			'numero'      => get_post_meta($post_id, '_billing_number', true),
			'bairro'      => get_post_meta($post_id, '_billing_neighborhood', true),
			'cidade'      => get_post_meta($post_id, '_billing_city', true),
			'uf'          => get_post_meta($post_id, '_billing_state', true),
			'cep'         => $WooCommerceNFe_Format->cep(get_post_meta($post_id, '_billing_postcode', true)),
			'telefone'    => $phone,
			'email'       => $email
		);
		$shipping = array(
			'endereco'    => get_post_meta($post_id, '_shipping_address_1', true),
			'complemento' => get_post_meta($post_id, '_shipping_address_2', true),
			'numero'      => get_post_meta($post_id, '_shipping_number', true),
			'bairro'      => get_post_meta($post_id, '_shipping_neighborhood', true),
			'cidade'      => get_post_meta($post_id, '_shipping_city', true),
			'uf'          => get_post_meta($post_id, '_shipping_state', true),
			'cep'         => $WooCommerceNFe_Format->cep(get_post_meta($post_id, '_shipping_postcode', true)),
			'telefone'    => $phone,
			'email'       => $email
		);

		// Compare and return transporte->entrega if are different addressses
		if ( $billing === $shipping ) {

			// Detect persontype and merge informations
			$tipo_pessoa_shipping = self::detect_persontype($post_id, '_shipping');
			$shipping = array_merge( self::get_persontype_info($post_id, $tipo_pessoa_shipping, '_shipping'), $shipping);

			$return['cliente'] = $shipping;

		} else {

			// Detect persontype and merge informations
			$tipo_pessoa_billing = self::detect_persontype($post_id, '_billing');
			$tipo_pessoa_shipping = self::detect_persontype($post_id, '_shipping');

			$billing = array_merge( self::get_persontype_info($post_id, $tipo_pessoa_billing, '_billing'), $billing);
			$shipping = array_merge( self::get_persontype_info($post_id, $tipo_pessoa_shipping, '_shipping'), $shipping);

			$return['cliente'] = $billing;
			$return['transporte']['entrega'] = $shipping;
		}

		return $return;

	}
	/**
	 * Detect persontype from order
	**/
	public function detect_persontype($post_id, $type = '_billing') {

		$tipo_pessoa = get_post_meta($post_id, $type.'_persontype', true);

		if ( !$tipo_pessoa && $type == '_shipping' ) {
			return 3;
		} elseif ( !$tipo_pessoa ) {

			if ( !empty(get_post_meta($post_id, $type.'_cpf', true)) ) {
				$tipo_pessoa = 1;
			} elseif ( !empty(get_post_meta($post_id, $type.'_cnpj', true)) ) {
				$tipo_pessoa = 2;
			}

			if (!$tipo_pessoa) $tipo_pessoa = 1;

		}

		return $tipo_pessoa;

	}
	/**
	 * Get informations from persontype
	**/
	public function get_persontype_info($post_id, $persontype = 1, $type = '_billing') {

		$WooCommerceNFe_Format = new WooCommerceNFe_Format;
		$was_shipping = false;

		if ( $persontype == 3 && $type == '_shipping' ) {
			$persontype = self::detect_persontype($post_id, '_billing');
			$type = '_billing';
			$was_shipping = true;
		}

		if ( $persontype == 1 ) {

			// Full name and CPF
			// Pegar a informação que o cliente digitou é mais importante que não colocar a informação correta na nota, já que o cliente explicitamente solicitou a entrega para outra pessoa, mas não tem a opção de digitar o CPF da mesma.
			$person_info['nome_completo'] = get_post_meta($post_id, ($was_shipping ? '_shipping' : $type) . '_first_name', true).' '.get_post_meta($post_id, ($was_shipping ? '_shipping' : $type) . '_last_name', true);
			$person_info['cpf'] = $WooCommerceNFe_Format->cpf(get_post_meta($post_id, $type.'_cpf', true));

		} elseif ( $persontype == 2 ) {

			// Razao Social, CNPJ and IE
			$person_info['razao_social'] = get_post_meta($post_id, $type.'_company', true);
			$person_info['cnpj'] = $WooCommerceNFe_Format->cnpj(get_post_meta($post_id, $type.'_cnpj', true));
			$person_info['ie'] = str_replace(array('-','.',','), '', get_post_meta($post_id, $type.'_ie', true));

		}

		return $person_info;

	}

}

class WebmaniaBR_Rest_Controller extends WP_REST_Controller {

  //The namespace and version for the REST SERVER
  var $my_namespace = 'wc/v';
  var $my_version   = '3';

  public function register_routes() {
    $namespace = $this->my_namespace . $this->my_version;
    $base      = 'nota-fiscal';
    register_rest_route( $namespace, '/' . $base, array(
      array(
          'methods'         => WP_REST_Server::READABLE,
          'callback'        => array( $this, 'get_nota_fiscal' ),
          'permission_callback'   => array( $this, 'get_nota_fiscal_permission' )
        )
    )  );
  }

  // Register our REST Server
  public function hook_rest_server(){
    add_action( 'rest_api_init', array( $this, 'register_routes' ) );
  }

  public function get_nota_fiscal_permission(){
    if ( ! current_user_can( 'view_register' ) ) {
          return new WP_Error( 'rest_forbidden', 'Sem permissão para executar essa ação.', array( 'status' => 401 ) );
      }

      // This approach blocks the endpoint operation. You could alternatively do this by an un-blocking approach, by returning false here and changing the permissions check.
      return true;
  }

  public function get_nota_fiscal( WP_REST_Request $request ) {
    //Let Us use the helper methods to get the parameters
    $id = $request->get_param( 'id' );
		$force = $request->get_param( 'force' );
		if (!$force)
			$force = false;

		if (!$id)
			return array(false);

			$option = get_option('wc_settings_woocommercenfe_emissao_automatica');
			$force = apply_filters('webmaniabr_emissao_automatica', $force,  $option, $id);

			if ( ($force || $option == 1 ||  $option == 'yes' ) && get_post_type( $id ) == 'shop_order' ) {
				$return = WooCommerceNFe::instance()->emitirNFeAutomaticamenteOnStatusChange($id, $force);
			}

		if (!$return) {
			$nfe = get_post_meta( $id, 'nfe', true );
			$nfce = get_post_meta( $id, 'nfce', true );

			if (is_array($nfe) && !empty($nfe)) {
				return $nfe;
			} else if (is_array($nfce) && !empty($nfce)) {
				return $nfce;
			}
		}

		return $return;
  }
}
$my_rest_server = new WebmaniaBR_Rest_Controller();
$my_rest_server->hook_rest_server();

/**
* Active plugin
*/
add_action( 'plugins_loaded', array( WC_NFe(), 'init' ), 20);
function WC_NFe(){
	return WooCommerceNFe::instance();
}
