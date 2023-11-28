<?php

class NFeGatewayEbanx extends WooCommerceNFe {

  /**
   * Plugin activated
   *
   * @return boolean
   */
  static function is_activated(){

    return self::wmbr_is_plugin_active('ebanx-local-payment-gateway-for-woocommerce/woocommerce-gateway-ebanx.php');

	}

	/**
   * EBANX Payment Methods
   *
   * @return boolean
   */
	static function payment_methods(){

		return [ 'ebanx-banking-ticket', 'ebanx-credit-card-br', 'ebanx-credit-card-international', 'ebanx-global' ];

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
			in_array($order->payment_method, [ 'ebanx-credit-card-br', 'ebanx-credit-card-international' ])
		) {

      $order = wc_get_order( $post_id );
			$installments = $order->get_meta( '_instalments_number' );
			$installments = ($installments) ? $installments : 1;
      $data = UtilsGateways::mount_installments_data( $post_id, $data, $order, $installments, $args );

    }

    return $data;

	}

	/**
   * Mount data with EBANX payment type
   *
   * @param integer $post_id
   * @param array $data
   * @return array $data
   */
  static function payment_type( $post_id, $order, $data ){

    // Vars
    $origem_state = WC_Admin_Settings::get_option( 'woocommerce_default_country' );

    // Set payment type
    if ( $order->payment_method == 'ebanx-banking-ticket' ){

      $data['pedido']['forma_pagamento'] = ['15']; // 15 - Boleto Bancário

    } elseif (in_array($order->payment_method, [ 'ebanx-credit-card-br', 'ebanx-credit-card-international' ]) && $origem_state == 'BR:SC'){

      $data['pedido']['forma_pagamento'] = ['99']; // 99 - Outros

    } elseif (in_array($order->payment_method, [ 'ebanx-credit-card-br', 'ebanx-credit-card-international' ])){

      $data['pedido']['forma_pagamento'] = ['03']; // 03 - Cartão de crédito

    } else {

      $data['pedido']['forma_pagamento'] = ['99']; // 99 - Outros

    }

    return $data;

  }

}
