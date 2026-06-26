<?php
/**
 * Security Configuration for WooCommerce NFe
 * 
 * This file contains security settings and constants for the plugin
 * 
 * @package WooCommerceNFe
 * @since 3.4.0.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Security Constants
define( 'WOOCOMMERCE_NFE_SECURITY_VERSION', '1.0.0' );

// Rate Limiting Settings
define( 'WOOCOMMERCE_NFE_RATE_LIMIT_ENABLED', true );
define( 'WOOCOMMERCE_NFE_RATE_LIMIT_REQUESTS', 30 ); // Maximum requests
define( 'WOOCOMMERCE_NFE_RATE_LIMIT_WINDOW', 60 ); // Time window in seconds

// Session Security
define( 'WOOCOMMERCE_NFE_SESSION_TIMEOUT', 3600 ); // 1 hour
define( 'WOOCOMMERCE_NFE_SESSION_REGENERATE', 1800 ); // Regenerate session ID every 30 minutes

// File Upload Security
define( 'WOOCOMMERCE_NFE_MAX_FILE_SIZE', 104857600 ); // 100MB in bytes
define( 'WOOCOMMERCE_NFE_ALLOWED_FILE_TYPES', array( 'pdf', 'xml', 'txt', 'csv' ) );

// API Security
define( 'WOOCOMMERCE_NFE_API_TIMEOUT', 30 ); // API timeout in seconds
define( 'WOOCOMMERCE_NFE_API_MAX_RETRIES', 3 ); // Maximum API retry attempts

// Logging Security
define( 'WOOCOMMERCE_NFE_LOG_SENSITIVE_DATA', false ); // Don't log sensitive data
define( 'WOOCOMMERCE_NFE_LOG_IP_ADDRESSES', false ); // Don't log IP addresses
define( 'WOOCOMMERCE_NFE_LOG_RETENTION_DAYS', 30 ); // Delete logs after 30 days

// CSRF Protection
define( 'WOOCOMMERCE_NFE_CSRF_TOKEN_LIFETIME', 7200 ); // 2 hours

// Password Requirements (for any future password fields)
define( 'WOOCOMMERCE_NFE_MIN_PASSWORD_LENGTH', 12 );
define( 'WOOCOMMERCE_NFE_REQUIRE_SPECIAL_CHARS', true );
define( 'WOOCOMMERCE_NFE_REQUIRE_NUMBERS', true );
define( 'WOOCOMMERCE_NFE_REQUIRE_UPPERCASE', true );

// Security Headers (if not set by server)
if ( ! headers_sent() ) {
	// Prevent MIME sniffing
	header( 'X-Content-Type-Options: nosniff' );
	
	// Prevent clickjacking
	header( 'X-Frame-Options: SAMEORIGIN' );
	
	// Enable XSS protection
	header( 'X-XSS-Protection: 1; mode=block' );
	
	// Referrer Policy
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	
	// Remove PHP version
	header_remove( 'X-Powered-By' );
	
	// HSTS (only on HTTPS)
	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
	}
}

/**
 * Security functions
 */

/**
 * Check if security features are enabled
 * 
 * @return bool
 */
function woocommerce_nfe_is_security_enabled() {
	return apply_filters( 'woocommerce_nfe_security_enabled', true );
}

/**
 * Get security configuration
 * 
 * @param string $key Configuration key
 * @param mixed $default Default value
 * @return mixed
 */
function woocommerce_nfe_get_security_config( $key, $default = null ) {
	$config = array(
		'rate_limiting' => WOOCOMMERCE_NFE_RATE_LIMIT_ENABLED,
		'rate_limit_requests' => WOOCOMMERCE_NFE_RATE_LIMIT_REQUESTS,
		'rate_limit_window' => WOOCOMMERCE_NFE_RATE_LIMIT_WINDOW,
		'session_timeout' => WOOCOMMERCE_NFE_SESSION_TIMEOUT,
		'max_file_size' => WOOCOMMERCE_NFE_MAX_FILE_SIZE,
		'allowed_file_types' => WOOCOMMERCE_NFE_ALLOWED_FILE_TYPES,
		'api_timeout' => WOOCOMMERCE_NFE_API_TIMEOUT,
		'csrf_token_lifetime' => WOOCOMMERCE_NFE_CSRF_TOKEN_LIFETIME,
	);
	
	return isset( $config[ $key ] ) ? $config[ $key ] : $default;
}

/**
 * Validate request origin
 * 
 * @return bool
 */
function woocommerce_nfe_validate_request_origin() {
	// Check if request is from same origin
	if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
		$referer = parse_url( $_SERVER['HTTP_REFERER'] );
		$site_url = parse_url( site_url() );
		
		if ( $referer['host'] !== $site_url['host'] ) {
			return false;
		}
	}
	
	return true;
}

/**
 * Clean old log files
 * 
 * @return void
 */
