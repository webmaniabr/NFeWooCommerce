<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class NFeUtils {

  /**
   * Mount custom installment
   *
   * @param integer $post_id
   * @param array $data
   * @return array $data
   */
  static function custom_installments( $post_id, $data, $order, $args ){

    $invoice_input = WooCommerceNFe_Security::invoice_input( $post_id );
    // Vars
    $order = wc_get_order( $post_id );
    $data['parcelas'] = array();
    $nfe_installments_n = (isset($invoice_input['nfe_installments_n'])) ? (int) $invoice_input['nfe_installments_n'] : (int) $order->get_meta( '_nfe_installments_n' );
    $nfe_installments_n = max( 0, min( 120, $nfe_installments_n ) );
    $nfe_installments_due_date = (isset($invoice_input['nfe_installments_due_date']) && is_array($invoice_input['nfe_installments_due_date'])) ? array_map('sanitize_text_field', $invoice_input['nfe_installments_due_date']) : (array) $order->get_meta( '_nfe_installments_due_date' );
    $nfe_installments_value = (isset($invoice_input['nfe_installments_value']) && is_array($invoice_input['nfe_installments_value'])) ? array_map('sanitize_text_field', $invoice_input['nfe_installments_value']) : (array) $order->get_meta( '_nfe_installments_value' );
    $order_total = 0;

    // Installments
    for ($i = 0; $i < $nfe_installments_n; $i++){

      $date_src = isset($nfe_installments_due_date[$i]) ? $nfe_installments_due_date[$i] : '';
      $value_src = isset($nfe_installments_value[$i]) ? $nfe_installments_value[$i] : '0';
      $invoice_date = gmdate( 'Y-m-d', strtotime( str_replace( '/', '-', sanitize_text_field( $date_src ) ) ) );
      $value = number_format((float) str_replace(',', '.', str_replace('R$', '', $value_src)), 2, '.', '');
      $order_total += $value;

      $data['parcelas'][] = array(
        'vencimento' => $invoice_date,
        'valor' => $value
      );

    }

    // Vars
    if ($data['pedido']['total'] < $order_total){

      $value = $order_total;
      $discount = '0.00';
      $net_value = $order_total;

    } else {

      $value = $data['pedido']['total'];
      $discount = ($data['pedido']['total'] - $order_total);
      $net_value = $order_total;

    }

    // Billing
    $data['fatura'] =  array(
      'numero'		=> '000001',
      'valor'		 	=> number_format($value, 2, '.', ''),
      'desconto'		=> number_format($discount, 2, '.', ''),
      'valor_liquido' => number_format($order_total, 2, '.', '')
    );

    // Return
    return $data;

  }

  /**
   * Return Frenet Carriers
   *
   * @return object $frenet
   */
  static function get_frenet_carriers(){

    // Use WooCommerce's shipping-zone data store and cache, including the rest-of-world zone.
    $zones = WC_Shipping_Zones::get_zones();
    $zone = new WC_Shipping_Zone( 0 );
    $methods = $zone->get_shipping_methods( true );
    foreach ( $zones as $data ) {
      $methods = array_merge( $methods, $data['shipping_methods'] );
    }
    $instances = array();
    foreach ( $methods as $method ) {
      if ( $method->id === 'frenet' && $method->enabled === 'yes' ) { $instances[] = $method->get_instance_id(); }
    }
    sort( $instances, SORT_NUMERIC );
    $options = false;
    foreach ( $instances as $instance ) {
      $candidate = get_option( 'woocommerce_frenet_' . $instance . '_settings' );
      if ( is_array( $candidate ) && ! empty( $candidate['token'] ) && is_string( $candidate['token'] ) ) { $options = $candidate; break; }
    }
    if ( ! $options ) { return false; }

    // Connect
    $args = array(
      'headers' => array(
        'Content-Type' => 'application/json',
        'token' => $options['token']
      )
    );
    $response = wp_safe_remote_get( 'https://api.frenet.com.br/shipping/info', array_merge( $args, array( 'redirection' => 0, 'timeout' => 15 ) ) );

    if (is_wp_error( $response )){
      return false;
    } else {
      $frenet = json_decode( wp_remote_retrieve_body( $response ) );
    }

    if (!is_object($frenet) || !empty($frenet->Message) || !isset($frenet->ShippingSeviceAvailableArray) || !is_array($frenet->ShippingSeviceAvailableArray))
      return false;

    return $frenet;

  }

}
