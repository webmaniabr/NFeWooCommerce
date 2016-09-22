<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WooCommerceNFe_Backend extends WooCommerceNFe {

    function add_settings_tab( $settings_tabs ){

        global $domain;

        $settings_tabs['woocommercenfe_tab'] = __( 'Nota Fiscal', $domain );
        return $settings_tabs;

    }

    function settings_tab(){

        woocommerce_admin_fields( self::get_settings() );

    }

    function update_settings(){

        woocommerce_update_options( self::get_settings() );

    }

    function get_settings(){

        global $domain;

        $settings = array(
            'title' => array(
                'name'     => __( 'Credenciais de Acesso', $domain ),
                'type'     => 'title',
                'desc'     => 'Informe os acessos da sua aplicação.'
            ),
            'consumer_key' => array(
                'name' => __( 'Consumer Key', $domain ),
                'type' => 'text',
                'css' => 'width:300px;',
                'id'   => 'wc_settings_woocommercenfe_consumer_key'
            ),
            'consumer_secret' => array(
                'name' => __( 'Consumer Secret', $domain ),
                'type' => 'text',
                'css' => 'width:300px;',
                'id'   => 'wc_settings_woocommercenfe_consumer_secret'
            ),
            'access_token' => array(
                'name' => __( 'Access Token', $domain ),
                'type' => 'text',
                'css' => 'width:300px;',
                'id'   => 'wc_settings_woocommercenfe_access_token'
            ),
            'access_token_secret' => array(
                'name' => __( 'Access Token Secret', $domain ),
                'type' => 'text',
                'css' => 'width:300px;',
                'id'   => 'wc_settings_woocommercenfe_access_token_secret'
            ),
            'ambiente' => array(
                'name' => __( 'Ambiente Sefaz', $domain ),
                'type' => 'radio',
                'options' => array('1' => 'Produção', '2' => 'Desenvolvimento'),
                'default' => '2',
                'id'   => 'wc_settings_woocommercenfe_ambiente'
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_woocommercenfe_end'
            ),
            'title2' => array(
                'name'     => __( 'Configuração Padrão', $domain ),
                'type'     => 'title',
                'desc'     => 'A configuração padrão será utilizada para todos os produtos.<br>Caso deseje a configuração também pode ser personalizada em cada produto.'
            ),
            'emissao_automatica' => array(
                'name' => __( 'Emissão automática', $domain ),
                'type' => 'checkbox',
                'desc' => __( 'Emitir automaticamente a NF-e sempre que um pagamento for confirmado.', $domain ),
                'id'   => 'wc_settings_woocommercenfe_emissao_automatica',
            ),
            'natureza_operacao' => array(
                'name' => __( 'Natureza da Operação', $domain ),
                'type' => 'text',
                'css' => 'width:300px;',
                'id'   => 'wc_settings_woocommercenfe_natureza_operacao'
            ),
            'imposto' => array(
                'name' => __( 'Classe de Imposto', $domain ),
                'type' => 'text',
                'id'   => 'wc_settings_woocommercenfe_imposto'
            ),
            'ean' => array(
                'name' => __( 'Código de Barras EAN', $domain ),
                'type' => 'text',
                'id'   => 'wc_settings_woocommercenfe_ean'
            ),
            'ncm' => array(
                'name' => __( 'Código NCM', $domain ),
                'type' => 'text',
                'id'   => 'wc_settings_woocommercenfe_ncm'
            ),
            'cest' => array(
                'name' => __( 'Código CEST', $domain ),
                'type' => 'text',
                'id'   => 'wc_settings_woocommercenfe_cest'
            ),
            'origem' => array(
                'name' => __( 'Origem dos Produtos', $domain ),
                'type' => 'select',
                'options' => array(
                    'null' => 'Selecionar Origem dos Produtos',
                    '0' => '0 - Nacional, exceto as indicadas nos códigos 3, 4, 5 e 8',
                    '1' => '1 - Estrangeira - Importação direta, exceto a indicada no código 6',
                    '2' => '2 - Estrangeira - Adquirida no mercado interno, exceto a indicada no código 7',
                    '3' => '3 - Nacional, mercadoria ou bem com Conteúdo de Importação superior a 40% e inferior ou igual a 70%',
                    '4' => '4 - Nacional, cuja produção tenha sido feita em conformidade com os processos produtivos básicos de que tratam as legislações citadas nos Ajustes',
                    '5' => '5 - Nacional, mercadoria ou bem com Conteúdo de Importação inferior ou igual a 40%',
                    '6' => '6 - Estrangeira - Importação direta, sem similar nacional, constante em lista da CAMEX e gás natural',
                    '7' => '7 - Estrangeira - Adquirida no mercado interno, sem similar nacional, constante lista CAMEX e gás natural',
                    '8' => '8 - Nacional, mercadoria ou bem com Conteúdo de Importação superior a 70%'
                ),
                'css' => 'width:300px;',
                'id'   => 'wc_settings_woocommercenfe_origem'
            ),
            'section_end2' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_woocommercenfe_end2'
            ),
						'title4' => array(
								'name'     => __( 'Informações Complementares (Opcional)', $domain ),
								'type'     => 'title',
								'desc'     => 'Informações fiscais complementares.'
						),
						'fisco_inf' => array(
                'name' => __( 'Informações ao Fisco', $domain ),
                'type' => 'textarea',
                'id'   => 'wc_settings_woocommercenfe_fisco_inf',
								'class' => 'nfe_textarea',
            ),
						'cons_inf' => array(
                'name' => __( 'Informações Complementares ao Consumidor', $domain ),
                'type' => 'textarea',
                'id'   => 'wc_settings_woocommercenfe_cons_inf',
								'class' => 'nfe_textarea',
            ),
						'section_end3' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_woocommercenfe_end3'
            ),
            'title5' => array(
                'name'     => __( 'Campos Personalizados no Checkout', $domain ),
                'type'     => 'title',
                'desc'     => 'Informe se deseja mostrar os campos na página de Finalizar Compra.'
            ),
            'tipo_pessoa' => array(
                'name' => __( 'Exibir Tipo de Pessoa', $domain ),
                'type' => 'checkbox',
                'desc' => __( 'Caso esteja marcado exibe os campos de Tipo de Pessoa, CPF, CNPJ e Empresa nas informações de cobrança.', $domain ),
                'id'   => 'wc_settings_woocommercenfe_tipo_pessoa',
                'default' => 'yes',
            ),
            'mascara_campos' => array(
                'name' => __( 'Habilitar Máscara de Campos', $domain ),
                'type' => 'checkbox',
                'desc' => __( 'Caso esteja marcado adiciona máscaras de preenchimento para os campos de CPF e CNPJ.', $domain ),
                'id'   => 'wc_settings_woocommercenfe_mascara_campos',
                'default' => 'yes',
            ),
            'cep' => array(
                'name' => __( 'Preenchimento automático do Endereço', $domain ),
                'type' => 'checkbox',
                'desc' => __( 'Caso esteja marcado o endereço será automaticamente preenchido quando o usuário informar o CEP.', $domain ),
                'id'   => 'wc_settings_woocommercenfe_cep',
                'default' => 'yes',
            ),
            'section_end4' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_woocommercenfe_end4'
            ),
        );

        return $settings;

    }

		function register_metabox_nfe_emitida() {

        add_meta_box(
            'woocommernfe_nfe_emitida',
            'NF-e do Pedido',
            array('WooCommerceNFe_Backend', 'metabox_content_woocommernfe_nfe_emitida'),
            'shop_order',
            'normal',
            'high'
        );

    }

		function atualizar_status_nota() {

			if(!is_admin()){
				return false;
			}

			if($_GET['atualizar_nfe'] && $_GET['post'] && $_GET['chave']){

				$post_id = (int) sanitize_text_field($_GET['post']);
				$chave = sanitize_text_field($_GET['chave']);
				$webmaniabr = new NFe(WC_NFe()->settings);
				$response = $webmaniabr->consultaNotaFiscal($chave);

				if (isset($response->error)){

            WC_NFe()->add_error( __('Erro: '.$response->error, $domain) );
            return false;

        }else{

					$new_status = $response->status;
					$nfe_data = get_post_meta($post_id, 'nfe', true);

					foreach($nfe_data as &$order_nfe){
						if($order_nfe['chave_acesso'] == $chave){
							$order_nfe['status'] = $new_status;
						}
					}

					update_post_meta($post_id, 'nfe', $nfe_data);
					WC_NFe()->add_success( 'NF-e atualizada com sucesso' );
				}

			}

		}

		function metabox_content_woocommernfe_nfe_emitida( $post ) {
			$nfe_data = get_post_meta($post->ID, 'nfe', true);
			if(empty($nfe_data)): ?>
			<p>Nenhuma nota emitida para este pedido</p>
			<?php else: ?>
				<div class="all-nfe-info">
					<div class="head">
						<h4 class="head-title">Data</h4>
						<h4 class="head-title n-column">Nº</h4>
						<h4 class="head-title danfe-column">Danfe</h4>
						<h4 class="head-title status-column">Status</h4>
					</div>
					<div class="body">
						<?php foreach($nfe_data as $order_nfe): ?>
							<div class="single">
								<div>
								<h4 class="body-info"><?php echo $order_nfe['data'] ?></h4>
								<h4 class="body-info n-column"><?php echo $order_nfe['n_nfe'] ?></h4>
								<h4 class="body-info danfe-column"><a class="unstyled" target="_blank" href="<?php echo $order_nfe['url_danfe'] ?>"><span class="wrt">Visualizar Nota</span><span class="dashicons dashicons-media-text danfe-icon"></span></a></h4>
								<?php
									$post_url = get_edit_post_link($post->ID);
									$update_url = $post_url.'&atualizar_nfe=true&chave='.$order_nfe['chave_acesso'];
									
								?>
								<h4 class="body-info status-column"><span class="nfe-status <?php echo $order_nfe['status']; ?>"><?php echo $order_nfe['status']; ?></span><a class="unstyled" href="<?php echo $update_url; ?>"><span class="dashicons dashicons-image-rotate update-nfe"></span></a></h4></div>
								<div class="extra">
									<ul>
										<li><strong>RPS:</strong> <?php echo $order_nfe['n_recibo'] ?></li>
										<li><strong>Série:</strong> <?php echo $order_nfe['n_serie'] ?></li>
										<li><strong>Arquivo XML:</strong> <a target="_blank" href="<?php echo $order_nfe['url_xml'] ?>">Download XML</a></li>
										<li><strong>Código Verificação:</strong> <?php echo $order_nfe['chave_acesso'] ?></li>
									</ul>
								</div>
								<span class="dashicons dashicons-arrow-down-alt2 expand-nfe"></span>
							</div>




						<?php endforeach; ?>
					</div>
				</div>

 		<?php endif;

		}

    function register_metabox_listar_nfe() {

        add_meta_box(
            'woocommernfe_informacoes',
            'Informações Fiscais (Opcional)',
            array('WooCommerceNFe_Backend', 'metabox_content_woocommernfe_informacoes'),
            'product',
            'side',
            'high'
        );

    }

    function metabox_content_woocommernfe_informacoes( $post ){

        global $domain;

?>
<div class="inside" style="padding:0!important;">
		<div class="field">
				<p class="label" style="margin-bottom:8px;">
						<label style="font-size:13px;line-height:1.5em;font-weight:bold;">Ignorar Produto ao emitir NFe</label>
				</p>
				<input type="checkbox" name="ignorar_nfe" value="1" <?php if(get_post_meta( $post->ID, '_nfe_ignorar_nfe', true ) == 1) echo 'checked'; ?> >
		</div>
    <div class="field">
        <p class="label" style="margin-bottom:8px;">
            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">Classe de Imposto</label>
        </p>
        <input type="text" name="classe_imposto" value="<?php echo get_post_meta( $post->ID, '_nfe_classe_imposto', true ); ?>" style="width:100%;padding:5px;">
    </div>
    <div class="field">
        <p class="label" style="margin-bottom:8px;">
            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">Código de Barras EAN</label>
        </p>
        <input type="text" name="codigo_ean" value="<?php echo get_post_meta( $post->ID, '_nfe_codigo_ean', true ); ?>" style="width:100%;padding:5px;">
    </div>
    <div class="field">
        <p class="label" style="margin-bottom:8px;">
            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">Código NCM</label>
        </p>
        <input type="text" name="codigo_ncm" value="<?php echo get_post_meta( $post->ID, '_nfe_codigo_ncm', true ); ?>" style="width:100%;padding:5px;">
    </div>
    <div class="field">
        <p class="label" style="margin-bottom:8px;">
            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">Código CEST</label>
        </p>
        <input type="text" name="codigo_cest" value="<?php echo get_post_meta( $post->ID, '_nfe_codigo_cest', true ); ?>" style="width:100%;padding:5px;">
    </div>
    <div class="field">
        <p class="label" style="margin-bottom:8px;">
            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">Origem</label>
        </p>
        <?php
          $origem = get_post_meta( $post->ID, '_nfe_origem', true );
        ?>
        <select name="origem">
            <option value="null" <?php if (!is_numeric($origem)) echo 'selected'; ?> ><?php _e( 'Selecionar Origem do Produto', $domain ); ?></option>
            <option value="0" <?php if (is_numeric($origem) && $origem == 0) echo 'selected'; ?> ><?php _e( '0 - Nacional, exceto as indicadas nos códigos 3, 4, 5 e 8', $domain ); ?></option>
            <option value="1" <?php if ($origem == 1) echo 'selected'; ?> ><?php _e( '1 - Estrangeira - Importação direta, exceto a indicada no código 6', $domain ); ?></option>
            <option value="2" <?php if ($origem == 2) echo 'selected'; ?> ><?php _e( '2 - Estrangeira - Adquirida no mercado interno, exceto a indicada no código 7', $domain ); ?></option>
            <option value="3" <?php if ($origem == 3) echo 'selected'; ?> ><?php _e( '3 - Nacional, mercadoria ou bem com Conteúdo de Importação superior a 40% e inferior ou igual a 70%', $domain ); ?></option>
            <option value="4" <?php if ($origem == 4) echo 'selected'; ?> ><?php _e( '4 - Nacional, cuja produção tenha sido feita em conformidade com os processos produtivos básicos de que tratam as legislações citadas nos Ajustes', $domain ); ?></option>
            <option value="5" <?php if ($origem == 5) echo 'selected'; ?> ><?php _e( '5 - Nacional, mercadoria ou bem com Conteúdo de Importação inferior ou igual a 40%', $domain ); ?></option>
            <option value="6" <?php if ($origem == 6) echo 'selected'; ?> ><?php _e( '6 - Estrangeira - Importação direta, sem similar nacional, constante em lista da CAMEX e gás natural', $domain ); ?></option>
            <option value="7" <?php if ($origem == 7) echo 'selected'; ?> ><?php _e( '7 - Estrangeira - Adquirida no mercado interno, sem similar nacional, constante lista CAMEX e gás natural' ,$domain ); ?></option>
            <option value="8" <?php if ($origem == 8) echo 'selected'; ?> ><?php _e( '8 - Nacional, mercadoria ou bem com Conteúdo de Importação superior a 70%', $domain ); ?></option>
       </select>
    </div>

</div>
<?php

    }


	function add_order_status_column_header( $columns ) {

		$new_columns = array();

		foreach ( $columns as $column_name => $column_info ) {

			$new_columns[ $column_name ] = $column_info;

			if ( 'order_status' == $column_name ) {

				$new_columns['nfe'] = __( 'Status NF-e' );

			}
		}

		return $new_columns;
	}

	function add_order_status_column_content( $column ) {
		global $post;

		if ( 'nfe' == $column ) {

			$nfe = get_post_meta( $post->ID, 'nfe', true );
			$order = new WC_Order( $post->ID );

            if ($order->get_status() == 'pending' || $order->get_status() == 'cancelled') echo '<span class="nfe_none">-</span>';
			elseif ($nfe) echo '<div class="nfe_success">NF-e Emitida</div>';
			else echo '<div class="nfe_alert">NF-e não emitida</div>';

		}

	}

	function add_order_meta_box_actions( $actions ) {

		if (get_option( 'sefaz' ) == 'offline') return false;
		$actions['wc_nfe_emitir'] = __( 'Emitir NF-e' );
		return $actions;

	}

	function add_order_bulk_actions() {
		global $post_type, $post_status;

		if ( $post_type == 'shop_order' ) {

			if (get_option( 'sefaz' ) == 'offline') return false;
			if ($post_status == 'trash' || $post_status == 'wc-cancelled' || $post_status == 'wc-pending') return false;

			?>
			<script type="text/javascript">
				jQuery( document ).ready( function ( $ ) {
						  var $emitir_nfe = $('<option>').val('wc_nfe_emitir').text('<?php _e( 'Emitir NF-e' ); ?>');
						  $( 'select[name^="action"]' ).append( $emitir_nfe );
					  });
			</script>
			<?php

		}
	}

	function style(){

		?>
		<style>
		.nfe_alert { display: inline; padding: .2em .6em .3em; font-size: 11px; font-weight: 700; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: .25em; background-color: #d9534f; }
		.nfe_success { display: inline; padding: .2em .6em .3em; font-size: 11px; font-weight: 700; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: .25em;  background-color: #5cb85c; }
		.nfe_none { color: #999; text-align:center; }
		.nfe_danfe { padding: 0px 12px 2px; border: 1px solid #CCC; margin-top: 5px; float: left; }
		.nfe_danfe:hover { background:#FFF; }
		.nfe_danfe a { color: #333; text-transform: uppercase; font-weight: bold; font-size: 11px; }
		.nfe_textarea{ min-width: 300px; min-height: 100px; }
		</style>
		<?php

	}

	function process_order_bulk_actions(){

		global $typenow;

		if ( 'shop_order' == $typenow ) {

			$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
			$action        = $wp_list_table->current_action();

			if ( ! in_array( $action, array( 'wc_nfe_emitir') ) ) return false;
			if ( isset( $_REQUEST['post'] ) ) $order_ids = array_map( 'absint', $_REQUEST['post'] );
			if ( empty( $order_ids ) ) return false;

			if ($action == 'wc_nfe_emitir') WC_NFe()->emitirNFe( $order_ids );

		}

	}

	function process_order_meta_box_actions( $post ){

		$order_id = $post->id;
		$post_status = $post->post_status;
		if ($post_status == 'trash' || $post_status == 'wc-cancelled') return false;

		parent::emitirNFe( array( $order_id ) );

	}

    function scripts(){

        wp_register_script( 'woocommercenfe_admin_script', apply_filters( 'woocommercenfe_plugins_url', plugins_url( 'assets/js/admin_scripts.js', __FILE__ ) ) );
        wp_register_style( 'woocommercenfe_admin_style', apply_filters( 'woocommercenfe_plugins_url', plugins_url( 'assets/css/admin_style.css', __FILE__ ) ) );

        wp_enqueue_style( 'woocommercenfe_admin_style' );
        wp_enqueue_script( 'woocommercenfe_admin_script' );

    }

    function customer_meta_fields( $fields ) {

        global $domain;

		// Billing fields.
		$new_fields['billing']['title'] = __( 'Endereço de Cobrança', $domain );
		$new_fields['billing']['fields']['billing_first_name'] = $fields['billing']['fields']['billing_first_name'];
		$new_fields['billing']['fields']['billing_last_name']  = $fields['billing']['fields']['billing_last_name'];
        $new_fields['billing']['fields']['billing_cpf'] = array(
            'label' => __( 'CPF', $domain ),
            'description' => ''
        );
        $new_fields['billing']['fields']['billing_cnpj'] = array(
            'label' => __( 'CNPJ', $domain ),
            'description' => ''
        );
        $new_fields['billing']['fields']['billing_company'] = $fields['billing']['fields']['billing_company'];
        $new_fields['billing']['fields']['billing_ie'] = array(
            'label' => __( 'Inscrição Estadual', $domain ),
            'description' => ''
        );
        $new_fields['billing']['fields']['billing_birthdate'] = array(
            'label' => __( 'Nascimento', $domain ),
            'description' => ''
        );
        $new_fields['billing']['fields']['billing_sex'] = array(
            'label' => __( 'Sexo', $domain ),
            'description' => ''
        );

		$new_fields['billing']['fields']['billing_address_1'] = $fields['billing']['fields']['billing_address_1'];
		$new_fields['billing']['fields']['billing_number'] = array(
			'label' => __( 'Número', $domain ),
			'description' => ''
		);
		$new_fields['billing']['fields']['billing_address_2'] = $fields['billing']['fields']['billing_address_2'];
		$new_fields['billing']['fields']['billing_neighborhood'] = array(
			'label' => __( 'Bairro', $domain ),
			'description' => ''
		);
		$new_fields['billing']['fields']['billing_city']     = $fields['billing']['fields']['billing_city'];
		$new_fields['billing']['fields']['billing_postcode'] = $fields['billing']['fields']['billing_postcode'];
		$new_fields['billing']['fields']['billing_country']  = $fields['billing']['fields']['billing_country'];
		$new_fields['billing']['fields']['billing_state']    = $fields['billing']['fields']['billing_state'];
		$new_fields['billing']['fields']['billing_phone']    = $fields['billing']['fields']['billing_phone'];

		if ( isset( $settings['cell_phone'] ) ) {
			$new_fields['billing']['fields']['billing_cellphone'] = array(
				'label' => __( 'Celular', $domain ),
				'description' => ''
			);
		}

		$new_fields['billing']['fields']['billing_email'] = $fields['billing']['fields']['billing_email'];

		// Shipping fields.
		$new_fields['shipping']['title'] = __( 'Customer Shipping Address', $domain );
		$new_fields['shipping']['fields']['shipping_first_name'] = $fields['shipping']['fields']['shipping_first_name'];
		$new_fields['shipping']['fields']['shipping_last_name']  = $fields['shipping']['fields']['shipping_last_name'];
		$new_fields['shipping']['fields']['shipping_company']    = $fields['shipping']['fields']['shipping_company'];
		$new_fields['shipping']['fields']['shipping_address_1']  = $fields['shipping']['fields']['shipping_address_1'];
		$new_fields['shipping']['fields']['shipping_number'] = array(
			'label' => __( 'Número', $domain ),
			'description' => ''
		);
		$new_fields['shipping']['fields']['shipping_address_2']  = $fields['shipping']['fields']['shipping_address_2'];
		$new_fields['shipping']['fields']['shipping_neighborhood'] = array(
			'label' => __( 'Bairro', $domain ),
			'description' => ''
		);
		$new_fields['shipping']['fields']['shipping_city']     = $fields['shipping']['fields']['shipping_city'];
		$new_fields['shipping']['fields']['shipping_postcode'] = $fields['shipping']['fields']['shipping_postcode'];
		$new_fields['shipping']['fields']['shipping_country']  = $fields['shipping']['fields']['shipping_country'];
		$new_fields['shipping']['fields']['shipping_state']    = $fields['shipping']['fields']['shipping_state'];

		return $new_fields;

	}

    function user_column_billing_address( $address, $user_id ) {
		$address['number']       = get_user_meta( $user_id, 'billing_number', true );
		$address['neighborhood'] = get_user_meta( $user_id, 'billing_neighborhood', true );

		return $address;
	}

    function user_column_shipping_address( $address, $user_id ) {
		$address['number']       = get_user_meta( $user_id, 'shipping_number', true );
		$address['neighborhood'] = get_user_meta( $user_id, 'shipping_neighborhood', true );

		return $address;
	}

    function shop_order_billing_fields( $data ) {

        global $domain;

		$billing_data['first_name'] = array(
			'label' => __( 'Nome', $domain ),
			'show'  => false
		);
		$billing_data['last_name'] = array(
			'label' => __( 'Sobrenome', $domain ),
			'show'  => false
		);
        $billing_data['persontype'] = array(
            'type'    => 'select',
            'label'   => __( 'Tipo Pessoa', $domain ),
            'options' => array(
                '0' => __( 'Selecionar', $domain ),
                '1' => __( 'Pessoa Física', $domain ),
                '2' => __( 'Pessoa Jurídica', $domain )
            ),
            'show'  => false
        );
        $billing_data['cpf'] = array(
            'label' => __( 'CPF', $domain ),
            'show'  => false
        );
        $billing_data['cnpj'] = array(
            'label' => __( 'CNPJ', $domain ),
            'show'  => false
        );
        $billing_data['ie'] = array(
            'label' => __( 'Inscrição Estadual', $domain ),
            'show'  => false
        );
        $billing_data['company'] = array(
            'label' => __( 'Empresa', $domain ),
        );
        $billing_data['birthdate'] = array(
            'label' => __( 'Nascimento', $domain ),
            'show'  => false
        );
        $billing_data['sex'] = array(
            'label' => __( 'Sexo', $domain ),
            'show'  => false
        );
		$billing_data['address_1'] = array(
			'label' => __( 'Endereço', $domain ),
			'show'  => false
		);
		$billing_data['number'] = array(
			'label' => __( 'Número', $domain ),
			'show'  => false
		);
		$billing_data['address_2'] = array(
			'label' => __( 'Complemento', $domain ),
			'show'  => false
		);
		$billing_data['neighborhood'] = array(
			'label' => __( 'Bairro', $domain ),
			'show'  => false
		);
		$billing_data['city'] = array(
			'label' => __( 'Cidade', $domain ),
			'show'  => false
		);
		$billing_data['state'] = array(
			'label' => __( 'Estado', $domain ),
			'show'  => false
		);
		$billing_data['country'] = array(
			'label'   => __( 'País', $domain ),
			'show'    => false,
			'type'    => 'select',
			'options' => array(
				'' => __( 'Selecione um País&hellip;', $domain )
			) + WC()->countries->get_allowed_countries()
		);
		$billing_data['postcode'] = array(
			'label' => __( 'CEP', $domain ),
			'show'  => false
		);

		$billing_data['phone'] = array(
			'label' => __( 'Telefone Fixo', $domain ),
		);

		if ( isset( $settings['cell_phone'] ) ) {
			$billing_data['cellphone'] = array(
				'label' => __( 'Celular', $domain ),
			);
		}

		$billing_data['email'] = array(
			'label' => __( 'E-mail', $domain ),
		);


		return $billing_data;

	}

    function shop_order_shipping_fields( $data ) {

        $shipping_data['first_name'] = array(
			'label' => __( 'Nome', $domain ),
			'show'  => false
		);
		$shipping_data['last_name'] = array(
			'label' => __( 'Sobrenome', $domain ),
			'show'  => false
		);
		$shipping_data['company'] = array(
			'label' => __( 'Empresa', $domain ),
			'show'  => false
		);
		$shipping_data['address_1'] = array(
			'label' => __( 'Endereço', $domain ),
			'show'  => false
		);
		$shipping_data['number'] = array(
			'label' => __( 'Número', $domain ),
			'show'  => false
		);
		$shipping_data['address_2'] = array(
			'label' => __( 'Complemento', $domain ),
			'show'  => false
		);
		$shipping_data['neighborhood'] = array(
			'label' => __( 'Bairro', $domain ),
			'show'  => false
		);
		$shipping_data['city'] = array(
			'label' => __( 'Cidade', $domain ),
			'show'  => false
		);
		$shipping_data['state'] = array(
			'label' => __( 'Estado', $domain ),
			'show'  => false
		);
		$shipping_data['country'] = array(
			'label'   => __( 'País', $domain ),
			'show'    => false,
			'type'    => 'select',
			'options' => array(
				'' => __( 'Selecione um País&hellip;', $domain )
			) + WC()->countries->get_allowed_countries()
		);
		$shipping_data['postcode'] = array(
			'label' => __( 'CEP', $domain ),
			'show'  => false
		);

		return $shipping_data;

	}

    function customer_details_ajax( $customer_data ) {

        $user_id      = (int) trim( stripslashes( $_POST['user_id'] ) );
		$type_to_load = esc_attr( trim( stripslashes( $_POST['type_to_load'] ) ) );

		$custom_data = array(
			$type_to_load . '_number'       => get_user_meta( $user_id, $type_to_load . '_number', true ),
			$type_to_load . '_neighborhood' => get_user_meta( $user_id, $type_to_load . '_neighborhood', true ),
			$type_to_load . '_persontype'   => get_user_meta( $user_id, $type_to_load . '_persontype', true ),
			$type_to_load . '_cpf'          => get_user_meta( $user_id, $type_to_load . '_cpf', true ),
			$type_to_load . '_cnpj'         => get_user_meta( $user_id, $type_to_load . '_cnpj', true ),
			$type_to_load . '_ie'           => get_user_meta( $user_id, $type_to_load . '_ie', true ),
			$type_to_load . '_birthdate'    => get_user_meta( $user_id, $type_to_load . '_birthdate', true ),
			$type_to_load . '_sex'          => get_user_meta( $user_id, $type_to_load . '_sex', true ),
			$type_to_load . '_cellphone'    => get_user_meta( $user_id, $type_to_load . '_cellphone', true )
		);

		return array_merge( $customer_data, $custom_data );
	}

    function save_custom_shop_data( $post_id ) {

		update_post_meta( $post_id, '_billing_number', woocommerce_clean( $_POST['_billing_number'] ) );
		update_post_meta( $post_id, '_billing_neighborhood', woocommerce_clean( $_POST['_billing_neighborhood'] ) );
		update_post_meta( $post_id, '_shipping_number', woocommerce_clean( $_POST['_shipping_number'] ) );
		update_post_meta( $post_id, '_shipping_neighborhood', woocommerce_clean( $_POST['_shipping_neighborhood'] ) );
        update_post_meta( $post_id, '_billing_persontype', woocommerce_clean( $_POST['_billing_persontype'] ) );
        update_post_meta( $post_id, '_billing_cpf', woocommerce_clean( $_POST['_billing_cpf'] ) );
        update_post_meta( $post_id, '_billing_cnpj', woocommerce_clean( $_POST['_billing_cnpj'] ) );
        update_post_meta( $post_id, '_billing_ie', woocommerce_clean( $_POST['_billing_ie'] ) );
        update_post_meta( $post_id, '_billing_birthdate', woocommerce_clean( $_POST['_billing_birthdate'] ) );
		update_post_meta( $post_id, '_billing_sex', woocommerce_clean( $_POST['_billing_sex'] ) );
        update_post_meta( $post_id, '_billing_cellphone', woocommerce_clean( $_POST['_billing_cellphone'] ) );

	}

    function save_informacoes_fiscais( $post_id ){

        if (get_post_type($post_id) == 'product'){

            if ($_POST['classe_imposto']) update_post_meta( $post_id, '_nfe_classe_imposto', $_POST['classe_imposto'] );
            if ($_POST['codigo_ean']) update_post_meta( $post_id, '_nfe_codigo_ean', $_POST['codigo_ean'] );
            if ($_POST['codigo_ncm']) update_post_meta( $post_id, '_nfe_codigo_ncm', $_POST['codigo_ncm'] );
            if ($_POST['codigo_cest']) update_post_meta( $post_id, '_nfe_codigo_cest', $_POST['codigo_cest'] );
						if ($_POST['ignorar_nfe']){
							update_post_meta( $post_id, '_nfe_ignorar_nfe', $_POST['ignorar_nfe'] );
						}else{
							update_post_meta( $post_id, '_nfe_ignorar_nfe', 0 );
						}
            if (is_numeric($_POST['origem']) || $_POST['origem']) update_post_meta( $post_id, '_nfe_origem', $_POST['origem'] );

        }

    }

}
