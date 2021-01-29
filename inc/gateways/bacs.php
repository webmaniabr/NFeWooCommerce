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
    if ( in_array( $order->payment_method, [ 'bacs', 'other' ] ) ){

      $data['pedido']['forma_pagamento'] = ['99']; // 99 - Outros

    }

    return $data;

  }
  

}