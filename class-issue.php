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
  function send( $order_ids = array(), $is_massa = false, $is_auto_invoice = false ){

		$result = array();

		foreach ($order_ids as $order_id) {

			// Data
			$order = wc_get_order( $order_id );
			$data = $this->order_data( $order_id );

			// Async
			if ($is_massa && isset($data['nfe'])) {
				$data['nfe']['assincrono'] = 1;
			}

			do_action('nfe_before_response', $data, $order_id);

			$this->get_credentials( $order_id );
			$webmaniabr = new NFe($this->settings);
			$webmaniabr_nfse = new NFSe($this->settings['bearer_access_token']);

			if (isset($data['nfe'])) {

				$response = $webmaniabr->emissaoNotaFiscal( $data['nfe'] );
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
					$is_auto_invoice && $this->send_error_email( $mensagem, $order_id );
					$this->add_id_to_invoice_errors( $invoice_error, $order_id );
					$this->add_error( $mensagem );
	
				} else {
	
					if ( is_object($response) && ($response->status == 'processamento') ) {
	
						$this->add_success( 'Nota Fiscal do pedido nº '.$order_id.' em processamento. O status será atualizado assim que a NF-e for processada pela Sefaz.' );
	
					} else {
	
						if ( is_object($response) && isset($data['nfe']['previa_danfe']) ) {
	
							$this->add_success( 'Pré-visualizar Danfe: <a href="'.$response->danfe.'" target="_blank">'.$response->danfe.'</a>' );
	
						} else {
	
							$this->add_success( 'Nota Fiscal do pedido nº '.$order_id.' gerada com sucesso.' );
							$this->remove_id_to_invoice_errors($order_id);
	
						}
					
					}
	
				}
	
				// If API respond with status, register 'NF-e'
				if ( is_object($response) && $response->status ) {

					$order = wc_get_order( $order_id );
					$nfe = $order->get_meta( 'nfe', true );
					$nfe_doc = $data['nfe']['cliente']['cpf'] ?? $data['nfe']['cliente']['cnpj'];
					$nfe_doc = str_replace(['.', '-', '/'], '', $nfe_doc);
	
					if (!$nfe)
						$nfe = array();
	
					// Identify NFe repeated
					if (count($nfe) > 0){
						$is_repeated = false;
						foreach ($nfe as $nf){
	
							if ($nf['chave_acesso'] == $response->chave){
	
								$is_repeated = true;
								break;
	
							}
	
						}
	
						if ($is_repeated) {
							continue;
						}
					}
	
					// Register new NFe
					$nfe[] = array(
						'uuid'   => (string) $response->uuid,
						'status' => (string) $response->status,
						'modelo' => 'nfe',
						'chave_acesso' => $response->chave,
						'n_recibo' => (int) isset($response->recibo) ? $response->recibo : '',
						'n_nfe' => (int) $response->nfe,
						'n_serie' => (int) $response->serie,
						'nfe_doc' => (string) $nfe_doc,
						'url_xml' => (string) $response->xml,
						'url_danfe' => (string) $response->danfe,
						'url_danfe_simplificada' => (string) $response->danfe_simples,
						'url_danfe_etiqueta' => (string) $response->danfe_etiqueta,
						'data' => date_i18n('d/m/Y'),
					);

					$order->update_meta_data('nfe', $nfe);
					$order->save();
	
				}
			}

			if (isset($data['nfse'])) {
				$response = $webmaniabr_nfse->emissaoNFSe($data['nfse']);
				$result[] = $response;

				if (isset($response->error) || isset($response->errors) || $response->status == 'reprovado') {

					$error_message = $response->error;
					if (!$error_message) {
						$error_message = (array) $response->errors;
						if (!empty($error_message)) $error_message = reset($error_message);
					}
					if (!$error_message) $error_message = $response->motivo;
					$mensagem = 'Erro ao emitir a NFS-e do Pedido #'.$order_id.':';
					$mensagem .= '<ul style="padding-left:20px;">';
					if (is_array($error_message)) {
						foreach ($error_message as $error_message_item) $mensagem .= '<li>'.$error_message_item.'</li>';
					}
					else {
						$mensagem .= '<li>'.$error_message.'</li>';
					}
					$mensagem .= '</ul>';
	
					$is_auto_invoice && $this->send_error_email( $mensagem, $order_id );
					$this->add_id_to_invoice_errors( (is_array($error_message) ? implode(' | ', $error_message) : $error_message), $order_id );
					$this->add_error( $mensagem );
	
				} else {
	
					if ( (is_object($response) && ($response->status == 'processando')) || $response->modelo == 'lote_rps' ) {
	
						$this->add_success( 'Nota Fiscal de Serviço do pedido nº '.$order_id.' em processamento. O status será atualizado assim que a NFS-e for processada pela Prefeitura.' );
	
					} else if ($response->status == 'aprovado') {
	
						$this->add_success( 'Nota Fiscal de Serviço do pedido nº '.$order_id.' gerada com sucesso.' );
						$this->remove_id_to_invoice_errors($order_id);
					
					}
	
				}
	
				// If API respond with status, register 'NFS-e'
				if ( is_object($response) && $response->status ) {
	
					$order = wc_get_order( $order_id );
					$nfe = $order->get_meta( 'nfe', true ); 
	
					if (!$nfe)
						$nfe = array();
	
					// Identify NFSe repeated
					if (count($nfe) > 0){
						$is_repeated = false;
						foreach ($nfe as $nf){
	
							if ($nf['uuid'] == $response->uuid){
	
								$is_repeated = true;
								break;
	
							}
	
						}
	
						if ($is_repeated) {
							continue;
						}
					}
	
					// Register new NFe
					$nfe[] = array(
						'uuid'   => (string) $response->uuid,
						'status' => (string) $response->status,
						'modelo' => (string) $response->modelo,
						'n_nfe' => (int) $response->numero ?: $response->numero_lote,
						'n_serie' => "{$response->serie_rps}:{$response->numero_rps}",
						'url_xml' => (string) $response->xml,
						'url_pdf' => (string) $response->pdf_nfse ?? '', 
						'pdf_rps' => (string) $response->pdf_rps ?? '',
						'data' => date_i18n('d/m/Y'),
					);
	
					$order->update_meta_data( 'nfe', $nfe );
					$order->save();
	
				}
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
	
		$order = wc_get_order( $post_id );
		$products = [];
		$services = [];

		foreach ($order->get_items() as $item) {
			$product_id = $item->get_product_id();
			if (get_post_meta($product_id, '_nfe_tipo_produto', true) == 2) $services[] = $item;
			else $products[] = $item;
		}

		$data = [];
		if (count($products) > 0) $data['nfe'] = $this->mount_nfe_data($post_id, $products);
		if (count($services) > 0) $data['nfse'] = $this->mount_nfse_data($post_id, $services);

		if (isset($data['nfe']) && isset($data['nfse'])) {
			foreach ($data['nfse']['rps'] as $rps) 
				$data['nfe']['pedido']['total'] -= $rps['servico']['valor_servicos'];
		}

		return $data;

	}

	/**
	 * Mount NF-e Data
	 *
	 * @return boolean
	 */
	function mount_nfe_data( $post_id, $products ){

		global $wpdb;

		// Vars
		$payment_methods = get_option('wc_settings_woocommercenfe_payment_methods', array());
		$payment_descs = get_option('wc_settings_woocommercenfe_payment_descs', array());
		$payment_keys = array_keys($payment_methods);
		$order = wc_get_order( $post_id );
		$default_imposto = get_option('wc_settings_woocommercenfe_imposto');
		$default_ncm = get_option('wc_settings_woocommercenfe_ncm');
		$default_cest = get_option('wc_settings_woocommercenfe_cest');
		$default_origem = get_option('wc_settings_woocommercenfe_origem');
		$transportadoras = get_option('wc_settings_woocommercenfe_transportadoras', array());
		$envio_email = get_option('wc_settings_woocommercenfe_envio_email');
		$coupons = method_exists($order, 'get_coupon_codes') ? $order->get_coupon_codes() : false;
		$coupons_percentage = array();
		$total_discount = $total_fee = 0;
		$fee_aditional_informations = '';
		$natureza_operacao = '';
		$payment_gateway = UtilsGateways::get_gateway_class( $order->get_payment_method() );

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
		$modalidade_frete = isset($_POST['modalidade_frete'])? $_POST['modalidade_frete']: $order->get_meta( '_nfe_modalidade_frete' );

		if (!$modalidade_frete || $modalidade_frete == 'null')
			 $modalidade_frete = 0;

		$order_key = $order->get_order_key();

		// Order Operation
		$natureza_operacao = ($order->get_meta( '_nfe_natureza_operacao_pedido' )) ? $order->get_meta( '_nfe_natureza_operacao_pedido' ) : get_option('wc_settings_woocommercenfe_natureza_operacao');

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
					$total_discount += $discount;

				} else {

					if ( $fee_aditional_informations != '' )
						$fee_aditional_informations .= ' / ';

					$fee_aditional_informations .= $item['name'] . ': R$' . number_format($item['line_total'], 2, ',', '');
					$fee = $item['line_total'];
					$total_fee += $fee;

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
			'frete'            => $order->get_shipping_total(), // Total do frete
			'desconto'         => number_format($total_discount, 2, '.', '' ), // Total do desconto
			'total'            => $order->get_total() // Total do pedido - sem descontos
		);

		//Intermediador da operação
		$intermediador = $_POST['nfe_info_intermediador'] ?? $order->get_meta( '_nfe_info_intermediador' ) ?? '';
		if ($intermediador) {
			$data['pedido']['intermediador'] = (!empty($_POST)) ? $_POST['nfe_info_intermediador_type'] : $order->get_meta( '_nfe_info_intermediador_type' );
			$data['pedido']['cnpj_intermediador'] = (!empty($_POST)) ? $_POST['nfe_info_intermediador_cnpj'] : $order->get_meta( '_nfe_info_intermediador_cnpj' );
			$data['pedido']['id_intermediador'] = (!empty($_POST)) ? $_POST['nfe_info_intermediador_id'] : $order->get_meta( '_nfe_info_intermediador_id' );
		}
		else {
			$data['pedido']['intermediador'] = get_option('wc_settings_woocommercenfe_intermediador');
			$data['pedido']['cnpj_intermediador'] = get_option('wc_settings_woocommercenfe_cnpj_intermediador');
			$data['pedido']['id_intermediador'] = get_option('wc_settings_woocommercenfe_id_intermediador');
		}

		if ( $total_fee && $total_fee > 0 ) {
			$data['pedido']['despesas_acessorias'] = number_format($total_fee, 2, '.', '');
		}

		// Set Payment Method
		if ($payment_gateway && method_exists( "$payment_gateway", 'payment_type' )){

			$data = $payment_gateway::payment_type( $post_id, $order, $data );

		} elseif ( isset($payment_methods[$order->get_payment_method()]) && $payment_methods[$order->get_payment_method()] ) {

			$data['pedido']['forma_pagamento'] = [ $payment_methods[$order->get_payment_method()] ];
			$data['pedido']['desc_pagamento'] = [$payment_descs[$order->get_payment_method()]];

		} else {

			$data['pedido']['forma_pagamento'] = '99'; // 99 - Outros
			$data['pedido']['desc_pagamento'] = 'Pagamento Digital';

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
		if ($additional_info = (!empty($_POST) && isset($_POST['nfe_additional_info'])) ? $_POST['nfe_additional_info'] : $order->get_meta( '_nfe_additional_info' )) {
			$value = $_POST['nfe_additional_info_text'];

			if (!isset($value)) {
				$value = $order->get_meta( '_nfe_additional_info_text' );
			}
			$consumidor_inf .= ' ' . $value;
		}

		if(!empty($consumidor_inf) && strlen($consumidor_inf) <= 2000){
			$data['pedido']['informacoes_complementares'] = $consumidor_inf;
		}

		// Customer
		if ($data['modelo'] == 2){

			$customer_cpf = $order->get_meta( 'billing_cpf' );
			$customer_cnpj = $order->get_meta( 'billing_cnpj' );

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

		// Contribuinte ICMS
		$contribuinte = (!empty($_POST) && isset($_POST['nfe_contribuinte'])) ? $_POST['nfe_contribuinte'] : $order->get_meta( '_nfe_contribuinte' );
		if (in_array($contribuinte, [1, 2, 9])) {
			$data['cliente']['contribuinte'] = $contribuinte;
		}

		// Products
		$bundles = array();

		if (!isset($data['produtos']))
			$data['produtos'] = array();

		foreach ($products as $key => $item){
			
			$product      = wc_get_product($item->get_product_id());
			$product_type = $product->get_type();
			$product_id   = $item->get_product_id();
			$bundled_by   = isset($item['bundled_by']);
			$variation_description = $beneficio_fiscal = ''; 

			if(!$bundled_by && is_a($item, 'WC_Order_Item_Product')){
				$bundled_by = $item->meta_exists('_bundled_by');
			}

			$variation_id = $item['variation_id'];
			if (!empty($variation_id)) {
				$variation = new WC_Product_Variation($variation_id);
				$attributes = $variation->get_attributes();

				foreach ($attributes as $name => $value) {
					$label = wc_attribute_label($name, $product);
					if ($value) {
						if ($variation_description) {
							$variation_description .= ', ';
						}
						$variation_description .= mb_strtoupper("{$label} : {$value}");
					}
				}
			}

			if ( $product_type == 'bundle' || $product_type == 'yith_bundle' || $product_type == 'mix-and-match' || $bundled_by ){
				$bundles[] = $item;
				continue;
			}

			$product_info = $this->get_product_nfe_info($item, $order);
			$ignore_product = apply_filters( 'nfe_order_product_ignore', $order->get_meta( '_nfe_ignorar_nfe' ), $product_id, $post_id);

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
			$beneficio_fiscal = $order->get_meta( '_nfe_beneficio_fiscal_pedido' );

			if ( isset($_POST['beneficio_fiscal_pedido']) && $_POST['beneficio_fiscal_pedido'] != '' && $_POST['beneficio_fiscal_pedido'] != $beneficio_fiscal ) {
				$beneficio_fiscal = $_POST['beneficio_fiscal_pedido'];
			}

			if ($beneficio_fiscal){
				$product_info['beneficio_fiscal'] = ($beneficio_fiscal) ? $beneficio_fiscal : '';
			}
			if ($variation_description){
				$product_info['informacoes_adicionais'] .= ($variation_description) ? $variation_description : '';
			}

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
		if ($custom_installments = $order->get_meta( '_nfe_installments' )){

			$data = NFeUtils::custom_installments( $post_id, $data, $order, $args = [ 'total_discount' => $data['pedido']['desconto'] ] );

		}

		// Payment
    if (
			isset($data['parcelas']) &&
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

		if ($shipping_method || $data['pedido']['modalidade_frete'] != 9) {
      
      $shipping_data = ($shipping_method) ? $shipping_method->get_data() : array();
		  $shipping_method_id = ($shipping_method) ? $shipping_method['method_id'] : '';
      
			// Common Shipping
			if (strpos($shipping_method_id, ':')){
				$shipping_method_id = substr($shipping_method['method_id'], 0, strpos($shipping_method['method_id'], ":"));
			}

			// Frenet shipping
			if ($shipping_method_id == 'frenet' && isset($shipping_data['meta_data']) && is_array($shipping_data['meta_data']) && $shipping_data['meta_data'][0]->key == 'FRENET_ID'){
				$shipping_method_id = $shipping_data['meta_data'][0]->value;
			}

			// Carrier
			if (!empty($transportadoras) && $transportadoras[$shipping_method_id]){

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
		if ($volume_weight = (!empty($_POST) && isset($_POST['nfe_volume_weight'])) ? $_POST['nfe_volume_weight'] : $order->get_meta( '_nfe_volume_weight' )){

			$order_specifics = array(
				'volume' => '_nfe_transporte_volume',
				'especie' => '_nfe_transporte_especie',
				'peso_bruto' => '_nfe_transporte_peso_bruto',
				'peso_liquido' => '_nfe_transporte_peso_liquido'
			);

			foreach ($order_specifics as $api_key => $meta_key) {

				$value = $_POST[str_replace('_nfe_', '', $meta_key)] ?? '';

				if (!isset($value) && !empty($value))
					$value = $order->get_meta( $meta_key );

				if ($value){
					$data['transporte'][$api_key] = $value;
				}
				if($value)$order->update_meta_data($meta_key, $value);

			}
			$order->save();

		}

		// Preview
		if (isset($_POST['previa_danfe'])){
			$data['previa_danfe'] = true;
		}

		// Return
		return apply_filters('nfe_order_data', $data, $post_id);

	}

	/**
	 * Mount NFS-e Data
	 * 
	 * @return array
	 */
	function mount_nfse_data($post_id, $services) {

		// Vars
		$order = wc_get_order( $post_id );
		$coupons = method_exists($order, 'get_coupon_codes') ? $order->get_coupon_codes() : false;
		$coupons_percentage = array();
		$total_discount = $total_fee = $total_value = 0;
		$envio_email = get_option('wc_settings_woocommercenfe_envio_email');
		$compare_addresses = $this->compare_addresses($order->get_id(), $envio_email);
		$tomador = $compare_addresses['cliente'];
		$tomador = array_filter($tomador, function($var) { return !empty($var); });

		// Init data
		$data = array(
			'ID' => $post_id,
			'origem' => 'woocommerce',
			'url_notificacao' => get_bloginfo('url').'/wc-api/nfse_callback?order_key='.$order->get_order_key().'&order_id='.$post_id,
			'ambiente' => apply_filters( 'nfe_environment', ( isset($_POST['emitir_homologacao']) && $_POST['emitir_homologacao'] ? '2' : (int) get_option('wc_settings_woocommercenfe_ambiente') ), $post_id ),
			'rps' => []
		);

		// Coupons
		if ($coupons){
			foreach($coupons as $coupon_code){
				$coupon_obj = new WC_Coupon($coupon_code);
				if($coupon_obj->discount_type == 'percent'){
					$coupons_percentage[] = $coupon_obj->coupon_amount;
				}
			}
		}

		// Fees
		if ($order->get_fees()){

			foreach ($order->get_fees() as $key => $item){

				if ($item['line_total'] < 0){

					$discount = abs($item['line_total']);
					$total_discount += $discount;

				}

			}

		}

		$total_discount = $order->get_total_discount() + $total_discount;

		// Mount Services
		$services_info = [];
		foreach ($services as $item) {

			$product_id  = $item->get_product_id();
			$ignore_product = apply_filters( 'nfe_order_product_ignore', get_post_meta($product_id, '_nfe_ignorar_nfe', true), $product_id, $post_id);

			if (!$ignore_product){

				$product = wc_get_product((($item['variation_id']) ? $item['variation_id'] : $item->get_product_id()));
				$classe_imposto = get_post_meta($product_id, '_nfe_classe_imposto', true) ?: get_option('wc_settings_woocommercenfe_imposto_nfse');
				$service_info = [
					'descricao' => $item['name'],
					'quantidade' => $item['qty'],
					'total' => number_format($order->get_item_subtotal( $item, false, false )*$item['qty'], 2, '.', '' ),
				];
				if (array_key_exists($classe_imposto, $services_info)) $services_info[$classe_imposto][] = $service_info;
				else $services_info[$classe_imposto] = [$service_info];

			}
			
		}

		// Mount RPS
		if (count($services_info) > 0){
			foreach ($services_info as $key => $item) {

				$valor_servicos = 0;
				$discriminacao = '';
	
				foreach ($item as $key2 => $service) {

					$valor_servicos += $service['total'];
					if ($key2 != 0) $discriminacao .= ' | ';
					$discriminacao .= $service['descricao'] . ' - Qtd.: ' . $service['quantidade'] . ' - R$' . $service['total'];

				}
				
				if (get_option('wc_settings_woocommercenfe_incluir_taxas_nfse') && $order->get_total_fees() > 0) {
					$valor_servicos += $order->get_total_fees();
					$discriminacao .= ' - Taxas: R$'. number_format($order->get_total_fees());
				}
	
				// Service additional information
				$servico_inf = get_option('wc_settings_woocommercenfe_servico_inf');
				if ($service_info = (!empty($_POST) && isset($_POST['nfe_service_info'])) ? $_POST['nfe_service_info'] : $order->get_meta( '_nfe_service_info' )) {
					$value = $_POST['nfe_service_info_text'];

					if (!isset($value)) {
						$value = $order->get_meta( '_nfe_service_info_text' );
					}
					$servico_inf .= ' ' . $value;
				}
				if (!empty($servico_inf)) $discriminacao .= ' - ' . $servico_inf;

				// Discount
				$tipo_desconto = $_POST['tipo_desconto'];
				if (empty($tipo_desconto)) $tipo_desconto = $order->get_meta( '_nfse_tipo_desconto' );
				if (empty($tipo_desconto)) $tipo_desconto = get_option('wc_settings_woocommercenfe_tipo_desconto_nfse');
				if (empty($tipo_desconto)) $tipo_desconto = 1;

				// Calculate discount and total value
				$valor_servicos = number_format($valor_servicos, 2, '.', '' );
				$discount = number_format(($total_discount/count($services_info)), 2, '.', '' );
				if ($discount && $discount > 0 && $tipo_desconto == 3) {
					$valor_servicos = number_format($valor_servicos-$discount, 2, '.', '');
				}

				$data['rps'][] = [
					'servico' => [
						'valor_servicos' => $valor_servicos,
						'discriminacao' => $discriminacao,
						'desconto_incondicionado' => ($tipo_desconto == 1) ? $discount : 0,
						'desconto_condicionado' => ($tipo_desconto == 2) ? $discount : 0,
						'classe_imposto' => $key
					],
					'tomador' => $tomador
				];
	
			}
		}

		// Return
		return $data;

	}

	/**
	 * Mount Produtct Data
	 *
	 * @return boolean
	 */
	function get_product_nfe_info($item, $order){

		global $wpdb;

		// Vars
		$product_id  = $item->get_product_id();
		$product     = wc_get_product((($item['variation_id']) ? $item['variation_id'] : $item->get_product_id()));
		$codigo_gtin  = get_post_meta($product_id, '_nfe_codigo_ean', true);
		$gtin_tributavel = get_post_meta($product_id, '_nfe_gtin_tributavel', true);
		$codigo_ncm  = get_post_meta($product_id, '_nfe_codigo_ncm', true);
		$codigo_cest = get_post_meta($product_id, '_nfe_codigo_cest', true);
		$origem      = get_post_meta($product_id, '_nfe_origem', true);
		$imposto     = get_post_meta($product_id, '_nfe_classe_imposto', true);
		$ind_escala  = get_post_meta($product_id, '_nfe_ind_escala', true);
		$cnpj_fabricante = get_post_meta($product_id, '_nfe_cnpj_fabricante', true);
		$unidade     = get_post_meta($product_id, '_nfe_unidade', true);
		$peso        = $product->get_weight();
		$weightUnit = get_option('woocommerce_weight_unit');
		if ($peso && $weightUnit == 'g') $peso = $peso/1000;
		$informacoes_adicionais = '';
		$informacoes_adicionais = get_post_meta($product_id, '_nfe_produto_informacoes_adicionais', true);

		//Get NCM from product variation
		$variation_id = $item['variation_id'];
		if ($variation_id) {
		
			$codigo_ncm = ($ncm = get_post_meta($variation_id, 'variable_ncm', true)) ? $ncm : $codigo_ncm;
		
		}

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

		$product_active_price = $order->get_item_subtotal( $item, false, false );

		$info = array(
			'nome' => $item['name'], // Nome do produto
			'informacoes_adicionais' => ($informacoes_adicionais) ? $informacoes_adicionais : '', // Variações do produto
			'codigo' => ($product->get_sku()) ? $product->get_sku() : '', // Código do produto
			'gtin' => ($codigo_gtin) ? $codigo_gtin : '', // Código GTIN
			'gtin_tributavel' => ($gtin_tributavel) ? $gtin_tributavel : '',
			'ncm' => ($codigo_ncm) ? $codigo_ncm : '', // Código NCM
			'cest' => ($codigo_cest) ? $codigo_cest : '', // Código CEST
			'ind_escala' => ($ind_escala) ? $ind_escala : '', // Indicador de escala relevante
			'cnpj_fabricante' => ($cnpj_fabricante) ? $cnpj_fabricante : '', // CNPJ do fabricante da mercadoria
			'quantidade' => $item->get_quantity(), // Quantidade de itens
			'unidade' => $unidade ? $unidade : 'UN', // Unidade de medida da quantidade de itens
			'peso' => ($peso) ? $peso : '', // Peso em KG. Ex: 800 gramas = 0.800 KG
			'origem' => (int) $origem, // Origem do produto
			'subtotal' => number_format($product_active_price, 2, '.', '' ), // Preço unitário do produto - sem descontos
			'total' => number_format($product_active_price*$item->get_quantity(), 2, '.', '' ), // Preço total (quantidade x preço unitário) - sem descontos
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

		if (empty($bundles) || !is_array($bundles)) return array('products' => [], 'bundle_discount' => 0);

		$total_bundle = 0;
		$total_products = 0;
		$bundle_products = array();
		$product_type = '';

		foreach($bundles as $item){

			$product = wc_get_product($item->get_product_id());
			$product_type = $product->get_type();
			$product_price = $product->get_price();
			$bundled_by = isset($item['bundled_by']);

			if(!$bundled_by && is_a($item, 'WC_Order_Item_Product')){
				$bundled_by = $item->meta_exists('_bundled_by');
			}

			$product_total = $product_price * $item->get_quantity();

			if ($bundled_by){

				$total_products += $product_total;

				if (!isset($bundle_products[$item->get_product_id()])) {

					$bundle_products[$item->get_product_id()] = $this->get_product_nfe_info($item, $order);
					$bundle_products[$item->get_product_id()]['subtotal'] = number_format($product_price, 2, '.', '' );
					$bundle_products[$item->get_product_id()]['total'] = number_format($product_total, 2, '.', '' );

				} else {

					$new_qty = ((int)$bundle_products[$item->get_product_id()]['quantidade']) + 1;
					$new_total = $new_qty * $product_price;
					$bundle_products[$item->get_product_id()]['quantidade'] = $new_qty;
					$bundle_products[$item->get_product_id()]['total'] = number_format($new_total, 2, '.', '' );

				}

			} elseif($product_type == 'yith_bundle' || $product_type == 'bundle') {

				$total_bundle += $product_price*$item->get_quantity();

			} elseif($product_type == 'mix-and-match') {

				$total_products_bundle = 0;
				$mnm_products_price = 0;
				$mnm_qty = $item->get_quantity();

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
            $discount = ($total_bundle == 0 && $total_products > 0) ? 0 : $discount;

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

		$order = wc_get_order( $post_id );
		$phone = (get_user_meta($post_id, 'billing_phone', true) ? get_user_meta($post_id, 'billing_phone', true) : $order->get_meta( '_billing_phone' ));
		$email = ($envio_email && $envio_email == 'yes' ? $order->get_meta( '_billing_email' ) : '');

		$billing = array(
			'endereco'    => $order->get_meta( '_billing_address_1' ),
			'complemento' => $order->get_meta( '_billing_address_2' ),
			'numero'      => $order->get_meta( '_billing_number' ),
			'bairro'      => $order->get_meta( '_billing_neighborhood' ),
			'cidade'      => $order->get_meta( '_billing_city' ),
			'uf'          => $order->get_meta( '_billing_state' ),
			'cep'         => $WooCommerceNFeFormat->cep($order->get_meta( '_billing_postcode' )),
			'telefone'    => $phone,
			'email'       => $email,
			'pais'        => $order->get_meta( '_billing_country' )
		);
		$shipping = array(
			'endereco'    => $order->get_meta( '_shipping_address_1' ),
			'complemento' => $order->get_meta( '_shipping_address_2' ),
			'numero'      => $order->get_meta( '_shipping_number' ),
			'bairro'      => $order->get_meta( '_shipping_neighborhood' ),
			'cidade'      => $order->get_meta( '_shipping_city' ),
			'uf'          => $order->get_meta( '_shipping_state' ),
			'cep'         => $WooCommerceNFeFormat->cep($order->get_meta( '_shipping_postcode' )),
			'telefone'    => $phone,
			'email'       => $email,
			'pais'        => $order->get_meta( '_shipping_country' )
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
			$tipo_pessoa_billing = $this->detect_persontype($post_id, '_billing');
			$billing = array_merge( $this->get_persontype_info($post_id, $tipo_pessoa_billing, '_billing'), $billing);

			$return['cliente'] = $billing;

		} else {

			// Detect persontype and merge informations
			$tipo_pessoa_billing = $this->detect_persontype($post_id, '_billing');
			$tipo_pessoa_shipping = $this->detect_persontype($post_id, '_shipping');

			$billing = array_merge( $this->get_persontype_info($post_id, $tipo_pessoa_billing, '_billing'), $billing);
			$shipping = array_merge( $this->get_persontype_info($post_id, $tipo_pessoa_shipping, '_shipping'), $shipping);

						$return['cliente'] = $billing;
						$return['transporte']['entrega'] = $shipping;
		}

		//Foreign customer NFS-e
		if (!empty($return['cliente']['pais'])) {
			$pais = $return['cliente']['pais'];
			if ($pais != 'BR') {
				//Remove address fields
				unset($return['cliente']['endereco']);
				unset($return['cliente']['complemento']);
				unset($return['cliente']['numero']);
				unset($return['cliente']['bairro']);
				unset($return['cliente']['cidade']);
				unset($return['cliente']['uf']);
				unset($return['cliente']['cep']);

				$return['cliente']['sigla_pais'] = $pais;
				if (isset($return['cliente']['nome_completo'])) {
					$return['cliente']['nome_estrangeiro'] = $return['cliente']['nome_completo'];
					unset($return['cliente']['cpf']);
					unset($return['cliente']['nome_completo']);
				}
				else if (isset($return['cliente']['razao_social'])) {
					$return['cliente']['nome_estrangeiro'] = $return['cliente']['razao_social'];
					unset($return['cliente']['cnpj']);
					unset($return['cliente']['razao_social']);
				}
			}
			unset($return['cliente']['pais']);
		}

		return $return;

	}

	/**
	 * Detect persontype from order
	 *
	 * @return integer
	**/
	public function detect_persontype($post_id, $type = '_billing') {

		$order = wc_get_order( $post_id );
		$tipo_pessoa = $order->get_meta( $type.'_persontype' );

		if ( !$tipo_pessoa && $type == '_shipping' ) {

			return 3;

		} elseif ( !$tipo_pessoa ) {

			if ( !empty($order->get_meta( $type.'_cpf' )) ) {
				$tipo_pessoa = 1;
			} elseif ( !empty($order->get_meta( $type.'_cnpj' )) ) {
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

		$order = wc_get_order( $post_id );
		$WooCommerceNFeFormat = new WooCommerceNFeFormat;

		if ( $persontype == 3 && $type == '_shipping' ) {
			$persontype = $this->detect_persontype($post_id, '_billing');
			$type = '_billing';
		}

		if ( $persontype == 1 ) {

			// Full name and CPF
			$person_info['nome_completo'] = $order->get_meta( $type.'_first_name' ).' '.$order->get_meta( $type.'_last_name' );
			$person_info['cpf'] = $WooCommerceNFeFormat->cpf($order->get_meta( $type.'_cpf' ));

		} elseif ( $persontype == 2 ) {

			// Razao Social, CNPJ and IE
			$person_info['razao_social'] = $order->get_meta( $type.'_company', true);
			$person_info['cnpj'] = $WooCommerceNFeFormat->cnpj($order->get_meta( $type.'_cnpj' ));
			$person_info['ie'] = str_replace(array('-','.',','), '', $order->get_meta( $type.'_ie' ));

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
		$order = wc_get_order( $order_id );
		$nfes = $order->get_meta( 'nfe', true );

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

    /**
     * Prevent orders for only ignored products from issuing NFE
     *
     * @return boolean
     **/
    function is_only_ignored_items( $post_id ) {

        $order = wc_get_order( $post_id );
        $items = $order->get_items();

        // If automatic issue, ignore orders with only ignored items
		foreach($items as $item){
			$ignore_item = apply_filters( 'nfe_order_product_ignore', get_post_meta($item['product_id'], '_nfe_ignorar_nfe', true), $item['product_id'], $post_id);
			if ($ignore_item != 1){
				return false;
			}
		}

        return true;
	}

}
