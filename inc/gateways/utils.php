<?php

class UtilsGateways {

  /**
   * Returns whether there is integration with the payment gateway
   * 
   * @param string $payment_method
   * @return string [Gateway name]
   */
  function get_gateway_class( $payment_method ){

    if (in_array( $payment_method, NFeGatewayEbanx::payment_methods() )){

      return 'NFeGatewayEbanx';

    } elseif (in_array( $payment_method, NFeGatewayPagarme::payment_methods() )){

      return 'NFeGatewayPagarme';

    } elseif (in_array( $payment_method, NFeGatewayPagSeguro::payment_methods() )){

      return 'NFeGatewayPagSeguro';

    } elseif (in_array( $payment_method, NFeGatewayPaypal::payment_methods() )){

      return 'NFeGatewayPaypal';

    } elseif (in_array( $payment_method, NFeGatewayBacs::payment_methods() )){

      return 'NFeGatewayBacs';

    }

    return false;

  }

  /**
   * Mount installments data
   * 
   * @param integer $post_id
   * @param object $order
   * @param integer $installments
   * @param array $args
   * @return string [Gateway name]
   */
  function mount_installments_data( $post_id, $data, $order, $installments, $args ){

    // Vars
    $order_total = $data['pedido']['total'];
      
    // Create 'fatura' array			
    $data['fatura'] =  array(
      'numero'		=> '000001',
      'valor'		 	=> number_format(($order_total + $args['total_discount']), 2, '.', ''),
      'desconto'		=> number_format($args['total_discount'], 2, '.', ''),
      'valor_liquido' => $order_total
    );				
    
    // Declare vars
    $data['parcelas'] = array();	
    $installment = round($order_total / $installments, 2);
    $total_installments = 0;
    $order_date = get_the_time('Y-m-d', $post_id);

    for ( $i = 1; $i <= $installments; $i++ ) {
      
      // When reach the last intallment, calculate the total
      if ( $i == $installments ) {
        $installment = $order_total - $total_installments;
      } else {
        $total_installments += $installment;
      }
      
      // Add installment to NF-e invoice
      $data['parcelas'][] = array(
        'vencimento' => $order_date,
        'valor' => number_format($installment, 2, '.', '')
      );
      
      // Add 30 days to next installment
      $order_date = date('Y-m-d', strtotime("+1 month", strtotime($order_date)));

    }

    return $data;

  }
  
}