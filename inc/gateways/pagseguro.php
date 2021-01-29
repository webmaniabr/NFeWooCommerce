<?php

class NFeGatewayPagSeguro extends WooCommerceNFe {

  /**
   * Plugin activated
   * 
   * @return boolean
   */
  static function is_activated(){

    return self::wmbr_is_plugin_active('woocommerce-pagseguro/woocommerce-pagseguro.php');

  }

  /**
   * Pagar.me Payment Methods
   * 
   * @return boolean
   */
	static function payment_methods(){

		return [ 'pagseguro' ];

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
    $pagseguro_transaction_data = get_post_meta( $post_id, '_wc_pagseguro_payment_data', true);

    // Mount data
    if (
			NFeGatewayPagSeguro::is_activated() &&
			get_option('wc_settings_parcelas_ebanx') == 'yes' &&
      $pagseguro_transaction_data &&
      $pagseguro_transaction_data['method'] &&
      !in_array( $pagseguro_transaction_data['method'], [ 'Bradesco', 'Santander', 'Itaú', 'Unibanco', 'Banco do Brasil', 'Real', 'Banrisul', 'HSBC', 'PagSeguro credit', 'Oi Paggo', 'Account deposit' ] )
		) {

			$installments = $pagseguro_transaction_data['installments'];
			$installments = ($installments) ? $installments : 1;
			$data = UtilsGateways::mount_installments_data( $post_id, $data, $order, $installments, $args );
			
    }
    
    return $data;

  }

  /**
   * Mount data with Pagseguro payment type
   * 
   * @param integer $post_id
   * @param array $data 
   * @return array $data
   */
  static function payment_type( $post_id, $order, $data ){

    // Vars
    $payment_type = get_post_meta($post_id, __( 'Payment type', 'woocommerce-pagseguro' ), true);
    $origem_state = WC_Admin_Settings::get_option( 'woocommerce_default_country' );

    // Set payment type
    if ( strtolower($payment_type) == 'boleto'){

      $data['pedido']['forma_pagamento'] = ['15'];

    } elseif ($payment_type == 'Cartão de Crédito' && $origem_state == 'BR:SC'){

      $data['pedido']['forma_pagamento'] = ['99'];

    } elseif ($payment_type == 'Cartão de Crédito'){

      $data['pedido']['forma_pagamento'] = ['03'];

    } else {

      $data['pedido']['forma_pagamento'] = ['99'];

    }

    return $data;

  }

}