function woocommerce_nfe_clean_old_logs() {
	if ( ! WOOCOMMERCE_NFE_LOG_RETENTION_DAYS ) {
		return;
	}
	
	$upload_dir = wp_upload_dir();
	$log_dir = $upload_dir['basedir'] . '/woocommerce-nfe-logs/';
	
	if ( ! is_dir( $log_dir ) ) {
		return;
	}
	
	$files = glob( $log_dir . '*.log' );
	$now = time();
	$retention_seconds = WOOCOMMERCE_NFE_LOG_RETENTION_DAYS * 86400;
	
	foreach ( $files as $file ) {
		if ( is_file( $file ) ) {
			if ( $now - filemtime( $file ) >= $retention_seconds ) {
				@unlink( $file );
			}
		}
	}
}

// Schedule log cleanup
if ( ! wp_next_scheduled( 'woocommerce_nfe_clean_logs' ) ) {
	wp_schedule_event( time(), 'daily', 'woocommerce_nfe_clean_logs' );
}
add_action( 'woocommerce_nfe_clean_logs', 'woocommerce_nfe_clean_old_logs' );

/**
 * Validate input length
 * 
 * @param string $input
 * @param int $max_length
 * @return bool
 */
function woocommerce_nfe_validate_input_length( $input, $max_length = 1000 ) {
	return strlen( $input ) <= $max_length;
}

/**
 * Sanitize file path
 * 
 * @param string $path
 * @return string|false
 */
function woocommerce_nfe_sanitize_file_path( $path ) {
	// Remove any directory traversal attempts
	$path = str_replace( array( '../', '..\\' ), '', $path );
	
	// Remove null bytes
	$path = str_replace( chr(0), '', $path );
	
	// Normalize slashes
	$path = str_replace( '\\', '/', $path );
	
	// Remove multiple slashes
	$path = preg_replace( '#/+#', '/', $path );
	
	// Check if path is within allowed directories
	$upload_dir = wp_upload_dir();
	$allowed_dirs = array(
		ABSPATH,
		WP_CONTENT_DIR,
		$upload_dir['basedir']
	);
	
	$is_allowed = false;
	foreach ( $allowed_dirs as $dir ) {
		if ( strpos( $path, $dir ) === 0 ) {
			$is_allowed = true;
			break;
		}
	}
	
	if ( ! $is_allowed ) {
		return false;
	}
	
	return $path;
}

/**
 * Create secure hash
 * 
 * @param string $data
 * @param string $key Optional key for HMAC
 * @return string
 */
function woocommerce_nfe_create_hash( $data, $key = '' ) {
	if ( empty( $key ) ) {
		$key = wp_salt( 'auth' );
	}
	
	return hash_hmac( 'sha256', $data, $key );
}

/**
 * Verify secure hash
 * 
 * @param string $data
 * @param string $hash
 * @param string $key Optional key for HMAC
 * @return bool
 */
function woocommerce_nfe_verify_hash( $data, $hash, $key = '' ) {
	$expected = woocommerce_nfe_create_hash( $data, $key );
	return hash_equals( $expected, $hash );
}

/**
 * Get client IP address safely
 * 
 * @return string
 */
function woocommerce_nfe_get_client_ip() {
	// Don't log IPs if disabled
	if ( ! WOOCOMMERCE_NFE_LOG_IP_ADDRESSES ) {
		return 'hidden';
	}
	
	// Check for CloudFlare IP
	if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
		return sanitize_text_field( $_SERVER['HTTP_CF_CONNECTING_IP'] );
	}
	
	// Check for proxy
	if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
		return sanitize_text_field( trim( $ips[0] ) );
	}
	
	// Standard IP
	if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		return sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
	}
	
	return 'unknown';
}

/**
 * Security audit log
 * 
 * @param string $event_type
 * @param array $data
 * @return void
 */
function woocommerce_nfe_security_log( $event_type, $data = array() ) {
	if ( ! apply_filters( 'woocommerce_nfe_enable_security_logging', false ) ) {
		return;
	}
	
	$log_entry = array(
		'timestamp' => current_time( 'mysql' ),
		'event' => sanitize_text_field( $event_type ),
		'user_id' => get_current_user_id(),
		'data' => array_map( 'sanitize_text_field', $data )
	);
	
	// Don't log sensitive data
	if ( WOOCOMMERCE_NFE_LOG_SENSITIVE_DATA === false ) {
		unset( $log_entry['data']['password'] );
		unset( $log_entry['data']['token'] );
		unset( $log_entry['data']['secret'] );
		unset( $log_entry['data']['api_key'] );
	}
	
	// Log to file
	$upload_dir = wp_upload_dir();
	$log_dir = $upload_dir['basedir'] . '/woocommerce-nfe-logs/';
	
	if ( ! is_dir( $log_dir ) ) {
		wp_mkdir_p( $log_dir );
		
		// Create .htaccess to protect log files
		$htaccess_content = "Order Deny,Allow\nDeny from all";
		@file_put_contents( $log_dir . '.htaccess', $htaccess_content );
	}
	
	$log_file = $log_dir . 'security-' . date( 'Y-m-d' ) . '.log';
	$log_message = date( 'Y-m-d H:i:s' ) . ' - ' . wp_json_encode( $log_entry ) . PHP_EOL;
	
	@file_put_contents( $log_file, $log_message, FILE_APPEND | LOCK_EX );
}
