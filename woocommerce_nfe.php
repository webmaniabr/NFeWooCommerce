<?php

/**
* Plugin Name: Nota Fiscal Eletrônica WooCommerce
* Plugin URI: webmaniabr.com
* Description: Emissão de Nota Fiscal Eletrônica para WooCommerce através da REST API da WebmaniaBR®.
* Author: WebmaniaBR
* Author URI: https://webmaniabr.com
* Version: 3.2.5.3
* Copyright: © 2009-2022 WebmaniaBR.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if (!class_exists('WooCommerceNFe'))
  require_once 'init-class.php';

function WC_NFe(){

  return WooCommerceNFe::instance();

}

add_action( 'plugins_loaded', 'WC_NFe', 20);
