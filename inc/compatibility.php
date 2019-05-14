<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Compatibility with other extensions.
 *
 * @class    WC_NFe_Compatibility
 * @version  2.2.0
 */
class WC_NFe_Compatibility {

	/**
	 * Complex product types integrated with SATT.
	 * @var array
	 */
	private static $bundle_types = array();

	/**
	 * Complex type container cart/order item key names.
	 *
	 * @deprecated  2.8.0
	 * @var         array
	 */
	public static $container_key_names = array();

	/**
	 * Complex type child cart/order item key names.
	 *
	 * @deprecated  2.8.0
	 * @var         array
	 */
	public static $child_key_names = array();

	/**
	 * Initialize.
	 */
	public static function init() {

		// Bundles.
		if ( class_exists( 'Yith_Bundles' ) ) {
			self::$bundle_types[]                      = 'yith_bundle';
			self::$container_key_names[]               = 'bundled_by';
			self::$child_key_names[]                   = 'bundled_items';
		}

		// Bundles.
		if ( class_exists( 'WC_Bundles' ) ) {
			self::$bundle_types[]                      = 'bundle';
			self::$container_key_names[]               = 'bundled_by';
			self::$child_key_names[]                   = 'bundled_items';
		}

		// Composites.
		if ( class_exists( 'WC_Composite_Products' ) ) {
			self::$bundle_types[]                      = 'composite';
			self::$container_key_names[]               = 'composite_parent';
			self::$child_key_names[]                   = 'composite_children';
		}

		// Mix n Match.
		if ( class_exists( 'WC_Mix_and_Match' ) ) {
			self::$bundle_types[]                      = 'mix-and-match';
			self::$container_key_names[]               = 'mnm_container';
			self::$child_key_names[]                   = 'mnm_contents';
		}

	}

	/*
	|--------------------------------------------------------------------------
	| Helpers
	|--------------------------------------------------------------------------
	*/

	/**
	 * Checks if the passed product is of a supported bundle type. Returns the type if yes, or false if not.
	 *
	 * @param  WC_Product  $product
	 * @return boolean
	 */
	public static function is_bundle_type_product( $product ) {
		return $product->is_type( self::$bundle_types );
	}



	/**
	 * True if an order item appears to be a bundle-type container item.
	 *
	 * @since  2.8.0
	 *
	 * @param  array     $order_item
	 * @return boolean
	 */
	public static function maybe_is_bundle_type_container_order_item( $order_item ) {

		$is = false;

		foreach ( self::$container_order_item_conditionals as $container_order_item_conditional ) {
			$is = ! empty( $order_item[ $container_order_item_conditional ] );

			if ( $is ) {
				break;
			}
		}

		return $is;
	}

	/**
	 * True if an order item appears to be part of a bundle-type product.
	 *
	 * @since  2.8.0
	 *
	 * @param  array     $cart_item
	 * @return boolean
	 */
	public static function maybe_is_bundled_type_order_item( $order_item ) {

		$is = false;

		foreach ( self::$container_key_names as $container_key_name ) {
			$is = ! empty( $order_item[ $container_key_name ] );

			if ( $is ) {
				break;
			}
		}

		return $is;
	}


}


WC_NFe_Compatibility::init();