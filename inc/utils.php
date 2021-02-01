<?php

class NFeUtils {

  /**
   * Mount custom installment
   * 
   * @param integer $post_id
   * @param array $data
   * @return array $data
   */
  static function custom_installments( $post_id, $data, $order, $args ){

    // Vars
    $data['parcelas'] = array();
    $nfe_installments_n = ($_POST['nfe_installments_n']) ? $_POST['nfe_installments_n'] : get_post_meta($post_id, '_nfe_installments_n', true);
    $nfe_installments_n = ($nfe_installments_n) ? $nfe_installments_n : 0;
    $nfe_installments_due_date = ($_POST['nfe_installments_due_date']) ? $_POST['nfe_installments_due_date'] : get_post_meta( $post_id, '_nfe_installments_due_date', true );
    $nfe_installments_value = ($_POST['nfe_installments_value']) ? $_POST['nfe_installments_value'] : get_post_meta( $post_id, '_nfe_installments_value', true );
    $order_total = 0;

    // Installments
    for ($i = 0; $i < $nfe_installments_n; $i++){

      $invoice_date = date('Y-m-d', strtotime(str_replace("/", "-", $nfe_installments_due_date[$i])));
      $value = number_format(str_replace(',', '.', str_replace('R$', '', $nfe_installments_value[$i])), 2, '.', '');
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

    global $wpdb;

    // Token
    $instance_id = $wpdb->get_results($wpdb->prepare("SELECT instance_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE is_enabled = %d AND method_id = %s ", 1, 'frenet'));

    if (!$instance_id)
      return false;

    foreach ($instance_id as $var){

      $options = get_option('woocommerce_frenet_' . $var->instance_id . '_settings');

      if ($options && $options['token'])
        break;

    }

    if (!$options || $options && !$options['token'])
      return false;

    // Connect
    $args = array(
      'headers' => array(
        'Content-Type' => 'application/json',
        'token' => $options['token']
      )
    );
    $response = wp_remote_get( 'http://api.frenet.com.br/shipping/info', $args );

    if (is_wp_error( $response )){
      return false;
    } else {
      $frenet = json_decode( $response['body'] );
    }
    
    if ($frenet->Message)
      return false;

    return $frenet;

  }

}