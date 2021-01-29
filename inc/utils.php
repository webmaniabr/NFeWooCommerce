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

    $nfe_installments_n = get_post_meta($post_id, '_nfe_installments_n', true);
    $nfe_installments_n = ($nfe_installments_n) ? $nfe_installments_n : 0;
    $nfe_installments_due_date = get_post_meta( $post_id, '_nfe_installments_due_date', true );
    $nfe_installments_value = get_post_meta( $post_id, '_nfe_installments_value', true );
    $order_total = $order->get_total();

    // Create 'fatura' array			
    $data['fatura'] =  array(
      'numero'		=> '000001',
      'valor'		 	=> number_format(($order_total + $args['total_discount']), 2, '.', ''),
      'desconto'		=> number_format($args['total_discount'], 2, '.', ''),
      'valor_liquido' => $order_total
    );

    for ($i = 0; $i < $nfe_installments_n; $i++){

      $data['parcelas'][] = array(
        'vencimento' => date('Y-m-d', strtotime(str_replace("/", "-", $nfe_installments_due_date[$i]))),
        'valor' => number_format(str_replace(',', '.', str_replace('R$', '', $nfe_installments_value[$i])), 2, '.', '')
      );

    }

    // Return
    return $data;

  }

}