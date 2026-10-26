<?php

/**
* Plugin Name: Nota Fiscal Eletrônica Webmania
* Plugin URI: https://webmania.com.br/nota-fiscal-eletronica/
* Description: Emissão de Nota Fiscal Eletrônica para WooCommerce através da REST API da Webmania®.
* Author: WebmaniaBR
* Author URI: https://webmaniabr.com
* Version: 3.4.7
* Requires PHP: 7.4
* Requires at least: 4.7
* Requires Plugins: woocommerce
* Text Domain: nota-fiscal-eletronica-woocommerce
* Domain Path: /languages
* WC requires at least: 3.0.0
* WC tested up to: 11.2.1
* Copyright: © 2009-2024 Webmania.
* License: GPL-3.0-or-later
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Load security configuration
if ( file_exists( __DIR__ . '/security-config.php' ) ) {
	require_once __DIR__ . '/security-config.php';
}

// Declare compatibility for WooCommerce HPOS
add_action( 'before_woocommerce_init', function() {
  if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
  }
} );

if (!class_exists('WooCommerceNFe'))
  require_once __DIR__ . '/init-class.php';

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Historical public bootstrap alias used by existing integrations.
function WC_NFe(){

  return WooCommerceNFe::instance();

}

// Initialize after WooCommerce and after WordPress makes translations available.
add_action( 'init', 'WC_NFe', 5 );
