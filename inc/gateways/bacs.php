<?php

class NFeGatewayBacs extends WooCommerceNFe {

  /**
   * Plugin activated
   *
   * @return boolean
   */
  static function is_activated(){

    return true;

  }

  /**
   * Bacs Payment Methods
   *
   * @return boolean
   */
	static function payment_methods(){

		return [ 'bacs', 'other' ];

  }

  /**
   * Mount installments data
   *
   * @param integer $post_id
   * @param array $data
   * @return array $data
   */
  static function installments( $post_id, $data, $order, $args ){

    if (
			NFeGatewayEbanx::is_activated() &&
			get_option('wc_settings_parcelas_ebanx') == 'yes' &&
			in_array($order->get_payment_method(), [ 'bacs' ])
		) {

      $order = wc_get_order( $post_id );
			$installments = $order->get_meta( '_instalments' );
			$installments = ($installments) ? $installments : 1;
      $data = UtilsGateways::mount_installments_data( $post_id, $data, $order, $installments, $args );

    }

    return $data;

	}

  /**
   * Mount data with Bacs payment type
   *
   * @param integer $post_id
   * @param array $data
   * @return array $data
   */
  static function payment_type( $post_id, $order, $data ){

    // Vars
    $origem_state = WC_Admin_Settings::get_option( 'woocommerce_default_country' );

    // Set payment type
    if ( in_array( $order->get_payment_method(), [ 'bacs', 'other' ] ) ){

      $data['pedido']['forma_pagamento'] = ( $order->get_payment_method() == 'bacs' ) ? ['18'] : ['99']; //18 - Transferência bancária, Carteira Digital (bacs) or 99 - Outros (other)

    }

    return $data;

  }


}
