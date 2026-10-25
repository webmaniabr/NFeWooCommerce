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

require_once __DIR__ . '/inc/class-client-ip.php';

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

// Site-wide HTTP security headers, including HSTS, belong to the host/site configuration.
// Loading a fiscal plugin must not change framing, transport policy or sibling subdomains.

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
		$referer = is_string( $_SERVER['HTTP_REFERER'] ) ? wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) ) : false;
		$site_url = wp_parse_url( site_url() );
		
		if ( ! is_array( $referer ) || ! is_array( $site_url ) || ( $referer['host'] ?? '' ) !== ( $site_url['host'] ?? '' ) ) {
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
	
	if ( ! class_exists( 'WooCommerceNFe_Security' ) ) { return; }
	$private = WooCommerceNFe_Security::private_directory();
	if ( ! $private ) { return; }
	$log_dir = $private . '/';
	
	if ( ! is_dir( $log_dir ) ) {
		return;
	}
	
	$files = glob( $log_dir . 'security-*.log' );
	if ( ! is_array( $files ) ) {
		return;
	}
	$now = time();
	$retention_seconds = WOOCOMMERCE_NFE_LOG_RETENTION_DAYS * 86400;
	
	foreach ( $files as $file ) {
		if ( is_file( $file ) ) {
			if ( $now - filemtime( $file ) >= $retention_seconds ) {
				wp_delete_file( $file );
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
	if ( ! is_string( $path ) || strpos( $path, chr(0) ) !== false ) { return false; }
	$resolved = realpath( $path );
	if ( false === $resolved ) { return false; }
	$uploads = wp_upload_dir();
	foreach ( array( ABSPATH, WP_CONTENT_DIR, $uploads['basedir'] ) as $directory ) {
		$root = realpath( $directory );
		if ( $root && ( $resolved === $root || strpos( $resolved, $root . DIRECTORY_SEPARATOR ) === 0 ) ) { return $resolved; }
	}
	return false;
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
	if ( ! WOOCOMMERCE_NFE_LOG_IP_ADDRESSES ) { return 'hidden'; }
	return WooCommerceNFe_Client_IP::from_request();
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
		'data' => array()
	);
	
	// Keep logs outside the document root, with an explicit audit field allowlist.
	if ( ! class_exists( 'WooCommerceNFe_Security' ) ) { return; }
	$log_entry['data'] = WooCommerceNFe_Security::safe_log_data( $data );
	$log_dir = WooCommerceNFe_Security::private_directory();
	if ( ! $log_dir ) { return; }
	$log_file = $log_dir . '/security-' . gmdate( 'Y-m-d' ) . '.log';
	file_put_contents( $log_file, wp_json_encode( $log_entry ) . PHP_EOL, FILE_APPEND | LOCK_EX );
	WooCommerceNFe_Security::filesystem()->chmod( $log_file, 0600 );
}
