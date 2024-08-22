<?php

class NFeGatewayPaypal extends WooCommerceNFe {

  /**
   * Plugin activated
   *
   * @return boolean
   */
  static function is_activated(){

    return self::wmbr_is_plugin_active('paypal-brasil-para-woocommerce/paypal-brasil.php');

  }

  /**
   * EBANX Payment Methods
   *
   * @return boolean
   */
	static function payment_methods(){

		return [ 'paypal-brasil-plus-gateway' ];

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
    $order = wc_get_order( $post_id );
    $paypal_data = $order->get_meta( 'wc_ppp_brasil_installments' );

    // Mount data
    if (
			NFeGatewayPaypal::is_activated() &&
			get_option('wc_settings_parcelas_ebanx') == 'yes' &&
      $order->get_payment_method() == 'paypal-brasil-plus-gateway'
		) {

      $installments = ($paypal_data) ? $paypal_data : 1;
			$data = UtilsGateways::mount_installments_data( $post_id, $data, $order, $installments, $args );

    }

    return $data;

  }

}
