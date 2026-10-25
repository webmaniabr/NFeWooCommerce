<?php
// Bundled for WordPress: never execute this file directly.
if ( ! defined( 'ABSPATH' ) ) { exit; }
//============================================================+
// File name   : tcpdf_autoconfig.php
// Version     : 1.1.1
// Begin       : 2013-05-16
// Last Update : 2025-04-18
// Authors     : Nicola Asuni - Tecnick.com LTD - www.tecnick.com - info@tecnick.com
// License     : GNU-LGPL v3 (https://www.gnu.org/copyleft/lesser.html)
// -------------------------------------------------------------------
// Copyright (C) 2011-2026 Nicola Asuni - Tecnick.com LTD
//
// This file is part of TCPDF software library.
//
// TCPDF is free software: you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// TCPDF is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the License
// along with TCPDF. If not, see
// <http://www.tecnick.com/pagefiles/tcpdf/LICENSE.TXT>.
//
// See LICENSE.TXT file for more information.
// -------------------------------------------------------------------
//
// Description : Try to automatically configure some TCPDF
//               constants if not defined.
//
//============================================================+

/**
 * @file
 * Try to automatically configure some TCPDF constants if not defined.
 * @package com.tecnick.tcpdf
 * @version 1.2.1
 */

// Disable phar stream wrapper globally.
// if (in_array('phar', stream_get_wrappers(), true)) {
//     stream_wrapper_unregister('phar');
// }

// Load main configuration file only if the K_TCPDF_EXTERNAL_CONFIG constant is set to false.
if (!defined('K_TCPDF_EXTERNAL_CONFIG') OR !K_TCPDF_EXTERNAL_CONFIG) {
	// define a list of default config files in order of priority
	$woocommercenfe_pdf_tcpdf_config_files = array(dirname(__FILE__).'/config/tcpdf_config.php', '/etc/php-tcpdf/tcpdf_config.php', '/etc/tcpdf/tcpdf_config.php', '/etc/tcpdf_config.php');
	foreach ($woocommercenfe_pdf_tcpdf_config_files as $woocommercenfe_pdf_tcpdf_config) {
		if (@file_exists($woocommercenfe_pdf_tcpdf_config) AND is_readable($woocommercenfe_pdf_tcpdf_config)) {
			require_once($woocommercenfe_pdf_tcpdf_config);
			break;
		}
	}
}

if (!defined('WOOCOMMERCE_NFE_PDF_K_PATH_MAIN')) {
	define ('WOOCOMMERCE_NFE_PDF_K_PATH_MAIN', dirname(__FILE__).'/');
}

if (!defined('WOOCOMMERCE_NFE_PDF_K_PATH_FONTS')) {
	define ('WOOCOMMERCE_NFE_PDF_K_PATH_FONTS', WOOCOMMERCE_NFE_PDF_K_PATH_MAIN.'fonts/');
}

if (!defined('WOOCOMMERCE_NFE_PDF_K_PATH_URL')) {
	define('WOOCOMMERCE_NFE_PDF_K_PATH_URL', plugins_url('/', __FILE__));
}

if (!defined('WOOCOMMERCE_NFE_PDF_K_PATH_IMAGES')) {
	$woocommercenfe_pdf_tcpdf_images_dirs = array(WOOCOMMERCE_NFE_PDF_K_PATH_MAIN.'examples/images/', WOOCOMMERCE_NFE_PDF_K_PATH_MAIN.'images/', '/usr/share/doc/php-tcpdf/examples/images/', '/usr/share/doc/tcpdf/examples/images/', '/usr/share/doc/php/tcpdf/examples/images/', '/var/www/tcpdf/images/', '/var/www/html/tcpdf/images/', '/usr/local/apache2/htdocs/tcpdf/images/', WOOCOMMERCE_NFE_PDF_K_PATH_MAIN);
	foreach ($woocommercenfe_pdf_tcpdf_images_dirs as $woocommercenfe_pdf_tcpdf_images_path) {
		if (@file_exists($woocommercenfe_pdf_tcpdf_images_path)) {
			define ('WOOCOMMERCE_NFE_PDF_K_PATH_IMAGES', $woocommercenfe_pdf_tcpdf_images_path);
			break;
		}
	}
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_HEADER_LOGO')) {
	$woocommercenfe_pdf_tcpdf_header_logo = '';
	if (@file_exists(WOOCOMMERCE_NFE_PDF_K_PATH_IMAGES.'tcpdf_logo.jpg')) {
		$woocommercenfe_pdf_tcpdf_header_logo = 'tcpdf_logo.jpg';
	}
	define ('WOOCOMMERCE_NFE_PDF_PDF_HEADER_LOGO', $woocommercenfe_pdf_tcpdf_header_logo);
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_HEADER_LOGO_WIDTH')) {
	if (!empty($woocommercenfe_pdf_tcpdf_header_logo)) {
		define ('WOOCOMMERCE_NFE_PDF_PDF_HEADER_LOGO_WIDTH', 30);
	} else {
		define ('WOOCOMMERCE_NFE_PDF_PDF_HEADER_LOGO_WIDTH', 0);
	}
}

