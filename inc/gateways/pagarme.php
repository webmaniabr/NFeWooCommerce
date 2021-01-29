<?php

class NFeGatewayPagarme extends WooCommerceNFe {

  /**
   * Plugin activated
   * 
   * @return boolean
   */
  static function is_activated(){

    return self::wmbr_is_plugin_active('woocommerce-pagarme/woocommerce-pagarme.php');

  }

  /**
   * Pagar.me Payment Methods
   * 
   * @return boolean
   */
	static function payment_methods(){

		return [ 'pagarme-banking-ticket', 'pagarme-credit-card' ];

	}

  /**
   * Mount installments data
   * 
   * @param integer $post_id
   * @param array $data 
   * @return array $data
   */
  static function installments( $post_id, $data, $order, $args ){

    // Vars
    $pagarme_transaction_data = get_post_meta( $post_id, '_wc_pagarme_transaction_data', true);

    // Mount data
    if (
			NFeGatewayPagarme::is_activated() &&
			get_option('wc_settings_parcelas_ebanx') == 'yes' &&
      $order->payment_method == 'pagarme-credit-card'
		) {

			$installments = $pagarme_transaction_data['installments'];
			$installments = ($installments) ? $installments : 1;
			$data = UtilsGateways::mount_installments_data( $post_id, $data, $order, $installments, $args );
			
    }
    
    return $data;

  }

  /**
   * Mount data with Pagar.me payment type
   * 
   * @param integer $post_id
   * @param array $data 
   * @return array $data
   */
  static function payment_type( $post_id, $order, $data ){

    // Vars
    $pagarme_transaction_data = get_post_meta( $post_id, '_wc_pagarme_transaction_data', true);
    $origem_state = WC_Admin_Settings::get_option( 'woocommerce_default_country' );

    // Set payment type
    if ( $order->payment_method == 'pagarme-banking-ticket' ){

      $data['pedido']['forma_pagamento'] = ['15']; // 15 - Boleto Bancário

    } elseif ( $order->payment_method == 'pagarme-credit-card' && $origem_state == 'BR:SC'){

      $data['pedido']['forma_pagamento'] = ['99']; // 99 - Outros

    } elseif ( $order->payment_method == 'pagarme-credit-card' ){

      $data['pedido']['forma_pagamento'] = ['03']; // 03 - Cartão de crédito

    } else {

      $data['pedido']['forma_pagamento'] = ['99']; // 99 - Outros

    }

    return $data;

  }

}