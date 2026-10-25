<?php
/**
 * Security Helper Class for WooCommerce NFe
 * 
 * @package WooCommerceNFe
 * @since 3.4.0.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once __DIR__ . '/class-client-ip.php';

class WooCommerceNFe_Security {
	/** Fiscal temporary files are local, never FTP-backed WordPress uploads. */
	public static function filesystem() {
		static $filesystem;
		if ( ! $filesystem ) {
			if ( ! class_exists( 'WP_Filesystem_Base' ) ) { require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php'; }
			if ( ! class_exists( 'WP_Filesystem_Direct' ) ) { require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php'; }
			$filesystem = new WP_Filesystem_Direct( null );
		}
		return $filesystem;
	}

	/** Allowed markup for carrier/payment settings, including controls omitted by wp_kses_post. */
	public static function form_html_tags() {
		return array(
			'div' => array( 'class' => true ), 'p' => array(),
			'label' => array( 'class' => true ), 'span' => array( 'class' => true ),
			'select' => array( 'name' => true, 'class' => true ),
			'option' => array( 'value' => true, 'selected' => true ),
			'input' => array( 'type' => true, 'name' => true, 'class' => true, 'value' => true, 'style' => true ),
			'button' => array( 'type' => true, 'class' => true ),
		);
	}

	public static function credential_options() {
		return array_map( static function ( $name ) { return 'wc_settings_woocommercenfe_' . $name; }, array( 'consumer_key', 'consumer_secret', 'access_token', 'access_token_secret', 'bearer_access_token' ) );
	}

	/** Only a verified order editor may override fiscal fields from POST. */
	public static function can_edit_invoice( $order_id ) {
		$nonce = isset( $_POST['nfe_meta_box_nonce'] ) && is_string( $_POST['nfe_meta_box_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nfe_meta_box_nonce'] ) ) : '';
		return current_user_can( 'manage_woocommerce' ) && current_user_can( 'edit_shop_order', $order_id ) &&
			is_string( $nonce ) && wp_verify_nonce( $nonce, 'save_nfe_meta_box_' . $order_id );
	}

	public static function invoice_input( $order_id ) {
		$nonce = isset( $_POST['nfe_meta_box_nonce'] ) && is_string( $_POST['nfe_meta_box_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nfe_meta_box_nonce'] ) ) : '';
		if ( ! current_user_can( 'manage_woocommerce' ) || ! current_user_can( 'edit_shop_order', $order_id ) || ! wp_verify_nonce( $nonce, 'save_nfe_meta_box_' . $order_id ) ) { return array(); }
		return wp_unslash( $_POST );
	}

	/** Read classic checkout input only after verifying either supported WooCommerce nonce name. */
	public static function checkout_input() {
		$key = isset( $_POST['woocommerce-process-checkout-nonce'] ) ? 'woocommerce-process-checkout-nonce' : '_wpnonce';
		$nonce = isset( $_POST[$key] ) && is_string( $_POST[$key] ) ? sanitize_text_field( wp_unslash( $_POST[$key] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'woocommerce-process_checkout' ) ) { return false; }
		$result = array();
		foreach ( array( 'billing_persontype', 'billing_cpf', 'billing_cnpj', 'billing_company', 'billing_neighborhood' ) as $field ) {
			$result[$field] = isset( $_POST[$field] ) && is_string( $_POST[$field] ) ? sanitize_text_field( wp_unslash( $_POST[$field] ) ) : '';
		}
		return $result;
	}

	/** Authenticated order editor input, covering classic storage, HPOS and this plugin's fiscal box. */
	public static function admin_order_input( $order_id ) {
		if ( ! current_user_can( 'edit_shop_order', $order_id ) ) { return false; }
		$nonce = isset( $_POST['woocommerce_meta_nonce'] ) && is_string( $_POST['woocommerce_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ) : '';
		$hpos = isset( $_POST['_wpnonce'] ) && is_string( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		$fiscal = isset( $_POST['nfe_meta_box_nonce'] ) && is_string( $_POST['nfe_meta_box_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nfe_meta_box_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'woocommerce_save_data' ) && ! wp_verify_nonce( $hpos, 'update-order_' . $order_id ) && ! wp_verify_nonce( $fiscal, 'save_nfe_meta_box_' . $order_id ) ) { return false; }
		return wp_unslash( $_POST );
	}

	/** Independent of the customer-visible WooCommerce order key. */
	public static function callback_token( $order, $model ) {
		// home_url() changes with is_ssl() and locale/domain filters. Use a stable site identity.
		$home = get_option( 'home' );
		$home = is_string( $home ) && $home !== '' ? set_url_scheme( $home, 'https' ) : home_url( '', 'https' );
		return self::callback_hash( $order, $model, $home );
	}

	private static function callback_hash( $order, $model, $home ) {
		return hash_hmac( 'sha256', 'wmbr-callback-v1|' . $home . '|' . $model . '|' . $order->get_id() . '|' . $order->get_order_key(), wp_salt( 'auth' ) );
	}

	public static function verify_callback_token( $order, $model, $token ) {
		if ( ! is_string( $token ) || ! preg_match( '/^[a-f0-9]{64}$/D', $token ) ) { return false; }
		if ( hash_equals( self::callback_token( $order, $model ), $token ) ) { return true; }
		// Preserve existing signed URLs generated before the site identity was made protocol-independent.
		foreach ( array_unique( array( home_url(), home_url( '', 'http' ), home_url( '', 'https' ) ) ) as $home ) {
			if ( hash_equals( self::callback_hash( $order, $model, $home ), $token ) ) { return true; }
		}
		return false;
	}

	public static function callback_url( $order, $model ) {
		return add_query_arg( array( 'order_id' => $order->get_id(), 'token' => self::callback_token( $order, $model ) ), home_url( '/wc-api/' . $model . '_callback', 'https' ) );
	}

	public static function sanitize_invoice( $invoice ) {
		if ( ! is_array( $invoice ) ) { return array(); }
		foreach ( array( 'url_xml', 'url_pdf', 'pdf_rps', 'url_danfe', 'url_danfe_simplificada', 'url_danfe_etiqueta' ) as $key ) {
			if ( isset( $invoice[$key] ) && ! WooCommerceNFe_HTTP::valid_url( $invoice[$key], 'document' ) ) { $invoice[$key] = ''; }
		}
		return $invoice;
	}

	/** add_option is atomic because option_name has a unique database index. */
	public static function acquire_lock( $order_id ) {
		global $wpdb;
		$name = 'wmbr_issue_lock_' . (int) $order_id;
		$value = ( time() + 900 ) . ':' . wp_generate_password( 32, false, false );
		if ( add_option( $name, $value, '', false ) ) { return $value; }
		$old = get_option( $name );
		if ( ! is_string( $old ) || (int) $old >= time() ) { return false; }
		// Compare-and-delete cannot remove a lock another request just acquired.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Compare-and-delete must be atomic; the options cache is explicitly invalidated below.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $name, $old ) );
		wp_cache_delete( $name, 'options' );
		return add_option( $name, $value, '', false ) ? $value : false;
	}

	public static function release_lock( $order_id, $value ) {
		global $wpdb;
		$name = 'wmbr_issue_lock_' . (int) $order_id;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Compare-and-delete must be atomic; the options cache is explicitly invalidated below.
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s", $name, $value ) );
		wp_cache_delete( $name, 'options' );
	}

	/** Never use a web-accessible upload/plugin directory for fiscal documents. */
	public static function private_directory() {
		$base = realpath( sys_get_temp_dir() );
		$root = realpath( ABSPATH );
		$document_root = isset( $_SERVER['DOCUMENT_ROOT'] ) && is_string( $_SERVER['DOCUMENT_ROOT'] ) ? realpath( sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) ) : false;
		if ( ! $base || ( $root && strpos( $base . '/', $root . '/' ) === 0 ) || ( $document_root && strpos( $base . '/', $document_root . '/' ) === 0 ) ) { return false; }
		$path = $base . '/wmbr-' . substr( hash_hmac( 'sha256', ABSPATH, wp_salt( 'auth' ) ), 0, 24 );
		if ( is_link( $path ) ) { return false; }
		$filesystem = self::filesystem();
		if ( ! is_dir( $path ) && ! $filesystem->mkdir( $path, 0700 ) && ! is_dir( $path ) ) { return false; }
		// On a shared temporary directory another local account could pre-create this folder
		// (e.g. after tmp cleanup) to read or replace DANFEs/logs: only use a folder we own.
		if ( function_exists( 'posix_geteuid' ) ) {
			$euid = posix_geteuid();
			if ( 0 !== $euid && fileowner( $path ) !== $euid ) { return false; }
		}
		if ( ! $filesystem->chmod( $path, 0700 ) ) { return false; }
		return $filesystem->is_writable( $path ) ? $path : false;
	}

	/** Preserve legacy files privately instead of leaving them publicly downloadable. */
	public static function migrate() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
		delete_option( 'old_credentials' );
		foreach ( self::credential_options() as $key ) {
			if ( function_exists( 'wp_set_option_autoload' ) ) { wp_set_option_autoload( $key, false ); }
		}
		if ( get_option( 'wmbr_private_files_migrated' ) ) { return; }
		$private = self::private_directory();
		if ( ! $private ) { add_action( 'admin_notices', array( __CLASS__, 'migration_notice' ) ); return; }
		$uploads = wp_upload_dir();
		$complete = true;
		foreach ( array( dirname( __DIR__ ) . '/danfes_pdf/*.pdf', $uploads['basedir'] . '/woocommerce-nfe-logs/*.log' ) as $pattern ) {
			foreach ( (array) glob( $pattern ) as $file ) {
				if ( is_link( $file ) || ! is_file( $file ) ) { continue; }
				$target = $private . '/legacy-' . wp_generate_password( 24, false, false ) . '-' . basename( $file );
				// Keep the migration atomic; WP_Filesystem::move may copy and report success even if deletion fails.
				// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename -- Never mark a publicly readable legacy source migrated unless the move succeeds.
				if ( ! @rename( $file, $target ) || is_file( $file ) || ! is_file( $target ) || ! self::filesystem()->chmod( $target, 0600 ) ) { $complete = false; }
			}
		}
		if ( $complete ) { update_option( 'wmbr_private_files_migrated', 1, false ); }
		else { add_action( 'admin_notices', array( __CLASS__, 'migration_notice' ) ); }
	}

	public static function migration_notice() {
		echo '<div class="notice notice-error"><p>Webmania: não foi possível proteger todos os arquivos fiscais antigos. Solicite à hospedagem uma pasta temporária gravável fora da raiz pública e restrinja o acesso às pastas danfes_pdf e woocommerce-nfe-logs até concluir a migração.</p></div>';
	}

	/** Log only structured, non-sensitive audit fields. Never raw payloads or errors. */
	public static function safe_log_data( $data ) {
		$result = array();
		foreach ( array( 'order_id', 'user_id', 'http_status', 'model', 'event', 'result' ) as $key ) {
			if ( isset( $data[$key] ) && is_scalar( $data[$key] ) ) { $result[$key] = sanitize_key( (string) $data[$key] ); }
		}
		return $result;
	}

	/**
	 * Sanitize and validate order ID
	 * 
	 * @param mixed $order_id
	 * @return int|false
	 */
	public static function validate_order_id( $order_id ) {
		if ( ! is_scalar( $order_id ) || ! ctype_digit( (string) $order_id ) ) { return false; }
		$order_id = (int) $order_id;
		
		if ( $order_id <= 0 ) {
			return false;
		}
		
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}
		
		return $order_id;
	}

	/**
	 * Validate and sanitize CPF
	 * 
	 * @param string $cpf
	 * @return string|false
	 */
	public static function sanitize_cpf( $cpf ) {
		$cpf = preg_replace( '/[^0-9]/', '', $cpf );
		
		if ( strlen( $cpf ) !== 11 ) {
			return false;
		}
		
		if ( ! WooCommerceNFeFormat::is_cpf( $cpf ) ) {
			return false;
		}
		
		return $cpf;
	}

	/**
	 * Validate and sanitize CNPJ
	 * 
	 * @param string $cnpj
	 * @return string|false
	 */
	public static function sanitize_cnpj( $cnpj ) {
		$cnpj = WooCommerceNFeFormat::normalize_document( $cnpj );
		
		if ( strlen( $cnpj ) !== 14 ) {
			return false;
		}
		
		if ( ! WooCommerceNFeFormat::is_cnpj( $cnpj ) ) {
			return false;
		}
		
		return $cnpj;
	}

	/**
	 * Sanitize monetary value
	 * 
	 * @param mixed $value
	 * @return float
	 */
	public static function sanitize_money( $value ) {
		$value = str_replace( array( 'R$', '€', '$', ' ' ), '', $value );
		$value = str_replace( ',', '.', $value );
		return floatval( $value );
	}

	/**
	 * Validate URL
	 * 
	 * @param string $url
	 * @return string|false
	 */
	public static function validate_url( $url ) {
		$url = esc_url_raw( $url );
		
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}
		
		// Only allow http and https protocols
		$parsed = wp_parse_url( $url );
		if ( ! is_array( $parsed ) || ! in_array( $parsed['scheme'] ?? '', array( 'http', 'https' ), true ) ) {
			return false;
		}
		
		return $url;
	}

	/**
	 * Create secure nonce for AJAX requests
	 * 
	 * @param string $action
	 * @return string
	 */
	public static function create_ajax_nonce( $action ) {
		return wp_create_nonce( 'woocommerce_nfe_' . $action . '_' . get_current_user_id() );
	}

	/**
	 * Verify AJAX nonce
	 * 
	 * @param string $action
	 * @param string $nonce_field
	 * @return bool
	 */
	public static function verify_ajax_nonce( $action, $nonce_field = 'security' ) {
		return check_ajax_referer( 'woocommerce_nfe_' . $action . '_' . get_current_user_id(), $nonce_field, false );
	}

	/**
	 * Sanitize array recursively
	 * 
	 * @param array $array
	 * @return array
	 */
	public static function sanitize_array( $array ) {
		if ( ! is_array( $array ) ) {
			return sanitize_text_field( $array );
		}
		
		foreach ( $array as $key => $value ) {
			if ( is_array( $value ) ) {
				$array[ $key ] = self::sanitize_array( $value );
			} else {
				$array[ $key ] = sanitize_text_field( $value );
			}
		}
		
		return $array;
	}

	/**
	 * Validate date format
	 * 
	 * @param string $date
	 * @param string $format
	 * @return bool
	 */
	public static function validate_date( $date, $format = 'd/m/Y' ) {
		$d = DateTime::createFromFormat( $format, $date );
		return $d && $d->format( $format ) === $date;
	}

	/**
	 * Get safe user IP address
	 * 
	 * @return string
	 */
	public static function get_user_ip() {
		return WooCommerceNFe_Client_IP::from_request();
	}

	/**
	 * Rate limiting for API calls
	 * 
	 * @param string $action
	 * @param int $limit
	 * @param int $window (in seconds)
	 * @return bool
	 */
	public static function check_rate_limit( $action, $limit = 10, $window = 60 ) {
		$user_id = get_current_user_id();
		$ip = self::get_user_ip();
		$key = 'nfe_rate_limit_' . $action . '_' . $user_id . '_' . $ip;
		
		$attempts = get_transient( $key );
		
		if ( $attempts === false ) {
			set_transient( $key, 1, $window );
			return true;
		}
		
		if ( $attempts >= $limit ) {
			return false;
		}
		
		set_transient( $key, $attempts + 1, $window );
		return true;
	}

	/**
	 * Sanitize file name
	 * 
	 * @param string $filename
	 * @return string
	 */
	public static function sanitize_filename( $filename ) {
		$filename = sanitize_file_name( $filename );
		
		// Remove any remaining special characters
		$filename = preg_replace( '/[^a-zA-Z0-9._-]/', '', $filename );
		
		// Ensure it has a safe extension
		$allowed_extensions = array( 'pdf', 'xml', 'txt', 'csv' );
		$extension = pathinfo( $filename, PATHINFO_EXTENSION );
		
		if ( ! in_array( strtolower( $extension ), $allowed_extensions, true ) ) {
			return false;
		}
		
		return $filename;
	}

	/**
	 * Generate secure random token
	 * 
	 * @param int $length
	 * @return string
	 */
	public static function generate_token( $length = 32 ) {
		if ( function_exists( 'wp_generate_password' ) ) {
			return wp_generate_password( $length, false );
		}
		
		return bin2hex( random_bytes( $length / 2 ) );
	}

	/**
	 * Escape output for JavaScript
	 * 
	 * @param mixed $data
	 * @return string
	 */
	public static function escape_js( $data ) {
		if ( is_array( $data ) || is_object( $data ) ) {
			return wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
		}
		
		return esc_js( $data );
	}

	/**
	 * Validate webhook signature
	 * 
	 * @param string $payload
	 * @param string $signature
	 * @param string $secret
	 * @return bool
	 */
	public static function validate_webhook_signature( $payload, $signature, $secret ) {
		if ( ! is_string( $payload ) || ! is_string( $secret ) || $secret === '' || ! is_string( $signature ) || ! preg_match( '/^[a-f0-9]{64}$/D', $signature ) ) { return false; }
		$calculated_signature = hash_hmac( 'sha256', $payload, $secret );
		return hash_equals( $calculated_signature, $signature );
	}

	/**
	 * Log security events
	 * 
	 * @param string $event_type
	 * @param array $data
	 * @return void
	 */
	public static function log_security_event( $event_type, $data = array() ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}
		
		$log_entry = array(
			'timestamp' => current_time( 'mysql' ),
			'event' => sanitize_text_field( $event_type ),
			'user_id' => get_current_user_id(),
			'data' => self::safe_log_data( $data )
		);
		
		// Log to WordPress debug log
		if ( function_exists( 'woocommerce_nfe_security_log' ) ) { woocommerce_nfe_security_log( $event_type, $log_entry['data'] ); }
		
		// Optionally store in database for audit trail
		if ( apply_filters( 'woocommerce_nfe_enable_security_audit', false ) ) {
			add_option( 'nfe_security_log_' . time() . '_' . wp_rand(), $log_entry, '', 'no' );
		}
	}

	/**
	 * Validate API credentials
	 * 
	 * @param array $credentials
	 * @return bool
	 */
	public static function validate_api_credentials( $credentials ) {
		$required_fields = array(
			'consumer_key',
			'consumer_secret',
			'oauth_access_token',
			'oauth_access_token_secret'
		);
		
		foreach ( $required_fields as $field ) {
			if ( empty( $credentials[ $field ] ) ) {
				return false;
			}
			
			// Basic length validation
			if ( strlen( $credentials[ $field ] ) < 10 ) {
				return false;
			}
		}
		
		return true;
	}

	/**
	 * Add security headers
	 * 
	 * @return void
	 */
	public static function add_security_headers() {
		if ( ! headers_sent() ) {
			header( 'X-Content-Type-Options: nosniff' );
			header( 'X-Frame-Options: SAMEORIGIN' );
			header( 'X-XSS-Protection: 1; mode=block' );
			header( 'Referrer-Policy: strict-origin-when-cross-origin' );
			
			if ( is_ssl() ) {
				header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
			}
		}
	}

	/**
	 * Validate AJAX request
	 * 
	 * @param string $action
	 * @param string $capability
	 * @return bool
	 */
	public static function validate_ajax_request( $action, $capability = 'manage_woocommerce' ) {
		// Check if it's an AJAX request
		if ( ! wp_doing_ajax() ) {
			return false;
		}
		
		// Verify nonce
		if ( ! self::verify_ajax_nonce( $action ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'nota-fiscal-eletronica-woocommerce' ) ), 403 );
			return false;
		}
		
		// Check user capability
		if ( ! current_user_can( $capability ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'nota-fiscal-eletronica-woocommerce' ) ), 403 );
			return false;
		}
		
		// Rate limiting
		if ( ! self::check_rate_limit( 'ajax_' . $action, 30, 60 ) ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please try again later.', 'nota-fiscal-eletronica-woocommerce' ) ), 429 );
			return false;
		}
		
		return true;
	}
}