if (!defined('WOOCOMMERCE_NFE_PDF_K_PATH_CACHE')) {
	$woocommercenfe_pdf_cache = WooCommerceNFe_Security::private_directory();
	if ( ! $woocommercenfe_pdf_cache ) { throw new RuntimeException('Private PDF cache unavailable.'); }
	define('WOOCOMMERCE_NFE_PDF_K_PATH_CACHE', $woocommercenfe_pdf_cache . '/');
}

if (!defined('WOOCOMMERCE_NFE_PDF_K_BLANK_IMAGE')) {
	define ('WOOCOMMERCE_NFE_PDF_K_BLANK_IMAGE', '_blank.png');
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_PAGE_FORMAT')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_PAGE_FORMAT', 'A4');
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_PAGE_ORIENTATION')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_PAGE_ORIENTATION', 'P');
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_CREATOR')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_CREATOR', 'TCPDF');
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_AUTHOR')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_AUTHOR', 'TCPDF');
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_HEADER_TITLE')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_HEADER_TITLE', 'TCPDF Example');
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_HEADER_STRING')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_HEADER_STRING', "by Nicola Asuni - Tecnick.com\nwww.tcpdf.org");
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_UNIT')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_UNIT', 'mm');
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_MARGIN_HEADER')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_MARGIN_HEADER', 5);
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_MARGIN_FOOTER')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_MARGIN_FOOTER', 10);
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_MARGIN_TOP')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_MARGIN_TOP', 27);
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_MARGIN_BOTTOM')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_MARGIN_BOTTOM', 25);
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_MARGIN_LEFT')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_MARGIN_LEFT', 15);
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_MARGIN_RIGHT')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_MARGIN_RIGHT', 15);
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_FONT_NAME_MAIN')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_FONT_NAME_MAIN', 'helvetica');
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_FONT_SIZE_MAIN')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_FONT_SIZE_MAIN', 10);
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_FONT_NAME_DATA')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_FONT_NAME_DATA', 'helvetica');
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_FONT_SIZE_DATA')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_FONT_SIZE_DATA', 8);
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_FONT_MONOSPACED')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_FONT_MONOSPACED', 'courier');
}

if (!defined('WOOCOMMERCE_NFE_PDF_PDF_IMAGE_SCALE_RATIO')) {
	define ('WOOCOMMERCE_NFE_PDF_PDF_IMAGE_SCALE_RATIO', 96/72);
}

if (!defined('WOOCOMMERCE_NFE_PDF_HEAD_MAGNIFICATION')) {
	define('WOOCOMMERCE_NFE_PDF_HEAD_MAGNIFICATION', 1.1);
}

if (!defined('WOOCOMMERCE_NFE_PDF_K_CELL_HEIGHT_RATIO')) {
	define('WOOCOMMERCE_NFE_PDF_K_CELL_HEIGHT_RATIO', 1.25);
}

if (!defined('WOOCOMMERCE_NFE_PDF_K_TITLE_MAGNIFICATION')) {
	define('WOOCOMMERCE_NFE_PDF_K_TITLE_MAGNIFICATION', 1.3);
}

if (!defined('WOOCOMMERCE_NFE_PDF_K_SMALL_RATIO')) {
	define('WOOCOMMERCE_NFE_PDF_K_SMALL_RATIO', 2/3);
}

if (!defined('WOOCOMMERCE_NFE_PDF_K_THAI_TOPCHARS')) {
	define('WOOCOMMERCE_NFE_PDF_K_THAI_TOPCHARS', true);
}

if (!defined('WOOCOMMERCE_NFE_PDF_K_TCPDF_CALLS_IN_HTML')) {
	define('WOOCOMMERCE_NFE_PDF_K_TCPDF_CALLS_IN_HTML', false);
}

if (!defined('WOOCOMMERCE_NFE_PDF_K_ALLOWED_TCPDF_TAGS')) {
	define('WOOCOMMERCE_NFE_PDF_K_ALLOWED_TCPDF_TAGS', '');
}

if (!defined('WOOCOMMERCE_NFE_PDF_K_TCPDF_THROW_EXCEPTION_ERROR')) {
	define('WOOCOMMERCE_NFE_PDF_K_TCPDF_THROW_EXCEPTION_ERROR', false);
}

if (!defined('WOOCOMMERCE_NFE_PDF_K_TIMEZONE')) {
	define('WOOCOMMERCE_NFE_PDF_K_TIMEZONE', @date_default_timezone_get());
}

// Custom cURL options for curl_setopt_array.
if (!defined('WOOCOMMERCE_NFE_PDF_K_CURLOPTS')) {
	define('WOOCOMMERCE_NFE_PDF_K_CURLOPTS', array());
}

//============================================================+
// END OF FILE
//============================================================+
