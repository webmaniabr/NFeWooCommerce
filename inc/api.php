<?php

/*
Based of the plugin: WooCommerce Extra Checkout Fields for Brazil
@author Claudio Sanches
@link https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WooCommerceNFe_Api extends WooCommerceNFe {

    function init() {

        if (get_option('wc_settings_woocommercenfe_tipo_pessoa') == 'yes'){

            add_filter( 'woocommerce_api_order_response', array( 'WooCommerceNFe_Api', 'orders' ), 100, 4 );
            add_filter( 'woocommerce_api_customer_response', array( 'WooCommerceNFe_Api', 'customer' ), 100, 4 );

        }

	}

    function format_number( $string ) {
		return str_replace( array( '.', '-', '/' ), '', $string );
	}

    function get_formatted_birthdate( $date, $server ) {
		$birthdate = explode( '/', $date );

		if ( isset( $birthdate[1] ) && ! empty( $birthdate[1] ) ) {
			return $server->format_datetime( $birthdate[1] . '/' . $birthdate[0] . '/' . $birthdate[2] );
		}

		return '';
	}

    function get_person_type( $type ) {

        if ($type == 1) return 'F';
        else if ($type == 2) return 'J';
        else return '';

    }

    function orders( $order_data, $order, $fields, $server ) {

		// Billing fields.
		$order_data['billing_address']['persontype']   = self::get_person_type( $order->billing_persontype );
		$order_data['billing_address']['cpf']          = self::format_number( $order->billing_cpf );
		$order_data['billing_address']['cnpj']         = self::format_number( $order->billing_cnpj );
		$order_data['billing_address']['ie']           = self::format_number( $order->billing_ie );
		$order_data['billing_address']['birthdate']    = self::get_formatted_birthdate( $order->billing_birthdate, $server );
		$order_data['billing_address']['sex']          = substr( $order->billing_sex, 0, 1 );
		$order_data['billing_address']['number']       = $order->billing_number;
		$order_data['billing_address']['neighborhood'] = $order->billing_neighborhood;
		$order_data['billing_address']['cellphone']    = str_replace("?", "", $order->billing_cellphone);

		// Shipping fields.
		$order_data['shipping_address']['number']       = $order->shipping_number;
		$order_data['shipping_address']['neighborhood'] = $order->shipping_neighborhood;

		// Customer fields.
		if ( 0 == $order->customer_user && isset( $order_data['customer'] ) ) {
			// Customer billing fields.
			$order_data['customer']['billing_address']['persontype']   = self::get_person_type( $order->billing_persontype );
			$order_data['customer']['billing_address']['cpf']          = self::format_number( $order->billing_cpf );
			$order_data['customer']['billing_address']['cnpj']         = self::format_number( $order->billing_cnpj );
			$order_data['customer']['billing_address']['ie']           = self::format_number( $order->billing_ie );
			$order_data['customer']['billing_address']['birthdate']    = self::get_formatted_birthdate( $order->billing_birthdate, $server );
			$order_data['customer']['billing_address']['sex']          = substr( $order->billing_sex, 0, 1 );
			$order_data['customer']['billing_address']['number']       = $order->billing_number;
			$order_data['customer']['billing_address']['neighborhood'] = $order->billing_neighborhood;
			$order_data['customer']['billing_address']['cellphone']    = str_replace("?", "", $order->billing_cellphone);

			// Customer shipping fields.
			$order_data['customer']['shipping_address']['number']       = $order->shipping_number;
			$order_data['customer']['shipping_address']['neighborhood'] = $order->shipping_neighborhood;
		}

		if ( $fields ) {
			$order_data = WC()->api->WC_API_Customers->filter_response_fields( $order_data, $order, $fields );
		}

		return $order_data;

	}

    function customer( $customer_data, $customer, $fields, $server ) {

        // Billing fields.
		$customer_data['billing_address']['persontype']   = self::get_person_type( $customer->billing_persontype );
		$customer_data['billing_address']['cpf']          = self::format_number( $customer->billing_cpf );
		$customer_data['billing_address']['cnpj']         = self::format_number( $customer->billing_cnpj );
		$customer_data['billing_address']['ie']           = self::format_number( $customer->billing_ie );
		$customer_data['billing_address']['birthdate']    = self::get_formatted_birthdate( $customer->billing_birthdate, $server );
		$customer_data['billing_address']['sex']          = substr( $customer->billing_sex, 0, 1 );
		$customer_data['billing_address']['number']       = $customer->billing_number;
		$customer_data['billing_address']['neighborhood'] = $customer->billing_neighborhood;
		$customer_data['billing_address']['cellphone']    = str_replace("?", "", $order->billing_cellphone);

		// Shipping fields.
		$customer_data['shipping_address']['number']       = $customer->shipping_number;
		$customer_data['shipping_address']['neighborhood'] = $customer->shipping_neighborhood;

		if ( $fields ) {
			$customer_data = WC()->api->WC_API_Customers->filter_response_fields( $customer_data, $customer, $fields );
		}

		return $customer_data;

	}

}
