<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WooCommerceNFe {

	public $domain = 'WooCommerceNFe';
	public $version = '3.1.3.1';
	protected static $_instance = NULL;

	public static function instance() {
    if ( is_null( self::$_instance ) ) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  public function __get( $key ) {
    return $this->$key();
	}
	
	function __construct(){

		global $domain, $woocommerce;

		// Validate plugin before Load
		if (!$this->validate_plugin()){
			return false;
		}

		// Globals
		$GLOBALS['version_woonfe'] = $this->version;

		// Load plugin
		$this->includes();
		$this->load_plugin_text_domain();
		$this->validate_plugin_config();
		$this->hooks();

	}

	/**
	 * Hooks
	 * 
	 * @return void
	 */
	function hooks(){

		add_filter( 'woocommercenfe_plugins_url', array($this, 'default_plugin_url') );
		add_action( 'woocommerce_order_status_changed', array($this, 'issue_automatic_invoice'), 10, 4 );
		add_filter( "plugin_action_links_".plugin_basename( __FILE__ ), array($this, 'plugin_add_settings_link') );
		do_action( 'woocommercenfe_loaded' );

	}

	/**
	 * Validate Plugin before Load
	 * 
	 * @return boolean
	 */
	function validate_plugin(){

		global $pagenow, $woocommerce;

		// WooCommerce Plugin
		if ( !class_exists( 'WooCommerce' ) ) {
			$this->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> Para a emissão de Nota Fiscal Eletrônica é necessário ativar o plugin WooCommerce.', $this->domain) );
			return false;
		}

		// WooCommerce Version
		$vars = get_object_vars($woocommerce);

		if ($vars['version'] < '3.0.0'){
			$this->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> Atualize o WooCommerce para a versão 3.0.0 ou superior.', $this->domain) );
			return false;
		}

		// cURL function
		if (!function_exists('curl_version')){
			$this->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> Necessário instalar o comando cURL no servidor, entre em contato com a sua hospedagem ou administrador do servidor.', $this->domain) );
			return false;
		}

		// Its valid
		return true;

	}

	/**
	 * Validate Plugin
	 * 
	 * @author boolean
	 */
	function validate_plugin_config(){

		global $pagenow;

		if (
			is_admin() && 
			$pagenow && 
			( 
				in_array( $pagenow, [ 'index.php', 'admin.php', 'plugins.php' ] ) ||
				strpos($pagenow, 'options-') !== false
			)
		){

			// Validate Credentials
			if (
				!get_option('wc_settings_woocommercenfe_access_token') ||
				!get_option('wc_settings_woocommercenfe_access_token_secret') ||
				!get_option('wc_settings_woocommercenfe_consumer_key') ||
				!get_option('wc_settings_woocommercenfe_consumer_secret')
			) {

				$this->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> Informe as credenciais de acesso da aplicação em WooCommerce > Configurações > Nota Fiscal.', $this->domain) );
				return false;

			} elseif (
				!get_option('wc_settings_woocommercenfe_natureza_operacao') ||
				!get_option('wc_settings_woocommercenfe_imposto') ||
				!get_option('wc_settings_woocommercenfe_ncm') ||
				get_option('wc_settings_woocommercenfe_origem') < 0 ||
				get_option('wc_settings_woocommercenfe_origem') == 'null'
			) {
	
				$this->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> Informe a Natureza da Operação, Classe de Imposto, Código NCM e Origem do produto em WooCommerce > Configurações > Nota Fiscal.', $this->domain) );
					return false;
	
			}

		}

		// Its validated
		return true;

	}

	/**
	 * Includes
	 */
	function includes(){

		include_once( 'inc/sdk/NFe.php' );
		include_once( 'inc/utils.php' );
		include_once( 'inc/gateways/utils.php' );
		include_once( 'inc/gateways/bacs.php' );
		include_once( 'inc/gateways/ebanx.php' );
		include_once( 'inc/gateways/pagarme.php' );
		include_once( 'inc/gateways/pagseguro.php' );
		include_once( 'inc/gateways/paypal.php' );
		include_once( 'class-format.php' );
		include_once( 'class-issue.php' );
		include_once( 'class-backend.php' );
		include_once( 'class-frontend.php' );
		
	}

	/**
	 * Load 
	 */
	function load_plugin_text_domain(){

		load_plugin_textdomain( $this->domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	}

	/**
	 * Get API Credentials
	 * 
	 * @return array
	 */
	function get_credentials( $order_id = 0 ){

		$this->settings = apply_filters( 'nfe_settings', array(
			'oauth_access_token' => get_option('wc_settings_woocommercenfe_access_token'),
			'oauth_access_token_secret' => get_option('wc_settings_woocommercenfe_access_token_secret'),
			'consumer_key' => get_option('wc_settings_woocommercenfe_consumer_key'),
			'consumer_secret' => get_option('wc_settings_woocommercenfe_consumer_secret'),
		), $order_id );

	}

	/**
	 * Add error
	 * 
	 * @return void
	 */
	function add_error( $message ){

		$messages = get_option('woocommercenfe_error_messages');

		if (!$messages) 
			$messages = array();

		if ($messages && count($messages) > 0) { 
			foreach ($messages as $msg){ 
				if ($msg == $message) 
					return false; 
			} 
		}
		
		$messages[] = $message;
		update_option('woocommercenfe_error_messages', $messages);

	}

	/**
	 * Add Success
	 * 
	 * @return void
	 */
	function add_success( $message ){

		$messages = get_option('woocommercenfe_success_messages');
		
		if (!$messages) 
			$messages = array();

		if ($messages && count($messages) > 0) { 
			foreach ($messages as $msg){ 
				if ($msg == $message) 
					return false; 
			} 
		}

		$messages[] = $message;
		update_option('woocommercenfe_success_messages', $messages);

	}

	/**
	 * Issue automatic NFe when change statuses
	 * 
	 * @return void
	 */
	function issue_automatic_invoice( $order_id, $from, $to, $order ) {
		
		// Validate
		$option = apply_filters( 'nfe_issue_automatic', get_option('wc_settings_woocommercenfe_emissao_automatica') );
		if ( !$option ){
			return;
		}

		// Process
		if (
			$to == 'processing' && ($option == 1 || $option == 'yes') ||
			$to == 'completed' && $option == 2
		){

			$nfes = get_post_meta( $order_id, 'nfe', true );

			if ( !empty($nfes) && is_array($nfes) ) {
				foreach ( $nfes as $nfe ) {
					if ( $nfe['status'] == 'aprovado' ) {
						return;
					}
				}
			}

			$nf = new WooCommerceNFeIssue;
			$response = $nf->send( array( $order_id ) );

		}

		do_action( 'nfe_issued_automatic', $order_id, $to, $response );

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
	 * Plugin URL
	 * 
	 * @return string
	 */
	function default_plugin_url( $url ){

		return str_replace('inc/', '', $url);

	}

	/**
	 * Config button
	 * 
	 * @return array
	**/
	public static function plugin_add_settings_link( $links ) {

	    $action_links = array(
	      'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=woocommercenfe_tab' ) . '" aria-label="Visualizar Configurações">Configurações</a>',
			);
			
	    return array_merge( $action_links, $links );
	}
	
	
	/**
	 * Custom function to verify 
	 * if plugin is active
	 * 
	 * @return array
	**/
	public static function wmbr_is_plugin_active( $plugin ) {

		return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );

	}

	/**
	 * Return person type
	 * 
	 * @return string
	**/
	function get_person_type( $type ) {

		if ($type == 1) 
			return 'F';
		else if ($type == 2) 
			return 'J';
		else 
			return '';

	}

	/**
	 * Plugin Extra Checkout Fields for Brazil
	 * 
	 * @return boolean
	 */
	function is_extra_checkout_fields_activated(){

		return self::wmbr_is_plugin_active('woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php');

	}

}

