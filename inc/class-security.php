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

class WooCommerceNFe_Security {

	/**
	 * Sanitize and validate order ID
	 * 
	 * @param mixed $order_id
	 * @return int|false
	 */
	public static function validate_order_id( $order_id ) {
		$order_id = absint( $order_id );
		
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
		$cnpj = preg_replace( '/[^0-9]/', '', $cnpj );
		
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
		$parsed = parse_url( $url );
		if ( ! in_array( $parsed['scheme'], array( 'http', 'https' ), true ) ) {
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
		// Check for CloudFlare IP
		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
		// Check for proxy
		elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
			$ip = trim( $ips[0] );
		}
		// Standard IP
		elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		else {
			$ip = 'unknown';
		}
		
		// Validate IP address
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return 'invalid';
		}
		
		return $ip;
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
			'ip' => self::get_user_ip(),
			'data' => self::sanitize_array( $data )
		);
		
		// Log to WordPress debug log
		error_log( 'NFe Security Event: ' . wp_json_encode( $log_entry ) );
		
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
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'woocommerce' ) ), 403 );
			return false;
		}
		
		// Check user capability
		if ( ! current_user_can( $capability ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'woocommerce' ) ), 403 );
			return false;
		}
		
		// Rate limiting
		if ( ! self::check_rate_limit( 'ajax_' . $action, 30, 60 ) ) {
			wp_send_json_error( array( 'message' => __( 'Too many requests. Please try again later.', 'woocommerce' ) ), 429 );
			return false;
		}
		
		return true;
	}
}
