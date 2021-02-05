<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WooCommerceNFeIssue extends WooCommerceNFe {

	function __construct(){}
	
	/**
	 * Validate Plugin before Load
	 * 
	 * @return boolean
	 */
  function send( $order_ids = array(), $is_massa = false ){

		$result = array();

		foreach ($order_ids as $order_id) {

			// Data
			$data = $this->order_data( $order_id );

			// Async
			if ($is_massa) {
				$data['assincrono'] = 1;
			}

			do_action('nfe_before_response', $data, $order_id);

			$this->get_credentials( $order_id );
			$webmaniabr = new NFe($this->settings);
			$response = $webmaniabr->emissaoNotaFiscal( $data );
			$result[] = $response;

			do_action('nfe_after_response', $response, $order_id);

			if (isset($response->error) || $response->status == 'reprovado') {

				$mensagem = 'Erro ao emitir a NF-e do Pedido #'.$order_id.':';
				$mensagem .= '<ul style="padding-left:20px;">';
				$mensagem .= '<li>'.$response->error.'</li>';

				if (isset($response->log)){
					if ($response->log->xMotivo){
						if(isset($response->log->aProt[0]->xMotivo)){
							$error = $response->log->aProt[0]->xMotivo;
						}else{
							$error = $response->log->xMotivo;
						}
						$mensagem .= '<li>'.$error.'</li>';
					} else {
						foreach ($response->log as $erros){
							foreach ($erros as $erro) {
								$mensagem .= '<li>'.$erro.'</li>';
							}
						}
					}
				}
				$mensagem .= '</ul>';

				$invoice_error = isset($response->error) ? $response->error : $error;
				$this->send_error_email( $mensagem, $order_id );
				$this->add_id_to_invoice_errors( $invoice_error, $order_id );
				$this->add_error( $mensagem );

			} else {

				if ( is_object($response) && $data['previa_danfe'] ) {

					$this->add_success( 'Pré-visualizar Danfe: <a href="'.$response->danfe.'" target="_blank">'.$response->danfe.'</a>' );

				} else {

					$this->add_success( 'Nota Fiscal do pedido nº '.$order_id.' gerada com sucesso.' );
					$this->remove_id_to_invoice_errors($order_id);

				}

			}

			// If API respond with status, register 'NF-e'
			if ( is_object($response) && $response->status ) {

				$nfe = get_post_meta( $order_id, 'nfe', true );

				if (!$nfe) 
					$nfe = array();

				// Identify NFe repeated
				if (count($nfe) > 0){
					foreach ($nfe as $nf){

						if ($nf['chave_acesso'] == $response->chave){

							return $result;

						}

					}
				}

				// Register new NFe
				$nfe[] = array(
					'uuid'   => (string) $response->uuid,
					'status' => (string) $response->status,
					'chave_acesso' => $response->chave,
					'n_recibo' => (int) $response->recibo,
					'n_nfe' => (int) $response->nfe,
					'n_serie' => (int) $response->serie,
					'url_xml' => (string) $response->xml,
					'url_danfe' => (string) $response->danfe,
					'data' => date_i18n('d/m/Y'),
				);

				update_post_meta( $order_id, 'nfe', $nfe );

			}

		}

		// Return
		return $result;

	}

	/**
	 * Mount Order Data
	 * 
	 * @return boolean
	 */
	function order_data( $post_id ){

		global $wpdb;

		// Vars
		$payment_methods = get_option('wc_settings_woocommercenfe_payment_methods', array());
		$payment_keys = array_keys($payment_methods);
		$order = new WC_Order( $post_id );
		$default_imposto = get_option('wc_settings_woocommercenfe_imposto');
		$default_ncm = get_option('wc_settings_woocommercenfe_ncm');
		$default_cest = get_option('wc_settings_woocommercenfe_cest');
		$default_origem = get_option('wc_settings_woocommercenfe_origem');
		$transportadoras = get_option('wc_settings_woocommercenfe_transportadoras', array());
		$envio_email = get_option('wc_settings_woocommercenfe_envio_email');
		$coupons = $order->get_coupon_codes();
		$coupons_percentage = array();
		$total_discount = $total_fee = 0;
		$fee_aditional_informations = '';
		$natureza_operacao = '';
		$payment_gateway = UtilsGateways::get_gateway_class( $order->payment_method );

		// Coupons
		if ($coupons){
			foreach($coupons as $coupon_code){
				$coupon_obj = new WC_Coupon($coupon_code);
				if($coupon_obj->discount_type == 'percent'){
					$coupons_percentage[] = $coupon_obj->coupon_amount;
				}
			}
		}
		
		// Order
		$modalidade_frete = $_POST['modalidade_frete'];

		if (!isset($modalidade_frete)) 
			$modalidade_frete = get_post_meta($post_id, '_nfe_modalidade_frete', true);

		if (!$modalidade_frete || $modalidade_frete == 'null')
			 $modalidade_frete = 0;

		$order_key = $order->get_order_key();

		// Order Operation
		$natureza_operacao = (get_post_meta($order->get_id(), '_nfe_natureza_operacao_pedido', true)) ? get_post_meta($order->get_id(), '_nfe_natureza_operacao_pedido', true) : get_option('wc_settings_woocommercenfe_natureza_operacao');

		if ( isset($_POST['natureza_operacao_pedido']) && $_POST['natureza_operacao_pedido'] != '' && $_POST['natureza_operacao_pedido'] != $natureza_operacao ) {
			$natureza_operacao = $_POST['natureza_operacao_pedido'];
		}

		// Init data
		$data = array(
			'ID'                => $post_id, // Número do pedido
			'origem'					  => 'woocommerce',
			'url_notificacao'   => get_bloginfo('url').'/wc-api/nfe_callback?order_key='.$order_key.'&order_id='.$post_id,
			'operacao'          => apply_filters( 'nfe_order_operation', 1, $post_id ), // Tipo de Operação da Nota Fiscal
			'natureza_operacao' => apply_filters( 'nfe_order_operation_n', $natureza_operacao, $post_id  ), // Natureza da Operação
			'modelo'            => apply_filters( 'nfe_order_model', 1, $post_id ), // Modelo da Nota Fiscal (NF-e ou NFC-e)
			'finalidade'        => apply_filters( 'nfe_order_finality', 1, $post_id ), // Finalidade de emissão da Nota Fiscal
			'ambiente'          => apply_filters( 'nfe_environment', ( isset($_POST['emitir_homologacao']) && $_POST['emitir_homologacao'] ? '2' : (int) get_option('wc_settings_woocommercenfe_ambiente') ), $post_id ) // Identificação do Ambiente do Sefaz
		);

		// Fees
		if ($order->get_fees()){

			foreach ($order->get_fees() as $key => $item){
				
				if ($item['line_total'] < 0){

					$discount = abs($item['line_total']);
					$total_discount = $discount + $total_discount;

				} else {

					if ( $fee_aditional_informations != '' ) 
						$fee_aditional_informations .= ' / ';
					
					$fee_aditional_informations .= $item['name'] . ': R$' . number_format($item['line_total'], 2, ',', '');
					$fee = $item['line_total'];
					$total_fee = $fee + $total_fee;

				}
			}
		}

		$total_discount = $order->get_total_discount() + $total_discount;
		$data_emissao = get_option('wc_settings_woocommercenfe_data_emissao');
		
		if ( isset($data_emissao) && $data_emissao == 'yes' ) {
			$data['data_emissao'] = get_the_time('Y-m-d H:i:s', $post_id);
			$data['data_entrada_saida'] = get_the_time('Y-m-d H:i:s', $post_id);
		}
		
		$data['pedido'] = array(
			'presenca'         => apply_filters( 'nfe_order_presence', 2, $post_id ), // Indicador de presença do comprador no estabelecimento comercial no momento da operação
			'modalidade_frete' => apply_filters( 'nfe_order_freight', (int) $modalidade_frete, $post_id ), // Modalidade do frete
			'frete'            => number_format(get_post_meta( $order->get_id(), '_order_shipping', true ), 2, '.', '' ), // Total do frete
			'desconto'         => number_format($total_discount, 2, '.', '' ), // Total do desconto
			'total'            => $order->get_total() // Total do pedido - sem descontos
		);

		if ( $total_fee && $total_fee > 0 ) {
			$data['pedido']['despesas_acessorias'] = number_format($total_fee, 2, '.', '');
		}

		// Set Payment Method
		if ($payment_gateway && method_exists( "$payment_gateway", 'payment_type' )){

			$data = $payment_gateway::payment_type( $post_id, $order, $data );

		} elseif ( isset($payment_methods[$order->payment_method]) && $payment_methods[$order->payment_method] ) {

			$data['pedido']['forma_pagamento'] = [ $payment_methods[$order->payment_method] ];

		} else {

			$data['pedido']['forma_pagamento'] = '99'; // 99 - Outros

		}

		// Tax informations
		$fisco_inf = get_option('wc_settings_woocommercenfe_fisco_inf');
		
		if (!empty($fisco_inf) && strlen($fisco_inf) <= 2000) {
			$data['pedido']['informacoes_fisco'] = $fisco_inf;
		}

		// Consumer information
		$consumidor_inf = get_option('wc_settings_woocommercenfe_cons_inf');
		
		if ( $fee_aditional_informations != '' ) {
			$consumidor_inf .= $fee_aditional_informations;
		}

		if(!empty($consumidor_inf) && strlen($consumidor_inf) <= 2000){
			$data['pedido']['informacoes_complementares'] = $consumidor_inf;
		}

		// Customer
		if ($data['modelo'] == 2){

			$customer_cpf = get_post_meta($post_id, 'billing_cpf', true);
			$customer_cnpj = get_post_meta($post_id, 'billing_cnpj', true);

			if ($customer_cpf || $customer_cnpj){

				if ($customer_cpf)
					$data['cliente']['cpf'] = $customer_cpf;
				else
					$data['cliente']['cnpj'] = $customer_cnpj;

			}

		} else {

			$compare_addresses = $this->compare_addresses($order->get_id(), $envio_email);
			$data['cliente'] = $compare_addresses['cliente'];

			if ( isset($compare_addresses['transporte']['entrega']) ) {
				$data['transporte']['entrega'] = $compare_addresses['transporte']['entrega'];
			}

		}

		// Products
		$bundles = array();
		
		if (!isset($data['produtos'])) 
			$data['produtos'] = array();

		foreach ($order->get_items() as $key => $item){

			$product      = wc_get_product($item['product_id']);
			$product_type = $product->get_type();
			$product_id   = $item['product_id'];
			$bundled_by = isset($item['bundled_by']);

			if(!$bundled_by && is_a($item, 'WC_Order_Item_Product')){
				$bundled_by = $item->meta_exists('_bundled_by');
			}

			$variation_id = $item['variation_id'];

			if ( $product_type == 'bundle' || $product_type == 'yith_bundle' || $product_type == 'mix-and-match' || $bundled_by ){
				$bundles[] = $item;
				continue;
			}

			$product_info = $this->get_product_nfe_info($item, $order);
			$ignore_product = apply_filters( 'nfe_order_product_ignore', get_post_meta($product_id, '_nfe_ignorar_nfe', true), $product_id, $post_id);

			// Ignore product or NOT
			if ($ignore_product == 1){

				$data['pedido']['total'] -= $item['line_subtotal'];

				if ($coupons_percentage){

					foreach($coupons_percentage as $percentage){

						$data['pedido']['total'] += ($percentage/100)*$item['line_subtotal'];
						$data['pedido']['desconto'] -= ($percentage/100)*$item['line_subtotal'];

					}

				}

				$data['pedido']['total']    = number_format($data['pedido']['total'], 2, '.', '' );
				$data['pedido']['desconto'] = number_format($data['pedido']['desconto'], 2, '.', '' );

				continue;

			}

			// Tax Benefit
			$beneficio_fiscal = get_post_meta($order->get_id(), '_nfe_beneficio_fiscal_pedido', true);

			if ( isset($_POST['beneficio_fiscal_pedido']) && $_POST['beneficio_fiscal_pedido'] != '' && $_POST['beneficio_fiscal_pedido'] != $beneficio_fiscal ) {
				$beneficio_fiscal = $_POST['beneficio_fiscal_pedido'];
			}

			$product_info['beneficio_fiscal'] = ($beneficio_fiscal) ? $beneficio_fiscal : '';

			// Mount data
			$data['produtos'][] = $product_info;

		}

		$bundle_info = $this->set_bundle_products_array($bundles, $order);
		$data['produtos'] = array_merge($bundle_info['products'], $data['produtos']);
		$data['pedido']['desconto'] += $bundle_info['bundle_discount'];
		$data['pedido']['desconto'] = number_format($data['pedido']['desconto'], 2, '.', '' );
		$data['pedido'] = apply_filters( 'nfe_order_payment', $data['pedido'], $post_id );

		// Gateway Installments
		if ($payment_gateway && method_exists( "$payment_gateway", 'installments' )){

			$data = $payment_gateway::installments( $post_id, $data, $order, $args = [ 'total_discount' => $data['pedido']['desconto'] ] );

		}

		// Custom Installments
		if ($custom_installments = get_post_meta( $post_id, '_nfe_installments', true )){

			$data = NFeUtils::custom_installments( $post_id, $data, $order, $args = [ 'total_discount' => $data['pedido']['desconto'] ] );

		}

		// Payment
    if (
			$data['parcelas'] && 
			(
				(count($data['parcelas']) > 1) || 
				(count($data['parcelas']) == 1 && $data['parcelas'][0]['vencimento'] > current_time('Y-m-d'))
			)
		){

      $data['pedido']['pagamento'] = 1; // 1 - Pagamento a prazo

    } else {

      $data['pedido']['pagamento'] = 0; // 0 - Pagamento à vista

    }

	  // Courier
		$shipping_method = @array_shift($order->get_shipping_methods());
		$shipping_data = $shipping_method->get_data();
		$shipping_method_id = $shipping_method['method_id'];

		if (strpos($shipping_method_id, ':')){
			$shipping_method_id = substr($shipping_method['method_id'], 0, strpos($shipping_method['method_id'], ":"));
		}

		$include_shipping_info = get_option('wc_settings_woocommercenfe_transp_include');

		if ($include_shipping_info == 'on' && (isset($transportadoras[$shipping_method_id]) || $shipping_method_id == 'frenet')){

			// Frenet
			if ($shipping_method_id == 'frenet'){
				if (isset($shipping_data['meta_data']) && is_array($shipping_data['meta_data']) && $shipping_data['meta_data'][0]->key == 'FRENET_ID'){
					$shipping_method_id = $shipping_data['meta_data'][0]->value;
				}
			}

			// Courier data
			if ($shipping_method_id != 'frenet'){

				$transp = $transportadoras[$shipping_method_id];
			
				$data['transporte']['cnpj']         = $transp['cnpj'];
				$data['transporte']['razao_social'] = $transp['razao_social'];
				$data['transporte']['ie']           = $transp['ie'];
				$data['transporte']['endereco']     = $transp['address'];
				$data['transporte']['uf']           = $transp['uf'];
				$data['transporte']['cidade']       = $transp['city'];
				$data['transporte']['cep']          = $transp['cep'];

			}
			
		}

		// Product Volume and Weight
		if ($volume_weight = get_post_meta( $post_id, '_nfe_volume_weight', true )){

			$order_specifics = array(
				'volume' => '_nfe_transporte_volume',
				'especie' => '_nfe_transporte_especie',
				'peso_bruto' => '_nfe_transporte_peso_bruto',
				'peso_liquido' => '_nfe_transporte_peso_liquido'
			);
	
			foreach ($order_specifics as $api_key => $meta_key) {
	
				$value = $_POST[str_replace('_nfe_', '', $meta_key)];
	
				if (!isset($value)) 
					$value = get_post_meta($post_id, $meta_key, true);
	
				if ($value){
					$data['transporte'][$api_key] = $value;
				}
	
			}

		}

		// Preview
		if ($_POST['previa_danfe']){
			$data['previa_danfe'] = true;
		}

		// Return
		return apply_filters('nfe_order_data', $data, $post_id);

	}

	/**
	 * Mount Produtct Data
	 * 
	 * @return boolean
	 */
	function get_product_nfe_info($item, $order){

		global $wpdb;

		// Vars
		$product_id  = $item['product_id'];
		$product     = wc_get_product((($item['variation_id']) ? $item['variation_id'] : $item['product_id']));
		$codigo_gtin  = get_post_meta($product_id, '_nfe_codigo_ean', true);
		$gtin_tributavel = get_post_meta($product_id, '_nfe_gtin_tributavel', true);
		$codigo_ncm  = get_post_meta($product_id, '_nfe_codigo_ncm', true);
		$codigo_cest = get_post_meta($product_id, '_nfe_codigo_cest', true);
		$origem      = get_post_meta($product_id, '_nfe_origem', true);
		$imposto     = get_post_meta($product_id, '_nfe_classe_imposto', true);
		$ind_escala  = get_post_meta($product_id, '_nfe_ind_escala', true);
		$cnpj_fabricante = get_post_meta($product_id, '_nfe_cnpj_fabricante', true);
		$peso        = $product->get_weight();
		$informacoes_adicionais = '';
		$informacoes_adicionais = get_post_meta($product_id, '_nfe_produto_informacoes_adicionais', true);

		if (!$codigo_ncm){

			$product_cat = get_the_terms($product_id, 'product_cat');
			
			if (is_array($product_cat)) {
				foreach($product_cat as $cat){

					if (function_exists('get_term_meta')) {
						$ncm = get_term_meta($cat->term_id, '_ncm', true);
					}

		      if ($ncm) {
						$codigo_ncm = $ncm;
						break;
					}

		    }
			}

			if(!$codigo_ncm) 
				$codigo_ncm = get_option('wc_settings_woocommercenfe_ncm');

		}

		if (!$codigo_cest){
			$codigo_cest = get_option('wc_settings_woocommercenfe_cest');
		}

		if (!is_numeric($origem)){
			$origem = get_option('wc_settings_woocommercenfe_origem');
		}

		if (!$imposto){
			$imposto = get_option('wc_settings_woocommercenfe_imposto');
		}

		$variacoes = ''; //Used to append variation name to product name

		foreach (array_keys($item['item_meta']) as $meta){

			if (strpos($meta,'pa_') !== false) {

				$atributo = $item[$meta];
				$nome_atributo = str_replace( 'pa_', '', $meta );
				$nome_atributo = $wpdb->get_var( "SELECT attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = '$nome_atributo'" );
				$valor = strtoupper($item[$meta]);
				$variacoes .= ' - '.strtoupper($nome_atributo).': '.$valor;

			}

		}

		$product_active_price = $order->get_item_subtotal( $item, false, false );

		$info = array(
			'nome' => $item['name'].$variacoes, // Nome do produto
			'informacoes_adicionais' => ($informacoes_adicionais) ? $informacoes_adicionais : '', // Variações do produto
			'codigo' => ($product->get_sku()) ? $product->get_sku() : '', // Código do produto
			'gtin' => ($codigo_gtin) ? $codigo_gtin : '', // Código GTIN
			'gtin_tributavel' => ($gtin_tributavel) ? $gtin_tributavel : '',
			'ncm' => ($codigo_ncm) ? $codigo_ncm : '', // Código NCM
			'cest' => ($codigo_cest) ? $codigo_cest : '', // Código CEST
			'ind_escala' => ($ind_escala) ? $ind_escala : '', // Indicador de escala relevante
			'cnpj_fabricante' => ($cnpj_fabricante) ? $cnpj_fabricante : '', // CNPJ do fabricante da mercadoria
			'quantidade' => $item['qty'], // Quantidade de itens
			'unidade' => 'UN', // Unidade de medida da quantidade de itens
			'peso' => ($peso) ? $peso : '', // Peso em KG. Ex: 800 gramas = 0.800 KG
			'origem' => (int) $origem, // Origem do produto
			'subtotal' => number_format($product_active_price, 2, '.', '' ), // Preço unitário do produto - sem descontos
			'total' => number_format($product_active_price*$item['qty'], 2, '.', '' ), // Preço total (quantidade x preço unitário) - sem descontos
			'classe_imposto' => $imposto // Referência do imposto cadastrado
		);

		return apply_filters('nfe_order_data_product', $info, $order->get_id(), $product);

  }
	
	/**
	 * Mount Produtct Data
	 * 
	 * @return boolean
	 */
  function set_bundle_products_array( $bundles, $order){

		$total_bundle = 0;
		$total_products = 0;
		$bundle_products = array();

		foreach($bundles as $item){

			$product = wc_get_product($item['product_id']);
			$product_type = $product->get_type();
			$product_price = $product->get_price();
			$bundled_by = isset($item['bundled_by']);

			if(!$bundled_by && is_a($item, 'WC_Order_Item_Product')){
				$bundled_by = $item->meta_exists('_bundled_by');
			}

			$product_total = $product_price * $item['qty'];

			if ($bundled_by){

				$total_products += $product_total;

				if (!isset($bundle_products[$item['product_id']])) {

					$bundle_products[$item['product_id']] = $this->get_product_nfe_info($item, $order);
					$bundle_products[$item['product_id']]['subtotal'] = number_format($product_price, 2, '.', '' );
					$bundle_products[$item['product_id']]['total'] = number_format($product_total, 2, '.', '' );

				} else {

					$new_qty = ((int)$bundle_products[$item['product_id']]['quantidade']) + 1;
					$new_total = $new_qty * $product_price;
					$bundle_products[$item['product_id']]['quantidade'] = $new_qty;
					$bundle_products[$item['product_id']]['total'] = number_format($new_total, 2, '.', '' );

				}

			} elseif($product_type == 'yith_bundle') {

				$total_bundle += $product_price*$item['qty'];

			} elseif($product_type == 'mix-and-match') {

				$total_products_bundle = 0;
				$mnm_products_price = 0;
				$mnm_qty = $item['qty'];

				foreach ( $order->get_items() as $key => $item ) {
					// Get if the product belongs to Mix and Match to calculate discount
					$mnm_configs = wc_get_order_item_meta( $key, '_mnm_config', true);
					if ( $mnm_configs ) {
						// Get the total and quantity from Mix and Match bundle
						$mnm_product_price_total = wc_get_order_item_meta( $key, '_line_subtotal', true); // Get subtotal to aply coupon discount after, line_total aplies this discount before needed
						$mnm_product_price_qty = wc_get_order_item_meta( $key, '_qty', true);
						// Use the products inside the bundle
						foreach ( $mnm_configs as $mnm_config ) {
							// Load the product
							$product_mnm = wc_get_product( $mnm_config['product_id'] );
							// Store the discount from bundle
							$mnm_products_price += $product_mnm->get_price() * ( $mnm_config['quantity'] * $mnm_product_price_qty );
						}
						// Update total discount
						$mnm_products_price -= $mnm_product_price_total;
					}
				}

				$total_discount = $mnm_products_price;

			}

		}

		if ($total_products < $total_bundle && $product_type != 'mix-and-match') {

			end($bundle_products);
			$end_key = key($bundle_products);
			$subtotal_end = $bundle_products[$end_key]['subtotal'];
			$qty_end = $bundle_products[$end_key]['quantidade'];
			$diff = $total_bundle - $total_products;
			$add = $diff / $qty_end;
			$subtotal_end += $add;
			$bundle_products[$end_key]['subtotal'] = round($subtotal_end, 2);
			$bundle_products[$end_key]['total'] = $subtotal_end * $qty_end;
			$discount = 0;

		} else if($product_type == 'mix-and-match') {

			$discount = abs($total_discount);

		} else {

			$discount = abs($total_bundle - $total_products);

		}

		// Return
		return array('products' => $bundle_products, 'bundle_discount' => $discount);

	}

	/**
	 * Verify if shipping and billing
	 * informations are different
	 * 
	 * @return array
	**/
	public function compare_addresses($post_id, $envio_email) {

		$WooCommerceNFeFormat = new WooCommerceNFeFormat;

		$phone = (get_user_meta($post_id, 'billing_phone', true) ? get_user_meta($post_id, 'billing_phone', true) : get_post_meta($post_id, '_billing_phone', true));
		$email = ($envio_email && $envio_email == 'yes' ? get_post_meta($post_id, '_billing_email', true) : '');

		$billing = array(
			'endereco'    => get_post_meta($post_id, '_billing_address_1', true),
			'complemento' => get_post_meta($post_id, '_billing_address_2', true),
			'numero'      => get_post_meta($post_id, '_billing_number', true),
			'bairro'      => get_post_meta($post_id, '_billing_neighborhood', true),
			'cidade'      => get_post_meta($post_id, '_billing_city', true),
			'uf'          => get_post_meta($post_id, '_billing_state', true),
			'cep'         => $WooCommerceNFeFormat->cep(get_post_meta($post_id, '_billing_postcode', true)),
			'telefone'    => $phone,
			'email'       => $email
		);
		$shipping = array(
			'endereco'    => get_post_meta($post_id, '_shipping_address_1', true),
			'complemento' => get_post_meta($post_id, '_shipping_address_2', true),
			'numero'      => get_post_meta($post_id, '_shipping_number', true),
			'bairro'      => get_post_meta($post_id, '_shipping_neighborhood', true),
			'cidade'      => get_post_meta($post_id, '_shipping_city', true),
			'uf'          => get_post_meta($post_id, '_shipping_state', true),
			'cep'         => $WooCommerceNFeFormat->cep(get_post_meta($post_id, '_shipping_postcode', true)),
			'telefone'    => $phone,
			'email'       => $email
		);
		
		if ( $shipping['endereco'] == '' ) {
			$is_digital_order = $this->is_digital_order($post_id);

			if ( $is_digital_order ) {
				$tipo_pessoa_billing = $this->detect_persontype($post_id, '_billing');
				$billing = array_merge( $this->get_persontype_info($post_id, $tipo_pessoa_billing, '_billing'), $billing);
				
				$return['cliente'] = $billing;
				return $return;
			}
		}

		// Compare and return transporte->entrega if are different addressses
		if ( $billing === $shipping ) {

			// Detect persontype and merge informations
			$tipo_pessoa_shipping = $this->detect_persontype($post_id, '_shipping');
			$shipping = array_merge( $this->get_persontype_info($post_id, $tipo_pessoa_shipping, '_shipping'), $shipping);

			$return['cliente'] = $shipping;

		} else {

			// Detect persontype and merge informations
			$tipo_pessoa_billing = $this->detect_persontype($post_id, '_billing');
			$tipo_pessoa_shipping = $this->detect_persontype($post_id, '_shipping');

			$billing = array_merge( $this->get_persontype_info($post_id, $tipo_pessoa_billing, '_billing'), $billing);
			$shipping = array_merge( $this->get_persontype_info($post_id, $tipo_pessoa_shipping, '_shipping'), $shipping);

			$return['cliente'] = $billing;
			$return['transporte']['entrega'] = $shipping;
		}

		return $return;

	}

	/**
	 * Detect persontype from order
	 * 
	 * @return integer
	**/
	public function detect_persontype($post_id, $type = '_billing') {

		$tipo_pessoa = get_post_meta($post_id, $type.'_persontype', true);

		if ( !$tipo_pessoa && $type == '_shipping' ) {

			return 3;

		} elseif ( !$tipo_pessoa ) {

			if ( !empty(get_post_meta($post_id, $type.'_cpf', true)) ) {
				$tipo_pessoa = 1;
			} elseif ( !empty(get_post_meta($post_id, $type.'_cnpj', true)) ) {
				$tipo_pessoa = 2;
			}

			if (!$tipo_pessoa) 
				$tipo_pessoa = 1;

		}

		return $tipo_pessoa;

	}

	/**
	 * Get informations from persontype
	 * 
	 * @return array
	**/
	public function get_persontype_info($post_id, $persontype = 1, $type = '_billing') {

		$WooCommerceNFeFormat = new WooCommerceNFeFormat;

		if ( $persontype == 3 && $type == '_shipping' ) {
			$persontype = $this->detect_persontype($post_id, '_billing');
			$type = '_billing';
		}

		if ( $persontype == 1 ) {

			// Full name and CPF
			$person_info['nome_completo'] = get_post_meta($post_id, $type.'_first_name', true).' '.get_post_meta($post_id, $type.'_last_name', true);
			$person_info['cpf'] = $WooCommerceNFeFormat->cpf(get_post_meta($post_id, $type.'_cpf', true));

		} elseif ( $persontype == 2 ) {

			// Razao Social, CNPJ and IE
			$person_info['razao_social'] = get_post_meta($post_id, $type.'_company', true);
			$person_info['cnpj'] = $WooCommerceNFeFormat->cnpj(get_post_meta($post_id, $type.'_cnpj', true));
			$person_info['ie'] = str_replace(array('-','.',','), '', get_post_meta($post_id, $type.'_ie', true));

		}

		return $person_info;

	}

	/**
	 * Verify if is a digital order
	 * 
	 * @return boolean
	**/
	public function is_digital_order($order_id) {

		$order = wc_get_order( $order_id );

		foreach ($order->get_items() as $item_id => $item_data) {

			if ($variation_id = $item_data->get_variation_id()) {

				if (get_post_meta($variation_id, '_virtual', true) != 'yes') {
					return false;
				}

			} else {

				$product_id = $item_data->get_product_id();

				if (get_post_meta($product_id, '_virtual', true) != 'yes') {
					return false;
				}

			}

		}

		return true;

	}

	/**
	 * Add order id to list of automatic invoice errors
	 * 
	 * @return void
	 **/
	function add_id_to_invoice_errors( $message, $order_id ) {

		$ids_db = get_option('wmbr_auto_invoice_errors');
		$nfes = get_post_meta( $order_id, 'nfe', true );

		if ( !empty($nfes) && is_array($nfes) ) {
			foreach ( $nfes as $nfe ) {
				if ( $nfe['status'] == 'aprovado' ) {
						return false;
				}
			}
		}

		$ids_db[$order_id] = array(
			'datetime' => current_time("d-m-Y H:i:s"),
			'error' => $message
		);

		update_option( 'wmbr_auto_invoice_errors', $ids_db );

	}

	/**
	 * Remove order id to list of automatic invoice errors
	 * 
	 * @return void
	 **/
	function remove_id_to_invoice_errors( $order_id ) {

		$ids_db = get_option('wmbr_auto_invoice_errors');

		if ( is_array($ids_db) ) {
			
			if ( !array_key_exists($order_id, $ids_db) ) 
				return false;

			unset($ids_db[$order_id]);
			update_option( 'wmbr_auto_invoice_errors', $ids_db );

		}

	}

}