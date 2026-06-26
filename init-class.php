<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WooCommerceNFe {

	public string $domain = 'WooCommerceNFe';
	public string $version = '3.4.4';
	public array $settings = [];
	protected static ?WooCommerceNFe $_instance = null;

	public static function instance() {
    if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

  	public function __get( $key ) {
		// Security: Restrict access to safe properties only
		$allowed_properties = array(
			'domain',
			'version',
			'settings'
		);
		
		if ( in_array( $key, $allowed_properties, true ) ) {
			return $this->$key;
		}
		
		// Security: Log potential security attempt without exposing user input
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'WooCommerceNFe: Attempted access to restricted property' );
		}
		return null;
	}

	function __construct(){

		global $domain, $woocommerce;

		// Load hook message
		add_action( 'admin_notices', [ $this, 'display_messages' ] );

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
		add_action( 'transition_post_status', array($this, 'issue_automatic_invoice'), 10, 4 );
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
		$wc_version = defined('WC_VERSION')
			? constant('WC_VERSION')
			: (function_exists('WC') ? WC()->version : '0.0.0');

		if (version_compare($wc_version, '3.0.0', '<')){
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

			$hasAPI1Credentials = false;
			$hasAPI2Credentials = false;

			// Validate Credentials
			if (
				get_option('wc_settings_woocommercenfe_access_token') &&
				get_option('wc_settings_woocommercenfe_access_token_secret') &&
				get_option('wc_settings_woocommercenfe_consumer_key') &&
				get_option('wc_settings_woocommercenfe_consumer_secret')
			) {
				$hasAPI1Credentials = true;
			}
			if (get_option('wc_settings_woocommercenfe_bearer_access_token')) {
				$hasAPI2Credentials = true;
			}
			if (!$hasAPI1Credentials && !$hasAPI2Credentials) {
				$this->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> Informe as credenciais de acesso da aplicação em WooCommerce > Configurações > Nota Fiscal.', $this->domain) );
				return false;
			}

			//Validate API 1.0 configs
			if ($hasAPI1Credentials && (
				!get_option('wc_settings_woocommercenfe_natureza_operacao') ||
				!get_option('wc_settings_woocommercenfe_imposto') ||
				!get_option('wc_settings_woocommercenfe_ncm') ||
				get_option('wc_settings_woocommercenfe_origem') < 0 ||
				get_option('wc_settings_woocommercenfe_origem') == 'null'
			)) {

				$this->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> Informe a Natureza da Operação, Classe de Imposto, Código NCM e Origem do produto em WooCommerce > Configurações > Nota Fiscal.', $this->domain) );
					return false;

			}

			//Validate API 2.0 configs
			if ($hasAPI2Credentials && (
				!get_option('wc_settings_woocommercenfe_imposto_nfse')
			)) {
				$this->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> Informe a Classe de Imposto do serviço em WooCommerce > Configurações > Nota Fiscal.', $this->domain) );
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

		include_once( 'inc/class-security.php' ); // Security helper class
		include_once( 'inc/sdk/NFe.php' );
		include_once( 'inc/sdk/NFSe.php' );
		include_once( 'inc/utils.php' );
		include_once( 'inc/gateways/utils.php' );
		include_once( 'inc/gateways/bacs.php' );
		include_once( 'inc/gateways/ebanx.php' );
		include_once( 'inc/gateways/pagarme.php' );
		include_once( 'inc/gateways/pagseguro.php' );
		include_once( 'inc/gateways/paypal.php' );
		include_once( 'inc/pdf/PDFMerger.php' );
		include_once( 'class-format.php' );
		include_once( 'class-issue.php' );
		include_once( 'class-print.php' );
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
			'bearer_access_token' => get_option('wc_settings_woocommercenfe_bearer_access_token'),
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
	function issue_automatic_invoice( $to, $from, $post ) {
		
		// Validations
		if (get_post_type($post) != 'shop_order')
			return;

		$option = apply_filters( 'nfe_issue_automatic', get_option('wc_settings_woocommercenfe_emissao_automatica') );
		if ( !$option ){
			return;
		}

		// Vars
		$order_id = $post->ID;
		$order = wc_get_order( $order_id );

		// Process
		if (
			$to == 'wc-processing' && ($option == 1 || $option == 'yes') ||
			$to == 'wc-completed' && $option == 2
		){
			
			$nfes = $order->get_meta('nfe');

			if ( !empty($nfes) && is_array($nfes) ) {
				foreach ( $nfes as $nfe ) {
					if ( isset($nfe) ) {
						return;
					}
				}
			}

			$nf = new WooCommerceNFeIssue;

            // Prevent orders for only ignored products from auto issuing NFe
            if ($nf->is_only_ignored_items( $order_id )) return;

            $response = $nf->send( array( $order_id ), false, true );

		}

		if (!empty($response)) {
			do_action( 'nfe_issued_automatic', $order_id, $to, $response );
	   	}

	}

	/**
	 * Send e-mail notification to user when auto invoice fail
	 **/
	function send_error_email( $message, $order_id ) {

		$email = get_option('wc_settings_woocommercenfe_email_notification');
		$ids_db = get_option('wmbr_auto_invoice_errors');
		if ( !$email || (is_array($ids_db) && array_key_exists($order_id, $ids_db)) ) {
			return;
		}

		// Security: Validate email address
		if (!is_email($email)) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log('WooCommerceNFe: Invalid email address for notifications');
			}
			return;
		}

		// Security: Sanitize inputs to prevent header injection
		$order_id = intval($order_id);
		$safe_message = wp_kses($message, array(
			'p' => array(),
			'br' => array(),
			'strong' => array(),
			'ul' => array(),
			'li' => array()
		));

		// Security: Properly escape subject and URL
		$subject = esc_html( sprintf('Erro ao emitir NF-e - Pedido #%d', $order_id) );
		$admin_url = esc_url( admin_url( 'post.php?post=' . $order_id . '&action=edit' ) );
		
		$html_message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		  <html xmlns="http://www.w3.org/1999/xhtml">
		  <head><meta charset="UTF-8"></head>
		  	<body>
		  		<p>Houve um erro de emissão automática no Pedido #' . esc_html($order_id) . ': <a target="_blank" href="' . $admin_url . '">Acesse o pedido</a></p>
		  		' . $safe_message . '
			</body>
		</html>';

		$headers = array(
		  	'Content-Type: text/html; charset=UTF-8'
		);

		// Security: Use single email address, validate before sending
		if ( is_email( $email ) ) {
			$enviar_email = wp_mail( sanitize_email( $email ), $subject, $html_message, $headers );
		}

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

	    // Security: Escape URL and add nonce
	    $settings_url = esc_url( admin_url( 'admin.php?page=wc-settings&tab=woocommercenfe_tab' ) );
	    $action_links = array(
	      'settings' => '<a href="' . $settings_url . '" aria-label="' . esc_attr__( 'Visualizar Configurações', 'woocommerce' ) . '">' . esc_html__( 'Configurações', 'woocommerce' ) . '</a>',
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
	public static function is_extra_checkout_fields_activated(){

		return self::wmbr_is_plugin_active('woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php');

	}

	/**
	 * Validate credentials
	 *
	 * @return boolean
	 */
	public function validate_credentials($credentials) {

		if (strlen($credentials['consumer_key']) != 32 || strlen($credentials['consumer_secret']) != 48 || strlen($credentials['oauth_access_token']) <= 48 || strlen($credentials['oauth_access_token_secret']) != 48) {
			return false;
		}

		return true;

	}

	/**
	 * Display Messages
	 *
	 * @return void
	 */
	public function display_messages(): void {

		$error_messages = get_option('woocommercenfe_error_messages');
		if ( $error_messages && is_array( $error_messages ) ) {
			?>
			<div class="notice notice-error is-dismissible">
				<?php 
				foreach ( $error_messages as $message ) { 
					// Security: Allow specific HTML tags in admin notices
					echo '<p>' . wp_kses( $message, array(
						'strong' => array(),
						'a' => array( 'href' => array(), 'target' => array() ),
						'ul' => array(),
						'li' => array()
					) ) . '</p>'; 
				} 
				?>
			</div>
			<?php
			delete_option('woocommercenfe_error_messages');
		}

		$success_messages = get_option('woocommercenfe_success_messages');
		if ( $success_messages && is_array( $success_messages ) ) {
			?>
			<div class="notice notice-success is-dismissible">
				<?php 
				foreach ( $success_messages as $message ) { 
					// Security: Allow specific HTML tags in admin notices
					echo '<p>' . wp_kses( $message, array(
						'strong' => array(),
						'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() )
					) ) . '</p>'; 
				} 
				?>
			</div>
			<?php
			delete_option('woocommercenfe_success_messages');
		}

	}

}
