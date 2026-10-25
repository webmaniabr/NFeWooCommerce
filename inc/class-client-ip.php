<?php
/** Visitor IP resolution shared by rate limiting, SDK compatibility and optional audit logging. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class WooCommerceNFe_Client_IP {
	/** Official proxy networks, checked on 2026-10-24. No network lookup is needed per request. */
	public static function cloudflare_ranges() {
		// https://www.cloudflare.com/ips-v4 and https://www.cloudflare.com/ips-v6
		return array(
			'173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
			'141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
			'197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
			'104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
			'2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
			'2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
		);
	}

	/** Return one canonical IPv4/IPv6 address; never accept lists, ports or control characters. */
	private static function normalize( $value ) {
		if ( ! is_string( $value ) || ! filter_var( $value, FILTER_VALIDATE_IP ) ) { return false; }
		$packed = inet_pton( $value );
		if ( $packed === false ) { return false; }
		if ( strlen( $packed ) === 16 && substr( $packed, 0, 12 ) === str_repeat( "\0", 10 ) . "\xff\xff" ) {
			$packed = substr( $packed, 12 );
		}
		return inet_ntop( $packed );
	}

	private static function in_range( $ip, $range ) {
		if ( ! is_string( $range ) || strlen( $range ) > 64 ) { return false; }
		$parts = explode( '/', $range, 2 );
		$network = self::normalize( $parts[0] );
		if ( $network === false ) { return false; }
		$packed = inet_pton( $ip );
		$net = inet_pton( $network );
		$size = strlen( $net );
		if ( strlen( $packed ) !== $size ) { return false; }
		$prefix = isset( $parts[1] ) ? $parts[1] : (string) ( $size * 8 );
		if ( ! ctype_digit( $prefix ) || (int) $prefix <= 0 || (int) $prefix > $size * 8 ) { return false; }
		$bits = (int) $prefix;
		$bytes = intdiv( $bits, 8 );
		if ( substr( $packed, 0, $bytes ) !== substr( $net, 0, $bytes ) ) { return false; }
		$remaining = $bits % 8;
		$mask = ( 0xff << ( 8 - $remaining ) ) & 0xff;
		return $remaining === 0 || ( ord( $packed[$bytes] ) & $mask ) === ( ord( $net[$bytes] ) & $mask );
	}

	/** A forwarded header can identify the visitor only through an explicitly trusted proxy. */
	public static function resolve( array $server, array $additional_proxies = array() ) {
		$remote = self::normalize( $server['REMOTE_ADDR'] ?? null );
		if ( $remote === false ) { return 'unknown'; }
		$trusted = false;
		foreach ( array_merge( self::cloudflare_ranges(), $additional_proxies ) as $range ) {
			if ( self::in_range( $remote, $range ) ) { $trusted = true; break; }
		}
		if ( ! $trusted ) { return $remote; }
		$visitor = self::normalize( $server['HTTP_CF_CONNECTING_IP'] ?? null );
		if ( $visitor === false ) { return $remote; }
		// Pseudo IPv4 "Overwrite Headers" retains the original visitor in CF-Connecting-IPv6.
		if ( self::in_range( $visitor, '240.0.0.0/4' ) ) {
			$ipv6 = self::normalize( $server['HTTP_CF_CONNECTING_IPV6'] ?? null );
			if ( $ipv6 !== false && strpos( $ipv6, ':' ) !== false ) { return $ipv6; }
		}
		return $visitor;
	}

	public static function from_request() {
		$server = array();
		foreach ( array( 'REMOTE_ADDR', 'HTTP_CF_CONNECTING_IP', 'HTTP_CF_CONNECTING_IPV6' ) as $key ) {
			if ( isset( $_SERVER[$key] ) && is_string( $_SERVER[$key] ) ) {
				$server[$key] = sanitize_text_field( wp_unslash( $_SERVER[$key] ) );
			}
		}
		// Additional origin proxies must forward/overwrite CF headers and restrict ingress to trusted sources.
		$additional = apply_filters( 'woocommerce_nfe_trusted_cloudflare_proxies', array() );
		return self::resolve( $server, is_array( $additional ) ? $additional : array() );
	}
}
