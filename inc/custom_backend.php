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
        woocommerce_admin_fields( $this->get_settings() );
				$transportadoras = get_option('wc_settings_woocommercenfe_transportadoras', array());
				?>

				<style>
					.nfe-shipping-label{
						min-width: 120px;
						display: inline-block;
					}
					.nfe-table-body,
					.nfe-table-head{
						overflow: hidden;
					}
					.nfe-table-head{
						border-bottom: 1px solid #e5e5e5;
					}
					.nfe-table-head h4{
						margin-top: 0;
						margin-bottom: 15px;
					}
					.nfe-table-head--payment{
						padding-bottom: 20px;
    				padding-top: 10px;
					}
					.nfe-table-head--payment > div,
					.nfe-table-body--payment .entry > div{
						width: 30%;
						display: inline-block;
						vertical-align: middle;
					}
					.nfe-table-head--payment > div h4{
						margin-bottom: 0;
					}
					.nfe-table-head--payment > div h4 span{
						font-size: 12px;
    				color: #696969;
					}
					.shipping-method-col-title{
						float:left;
						width: 30%;
					}
					.shipping-info-col-title{
						float:left;
						width: 70%;
					}
					.nfe-shipping-table{
						background: #FFF;
				    border: 1px solid #e5e5e5;
				    padding: 15px;
					}
					.nfe-shipping-table .entry{
						margin-top: 15px;
						border-bottom: 1px solid #e5e5e5;
						overflow: hidden;
						position: relative;
					}
					.nfe-shipping-table.payment-info{
						padding: 5px;
					}
					.nfe-shipping-table.payment-info .entry{
						border-bottom: 0;
						padding-left: 10px;
					}
					.nfe-shipping-table.payment-info .entry:nth-child(even){
						background-color:#efefef;
					}
					.nfe-shipping-table .nfe-table-body .entry:first-child{
						display: none;
					}
					.shipping-method-col{
						display: inline-block;
    				width: 30%;
    				float: left;
					}
					.shipping-info-col{
						display: inline-block;
    				width: 70%;
    				float: left;
					}
					.nfe-shipping-methods-sel{
						max-width: 80%;
					}
					#wmbr-add-shipping-info{
						margin-top: 15px;
					}
					.wmbr-remove-shipping-info,
					.wmbr-remove-shipping-info:active{
						  position: absolute;
					    right: 15px;
					    top: 50%;
					    transform: translate(0, -50%)!important;
					    background-color: #e25050!important;
					    color: #FFF!important;
					    border: 0;
					}
					.wmbr-remove-shipping-info span{
						vertical-align: middle;
				    position: relative;
				    top: -2px;
					}
					.cert_ajax_success, .cert_ajax_error {
					    background: white;
					    padding: 10px;
					}
					.cert_ajax_success {
					    border-left: 4px solid #46b450;
					}
					.cert_ajax_error {
					    border-left: 4px solid #dc3232;
					}
				</style>

				<h3>Certificado Digital A1</h3>
				<?php
					add_action( 'admin_footer', array($this, 'force_digital_certificate_update') );
					$certificate = json_decode($this->validadeCertificado(false, true));
					echo '<span id="update-digital-certificate-response">';
						if ( isset($certificate->status) && $certificate->status == 'success' ) {
							echo '<h4 class="cert_ajax_success">Faltam ' . $certificate->msg . ' dias para o Certificado Digital A1 expirar.</h4>';
						} elseif ( isset($certificate->status) && $certificate->status == 'error' ) {
							echo '<h4 class="cert_ajax_error">Certificado Digital A1 expirado</h4>';
						} elseif ( isset($certificate->status) && $certificate->status == 'null_credentials' ) {
							echo '<h4 class="cert_ajax_error">'.$certificate->msg.'</h4>';
						} else {
							echo '<h4 class="cert_ajax_error">Não foi possível atualizar seu Certificado Digital A1. Por favor, solicite suporte para <a target="_blank" href="mailto:suporte@webmaniabr.com">suporte@webmaniabr.com</a>.</h4>';
						}
					echo '</span>';
				?>

				<button type="button" class="button-primary" id="update-digital-certificate">Atualizar manualmente</button>

				<h3>Informações de Transportadoras</h3>
				<p>Cadastre as transportadoras particulares utilizadas em sua loja virtual para identificação na nota fiscal eletrônica. Observação: Para o transporte dos Correios não há necessidade de preenchimento dos dados.</p>
				<table class="form-table">
					<tr valign="top">
						<th scope="row" class="title-desc">Incluir informações na NF-e</th>
						<td class="forminp forminp-checkbox">
							<fieldset>
								<label for="wc_settings_woocommercenfe_transp_include">
									<?php $include = get_option('wc_settings_woocommercenfe_transp_include'); ?>
								<input name="wc_settings_woocommercenfe_transp_include" id="wc_settings_woocommercenfe_transp_include" type="checkbox" class="" value="1" <?php if($include == 'on') echo 'checked="checked"'; ?>> Marque este campo caso deseje inserir dados das transportadoras na NF-e.						</label>
							</fieldset>
						</td>
					</tr>
				</table>

				<div class="nfe-shipping-table">
					<div class="nfe-table-head">
						<h4 class="shipping-method-col-title">Método de Entrega</h4>
						<h4 class="shipping-info-col-title">Informações da Transportadora</h4>
					</div>
					<div class="nfe-table-body">
						<div class="entry">
							<div class="shipping-method-col">
								<?php echo $this->get_shipping_methods_select(); ?>
							</div>
							<div class="shipping-info-col">
									<p>Se for pessoa jurídica, preencher os campos de Razão Social, CNPJ e Inscrição Estadual. Se for pessoa física, preencher os campos CPF e Nome Completo.</p>
									<p><label class="nfe-shipping-label">Razão Social: </label><input type="text" name="shipping_info_rs_0"/></p>
									<p><label class="nfe-shipping-label">CNPJ:</label> <input type="text" name="shipping_info_cnpj_0"/></p>
									<p><label class="nfe-shipping-label">Inscrição estadual:</label> <input type="text" name="shipping_info_ie_0"/></p>
									<p><label class="nfe-shipping-label">CPF:</label> <input type="text" name="shipping_info_cpf_0"/></p>
									<p><label class="nfe-shipping-label">Nome Completo:</label> <input type="text" name="shipping_info_name_0"/></p>
									<p><label class="nfe-shipping-label">Endereço:</label> <input type="text" name="shipping_info_address_0"/></p>
									<p><label class="nfe-shipping-label">CEP:</label> <input type="text" name="shipping_info_cep_0"/></p>
									<p><label class="nfe-shipping-label">Cidade:</label> <input type="text" name="shipping_info_city_0"/></p>
									<p><label class="nfe-shipping-label">UF:</label> <input type="text" name="shipping_info_uf_0"/></p>
									<p><label class="nfe-shipping-label">Placa do Veículo:</label> <input type="text" name="shipping_info_plate_0"/></p>
									<p><label class="nfe-shipping-label">UF do Veículo:</label> <input type="text" name="shipping_info_uf_vehicle_0"/></p>
							</div>
							<button type="button" class="button wmbr-remove-shipping-info"><span class="dashicons dashicons-no"></span> Remover</button>
						</div>
						<?php echo $this->get_transportadoras_entries(); ?>
					</div>
					<button type="button" class="button-primary" id="wmbr-add-shipping-info">Adicionar novo</button>
					<input type="hidden" name="shipping-info-count" value="<?php echo count($transportadoras); ?>" />
				</div>

				<?php
				include_once(plugin_dir_path(dirname(__FILE__)).'/inc/templates/payment-setting.php');
    }
	function force_digital_certificate_update() {
	?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				var data = {
					'action': 'force_digital_certificate_update'
				};
				$("#update-digital-certificate").click(function(){
					var response = '';
					$("#update-digital-certificate").prop('disabled', true);
					jQuery.post(ajaxurl, data, function(response) {
						if ( response.status == 'success' ) {
							response = '<h4 class="cert_ajax_success">Seu Certificado Digital A1 foi atualizado: Faltam ' + response.msg + ' dias para o certificado digital A1 expirar.</h4>';
						} else if ( response.status == 'error' ) {
							response = '<h4 class="cert_ajax_error">Erro ao atualizar o Certificado Digital A1: ' + response.msg + '</h4> ';
						} else if ( response.status == 'null_credentials' ) {
							response = '<h4 class="cert_ajax_error">' + response.msg + '</h4> ';
						} else {
							response = '<h4 class="cert_ajax_error">Não foi possível atualizar seu Certificado Digital A1. Por favor, solicite suporte para <a href="mailto:suporte@webmaniabr.com">suporte@webmaniabr.com</a></h4>';
						}
						$("#update-digital-certificate-response").html(response);
						$("#update-digital-certificate").prop('disabled', false);
					}, 'json');
				});
			});
		</script>
	<?php
	}
    function update_settings(){
        woocommerce_update_options( $this->get_settings(true) );
		//Transportadoras
		$count = (int) $_POST['shipping-info-count'];
		$transportadoras = array();
		//Payment methods
		$payment_methods = array();
		$cnpj_payment_methods = array();
		foreach($_POST['payment_method'] as $key => $value){
			$payment_methods[$key] = sanitize_text_field($value);
		}
		for($i = 1; $i < $count+1; $i++){
			$id = $_POST['shipping_info_method_'.$i];
			if(!$id) continue;
			$transportadoras[$id] = array();
			$keys = array(
				'razao_social' => 'rs',
				'cnpj'         => 'cnpj',
				'ie'           => 'ie',
				'cpf'          => 'cpf',
				'nome'         => 'name',
				'address'      => 'address',
				'cep'          => 'cep',
				'city'         => 'city',
				'uf'           => 'uf',
				'placa'        => 'plate',
				'uf_veiculo'   => 'uf_vehicle'
			);
			foreach($keys as $name => $post_key){
				$transportadoras[$id][$name] = sanitize_text_field($_POST['shipping_info_'.$post_key.'_'.$i]);
			}
		}
		update_option('wc_settings_woocommercenfe_transportadoras', $transportadoras);
		update_option('wc_settings_woocommercenfe_payment_methods', $payment_methods);
		update_option('wc_settings_woocommercenfe_cnpj_payments', $cnpj_payment_methods);
		$include = $_POST['wc_settings_woocommercenfe_transp_include'];
		if($include){
			update_option('wc_settings_woocommercenfe_transp_include', 'on');
		}else{
			update_option('wc_settings_woocommercenfe_transp_include', 'off');
		}
    }
    function get_settings($update = false){
        global $domain;
        $auto_invoice_report_url = menu_page_url('wmbr_page_auto_invoice_errors', false);

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
                'options' => array('1' => 'Produção', '2' => 'Desenvolvimento (Testes)'),
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
                'desc'     => 'A configuração padrão será utilizada para todos os produtos.<br>Caso deseje a configuração também pode ser personalizada em cada produto ou categoria.'
            ),
						'emissao_automatica' => array(
                'name' => __( 'Emissão automática', $domain ),
                'type' => 'radio',
                'options' => array(
                	'0' => 'Não emitir automaticamente',
                	'1' => 'Sempre que o pedido ter o status alterado para um dos Status abaixo',
            	),
                'default' => '0',
                'id'   => 'wc_settings_woocommercenfe_emissao_automatica'
            ),
						'emissao_automatica_status' => array(
	                'name' => __( 'Status para emissão automática', $domain ),
	                'type' => 'multiselect',
	                'id'   => 'wc_settings_woocommercenfe_emissao_automatica_status',
									'css'  => 'height: 100px;',
									'desc' => 'Segure CONTROL ou SHIFT para selecionar mais de uma opção.'
	            ),
            'email_notification' => array(
                'name' => __( 'E-mail para notificação de erros de emissão', $domain ),
                'type' => 'email',
                'desc' => __( 'Informe um e-mail para notificações quando houver erro na emissão ou <a target="_blank" href="'.$auto_invoice_report_url.'">visualize aqui</a>.'),
                'css' => 'width:300px;',
                'id'   => 'wc_settings_woocommercenfe_email_notification'
            ),
					'data_emissao' => array(
                'name' => __( 'Emissão com data retroativa', $domain ),
                'type' =>'checkbox',
            	'desc' => __( 'Emissão de Nota Fiscal com a data do pedido (retroativa)<br /><em>*Recurso limitado para alguns Sefaz, consulte contabilidade caso necessário.</em>'),
                'default' => 'no',
                'id'   => 'wc_settings_woocommercenfe_data_emissao'
            ),
						'envio_email' => array(
							'name' => __( 'Envio automático de email', $domain ),
							'type' =>'checkbox',
							'desc' => __( 'Enviar email para o cliente após a emissão da nota fiscal eletrônica <br /><em style="color: red">Atenção: O email será enviado mesmo para notas emitidas em ambiente de homologação.</em>'),
							'default' => 'yes',
							'id'   => __('wc_settings_woocommercenfe_envio_email'),
						),
						'email_notification' => array(
                'name' => __( 'Notificação de erros', $domain ),
                'type' => 'email',
                'desc' => __( 'Informe um e-mail para notificações de erros na emissão ou <a target="_blank" href="'.$auto_invoice_report_url.'">visualize as notificações</a>.'),
                'css' => 'width:300px;',
                'id'   => 'wc_settings_woocommercenfe_email_notification'
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
                'name' => __( 'GTIN (Antigo código EAN)', $domain ),
                'type' => 'text',
                'id'   => 'wc_settings_woocommercenfe_ean'
            ),
            'gtin_tributavel' => array(
                'name' => __( 'GTIN tributável', $domain ),
                'type' => 'text',
                'id'   => 'wc_settings_woocommercenfe_gtin_tributavel'
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
            'cnpj_fabricante' => array(
                'name' => __( 'CNPJ do fabricante da mercadoria', $domain ),
                'type' => 'text',
                'id'   => 'wc_settings_woocommercenfe_cnpj_fabricante'
            ),

						'cnpj_credenciadora' => array(
                'name' => __( 'CNPJ da Credenciadora do Cartão', $domain ),
                'type' => 'text',
                'id'   => 'wc_settings_woocommercenfe_cnpj_credenciadora'
            ),

            'ind_escala' => array(
            	'name' => __('Indicador de escala relevante'),
            	'type' => 'select',
            	'options' => array(
                   'null' => 'Selecionar',
                   'S' => 'S - Produzido em Escala Relevante',
                   'N' => 'N - Produzido em Escala NÃO Relevante',
                ),
                'id'   => 'wc_settings_woocommercenfe_ind_escala'
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
			'section_ebanx' => array(
                'type' => 'sectionend',
                'id' => 'wc_settings_woocommercenfe_ebanx'
            ),
            'ebanx_title' => array(
                'name'     => __( 'Configurações EBANX', $domain ),
                'type'     => 'title',
                'desc'     => 'Defina as configurações do plugin EBANX.'
            ),
            'ebanx_parcelas' => array(
                'name' => __( 'Exibir parcelas como duplicata', $domain ),
                'type' => 'checkbox',
                'desc' => __( 'Exibir parcelas como duplicata na emissão da NF-e do pedido pagos pelo plugin EBANX.', $domain ),
                'id'   => 'wc_settings_parcelas_ebanx',
                'default' => 'no',
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
            )
        );
				$opt = array();
				foreach(wc_get_order_statuses() as $status => $desc) {
					$opt[$status] = $desc;
				}
				$settings['emissao_automatica_status']['options'] = $opt;
        // WooCommerce Extra Checkout Fields for Brazil
        if ( $this->wmbr_is_plugin_active('woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php') ) {
        	unset($settings['title5']);
        	unset($settings['tipo_pessoa']);
        	unset($settings['mascara_campos']);
        	unset($settings['cep']);
        }

		if ( !$this->wmbr_is_plugin_active('ebanx-local-payment-gateway-for-woocommerce/woocommerce-gateway-ebanx.php') ) {
        	unset($settings['section_ebanx']);
        	unset($settings['ebanx_title']);
        	unset($settings['ebanx_parcelas']);
		}

        return $settings;
    }
		function get_transportadoras_entries(){
			$transportadoras = get_option('wc_settings_woocommercenfe_transportadoras', array());
			$html  = '';
			$i = 1;
			foreach($transportadoras as $key => $transp){
				$html .= '<div class="entry">';
				$html .= '<div class="shipping-method-col">'.$this->get_shipping_methods_select($i, $key).'</div>';
				$html .= '<div class="shipping-info-col">';
				$html .= '<p><label class="nfe-shipping-label">Razão Social: </label><input type="text" name="shipping_info_rs_'.$i.'" value="'.$transp['razao_social'].'"/></p>';
				$html .= '<p><label class="nfe-shipping-label">CNPJ: </label><input type="text" name="shipping_info_cnpj_'.$i.'" value="'.$transp['cnpj'].'"/></p>';
				$html .= '<p><label class="nfe-shipping-label">Inscrição estadual: </label><input type="text" name="shipping_info_ie_'.$i.'" value="'.$transp['ie'].'"/></p>';
				$html .= '<p><label class="nfe-shipping-label">CPF: </label><input type="text" name="shipping_info_cpf_'.$i.'" value="'.$transp['cpf'].'"/></p>';
				$html .= '<p><label class="nfe-shipping-label">Nome Completo: </label><input type="text" name="shipping_info_name_'.$i.'" value="'.$transp['nome'].'"/></p>';
				$html .= '<p><label class="nfe-shipping-label">Endereço: </label><input type="text" name="shipping_info_address_'.$i.'" value="'.$transp['address'].'"/></p>';
				$html .= '<p><label class="nfe-shipping-label">CEP: </label><input type="text" name="shipping_info_cep_'.$i.'" value="'.$transp['cep'].'"/></p>';
				$html .= '<p><label class="nfe-shipping-label">Cidade: </label><input type="text" name="shipping_info_city_'.$i.'" value="'.$transp['city'].'"/></p>';
				$html .= '<p><label class="nfe-shipping-label">UF: </label><input type="text" name="shipping_info_uf_'.$i.'" value="'.$transp['uf'].'"/></p>';
				$html .= '<p><label class="nfe-shipping-label">Placa do Veículo: </label><input type="text" name="shipping_info_plate_'.$i.'" value="'.$transp['placa'].'"/></p>';
				$html .= '<p><label class="nfe-shipping-label">UF do Veículo: </label><input type="text" name="shipping_info_uf_vehicle_'.$i.'" value="'.$transp['uf_veiculo'].'"/></p>';
				$html .= '<button type="button" class="button wmbr-remove-shipping-info"><span class="dashicons dashicons-no"></span> Remover</button>';
				$html .= '</div>';
				$html .= '</div>';
				$i++;
			}
			return $html;
		}
		function get_shipping_methods_select($index = 0, $id = ''){
			$shipping = new WC_Shipping();
			$shipping->load_shipping_methods();
		  $shipping_methods = $shipping->get_shipping_methods();
			$html = '<select class="nfe-shipping-methods-sel" name="shipping_info_method_'.$index.'">';
			$html .= '<option value="">Selecionar</option>';
			foreach($shipping_methods as $method){
				if($method->id == 'correios'){
					continue;
				}
				($method->id == $id ? $selected = 'selected' : $selected = '');
				$title = $method->get_title();
				if(!$title && isset($method->method_title)){
					$title = $method->method_title;
				}
		    $html .= '<option value="'.$method->id.'" '.$selected.'>'.$title.'</option>';
		  }
			$html .= '</select>';
			return $html;
		}

		function get_payment_methods_select($method, $index = 0, $id = '') {

			$saved_values = get_option('wc_settings_woocommercenfe_payment_methods', array());
			$options = array(
				'01' => 'Dinheiro',
				'02' => 'Cheque',
				'03' => 'Cartão de Crédito',
				'04' => 'Cartão de Débito',
				'15' => 'Boleto Bancário',
				'90' => 'Sem pagamento',
				'pagseguro' => 'PagSeguro',
				'99' => 'Outros',
			);
			$html = '<select class="nfe-payment-methods-sel" name="payment_method['.$method.']">';
			$html .= '<option value="">Selecionar</option>';
			foreach($options as $value => $label){
				$selected = '';
				if(isset($saved_values[$method]) && $saved_values[$method] == $value){
					$selected = 'selected';
				}
		    $html .= '<option value="'.$value.'" '.$selected.'>'.$label.'</option>';
		  }
			$html .= '</select>';
			return $html;
		}
		function register_metabox_nfe_emitida() {
        add_meta_box(
            'woocommernfe_nfe_emitida',
            'NF-e do Pedido',
            array($this, 'metabox_content_woocommernfe_nfe_emitida'),
            'shop_order',
            'normal',
            'high'
        );
		add_meta_box(
            'woocommernfe_informacoes_adicionais',
            'Nota Fiscal',
            array($this, 'metabox_content_woocommernfe_informacoes_adicionais'),
            'shop_order',
            'side',
            'high'
        );
		//Specific shipping info
		add_meta_box(
            'woocommernfe_transporte',
            'Transporte (NF-e)',
            array($this, 'metabox_content_woocommernfe_transporte'),
            'shop_order',
            'side',
            'high'
        );
    }
		function atualizar_status_nota() {
			if(!is_admin()){
				return false;
			}
			if(isset($_GET['atualizar_ne']) || isset($_GET['atualizar_nfe']) && $_GET['post'] && $_GET['chave']){
				$post_id = (int) sanitize_text_field($_GET['post']);
				$chave = sanitize_text_field($_GET['chave']);
				$webmaniabr = new NFe(WC_NFe()->settings);
				$response = $webmaniabr->consultaNotaFiscal($chave);
				if (isset($response->error)){
            WC_NFe()->add_error( __('Erro: '.$response->error, $domain) );
            return false;
        }else{
					$new_status = $response->status;
					$id = 'nfe';
					$nfe_data = get_post_meta($post_id, $id, true);
					if (!$nfce_data) {
						$id = 'nfce';
						$nfe_data = get_post_meta($post_id, $id, true);
					}
					foreach($nfe_data as &$order_nfe){
						if($order_nfe['chave_acesso'] == $chave){
							$order_nfe['status'] = $new_status;
						}
					}
					update_post_meta($post_id, $id, $nfe_data);
					WC_NFe()->add_success( 'NF-e atualizada com sucesso' );
				}
			}
		}
		function metabox_content_woocommernfe_nfe_emitida( $post ) {
			$nfe_data = get_post_meta($post->ID, 'nfe', true);
			$nfce_data = get_post_meta($post->ID, 'nfce', true);
			if(empty($nfe_data) && empty($nfce_data)):?>
			<p>Nenhuma nota emitida para este pedido</p>

			<?php else:
                $nfe_data = array_reverse(empty($nfe_data) ? $nfce_data : $nfe_data);
            ?>
				<div class="all-nfe-info">
					<div class="head">
						<h4 class="head-title">Data</h4>
						<h4 class="head-title n-column">Nº</h4>
						<h4 class="head-title danfe-column">Danfe</h4>
						<h4 class="head-title status-column">Status</h4>
					</div>
					<div class="body">
						<?php foreach($nfe_data as $order_nfe):
							(isset($order_nfe['data']) ? $data_nfe = $order_nfe['data'] : $data_nfe = '' );
							(isset($order_nfe['n_nfe']) ? $numero_nfe = $order_nfe['n_nfe'] : $numero_nfe = '' );
							(isset($order_nfe['chave_acesso']) ? $chave_acesso_nfe = $order_nfe['chave_acesso'] : $chave_acesso_nfe = '' );
							(isset($order_nfe['status']) ? $status_nfe = $order_nfe['status'] : $status_nfe = '' );
							(isset($order_nfe['url_xml']) ? $xml_nfe = $order_nfe['url_xml'] : $xml_nfe = '' );
							(isset($order_nfe['n_recibo']) ? $recibo_nfe = $order_nfe['n_recibo'] : $recibo_nfe = '' );
							(isset($order_nfe['n_serie']) ? $serie_nfe = $order_nfe['n_serie'] : $serie_nfe = '' );
							?>
							<div class="single">
								<div>
								<h4 class="body-info"><?php echo $data_nfe; ?></h4>
								<h4 class="body-info n-column"><?php echo $numero_nfe; ?></h4>
								<h4 class="body-info danfe-column"><a class="unstyled" target="_blank" href="<?php echo $order_nfe['url_danfe'] ?>"><span class="wrt">Visualizar Nota</span><span class="dashicons dashicons-media-text danfe-icon"></span></a></h4>
								<?php
									$post_url = get_edit_post_link($post->ID);
									$update_url = $post_url.'&atualizar_nfe=true&chave='.$chave_acesso_nfe;
								?>
								<h4 class="body-info status-column"><span class="nfe-status <?php echo $status_nfe; ?>"><?php echo $status_nfe; ?></span><a class="unstyled" href="<?php echo $update_url; ?>"><span class="dashicons dashicons-image-rotate update-nfe"></span></a></h4></div>
								<div class="extra">
									<ul>
										<li><strong>RPS:</strong> <?php echo $recibo_nfe; ?></li>
										<li><strong>Série:</strong> <?php echo $serie_nfe ?></li>
										<li><strong>Arquivo XML:</strong> <a target="_blank" href="<?php echo $xml_nfe; ?>">Download XML</a></li>
										<li><strong>Código Verificação:</strong> <?php echo $chave_acesso_nfe; ?></li>
									</ul>
								</div>
								<span class="dashicons dashicons-arrow-down-alt2 expand-nfe"></span>
							</div>




						<?php endforeach; ?>
					</div>
				</div>

 		<?php endif;
		}
		function metabox_content_woocommernfe_informacoes_adicionais( $post ) {
			?>

			<div class="inside" style="padding:0!important;">
			    <div class="field emitir_ambiente" style="margin-bottom:10px;">
					<p class="label" style="margin-bottom:8px;">
						<input type="checkbox" name="emitir_homologacao"/>
						<label style="font-size:13px;line-height:1.5em;font-weight:bold;">Emitir em homologação</label>
					</p>
					<p class="label" style="margin-bottom:8px;">
						<input type="checkbox" name="nao_emitir"/>
						<label style="font-size:13px;line-height:1.5em;font-weight:bold;">Não emitir nota deste pedido</label>
					</p>
				</div>
				<hr>
				<div class="field outras_informacoes">
			        <p class="label" style="margin-bottom:8px;">
			            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">Natureza da Operação</label>
			        </p>
			        <input type="text" name="natureza_operacao_pedido" value="<?php echo get_post_meta( $post->ID, '_nfe_natureza_operacao_pedido', true ); ?>" style="width:100%;padding:5px;">
			    </div>

				<input type="hidden" name="wp_admin_nfe" value="1" />
			</div>

			<?php
		}
		function metabox_content_woocommernfe_transporte( $post ){
			global $domain;
			?>
			<div class="inside" style="padding:0!important;">
				<p>
					Informações complementares na emissão de Nota Fiscal para pedidos enviados via Transportadora.
				</p>
				<?php
					$forma_envio = get_post_meta( $post->ID, '_nfe_transporte_forma_envio', true );
					$modalidade_frete = get_post_meta($post->ID, '_nfe_modalidade_frete', true);
				?>
				<script>
				jQuery(function($) {
				    $('#transporte_forma_envio').on('change', function(){ if ($(this).val() == '1') $('.transporte').show(); else $('.transporte').hide(); });
						<?php if (is_numeric($forma_envio) && $forma_envio == '1'){ ?>$('.transporte').show();<?php } ?>
						$('input[name="transporte_peso_bruto"]').on('keyup', function(){
							$('input[name="transporte_peso_liquido"]').val($(this).val());
						});
				});
				</script>
				<div class="field">
						<p class="label" style="margin-bottom:8px;">
								<label style="font-size:13px;line-height:1.5em;font-weight:bold;">Modalidade do frete</label>
						</p>
						<select name="modalidade_frete" id="modalidade_frete">
								<option value="null" <?php if (!is_numeric($modalidade_frete)) echo 'selected'; ?> ><?php _e( 'Contratação do Frete por conta do Remetente (CIF)', $domain ); ?></option>
								<option value="1" <?php if (is_numeric($modalidade_frete) && $modalidade_frete == '1') echo 'selected'; ?> ><?php _e( 'Contratação do Frete por conta do Destinatário (FOB)', $domain ); ?></option>
								<option value="2" <?php if (is_numeric($modalidade_frete) && $modalidade_frete == '2') echo 'selected'; ?> ><?php _e( 'Contratação do Frete por conta de Terceiros', $domain ); ?></option>
								<option value="3" <?php if (is_numeric($modalidade_frete) && $modalidade_frete == '3') echo 'selected'; ?> ><?php _e( 'Transporte Próprio por conta do Remetente', $domain ); ?></option>
								<option value="4" <?php if (is_numeric($modalidade_frete) && $modalidade_frete == '4') echo 'selected'; ?> ><?php _e( 'Transporte Próprio por conta do Destinatário', $domain ); ?></option>
								<option value="9" <?php if (is_numeric($modalidade_frete) && $modalidade_frete == '9') echo 'selected'; ?> ><?php _e( 'Sem Ocorrência de Transporte', $domain ); ?></option>
					 </select>
		    </div>
				<div class="label transporte" style="margin-bottom:8px;margin-top:10px;">
						<label style="font-size:14px;line-height:1.5em;font-weight:bold;color:red">Volumes Transportados</label>
						<hr style="margin-top:5px;">
				</div>
			    <div class="field transporte">
			        <p class="label" style="margin-bottom:8px;">
			            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">Volumes</label>
			        </p>
			        <input type="text" name="transporte_volume" value="<?php echo get_post_meta( $post->ID, '_nfe_transporte_volume', true ); ?>" style="width:100%;padding:5px;">
			    </div>
			    <div class="field transporte">
			        <p class="label" style="margin-bottom:8px;">
			            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">Espécie</label>
			        </p>
			        <input type="text" name="transporte_especie" value="<?php echo get_post_meta( $post->ID, '_nfe_transporte_especie', true ); ?>" style="width:100%;padding:5px;">
			    </div>
				<div class="field transporte">
			        <p class="label" style="margin-bottom:8px;">
			            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">Peso Bruto</label> (KG)
			        </p>
			        <input type="text" name="transporte_peso_bruto" value="<?php echo get_post_meta( $post->ID, '_nfe_transporte_peso_bruto', true ); ?>" style="width:100%;padding:5px;" placeholder="Ex: 50.210 = 50,210KG">
			    </div>
				<div class="field transporte">
			        <p class="label" style="margin-bottom:8px;">
			            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">Peso Líquido</label> (KG)
			        </p>
			        <input type="text" name="transporte_peso_liquido" value="<?php echo get_post_meta( $post->ID, '_nfe_transporte_peso_liquido', true ); ?>" style="width:100%;padding:5px;" placeholder="Ex: 50.210 = 50,210KG">
			    </div>

				<div class="field transporte" style="display:none;">
			        <p class="label" style="margin-bottom:8px;">
			            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">Valor do Seguro (R$)</label>
			        </p>
			        <input type="text" name="transporte_seguro" value="<?php echo get_post_meta( $post->ID, '_nfe_transporte_seguro', true ); ?>" style="width:100%;padding:5px;">
			    </div>

				<input type="hidden" name="wp_admin_nfe" value="1" />
			</div>
			<?php
		}
    function register_metabox_listar_nfe() {
        add_meta_box(
            'woocommernfe_informacoes',
            'Informações Fiscais (Opcional)',
            array($this, 'metabox_content_woocommernfe_informacoes'),
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
            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">GTIN (antigo código EAN)</label>
        </p>
        <input type="text" name="codigo_ean" value="<?php echo get_post_meta( $post->ID, '_nfe_codigo_ean', true ); ?>" style="width:100%;padding:5px;">
    </div>
    <div class="field">
        <p class="label" style="margin-bottom:8px;">
            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">GTIN tributável</label>
        </p>
        <input type="text" name="gtin_tributavel" value="<?php echo get_post_meta( $post->ID, '_nfe_gtin_tributavel', true ); ?>" style="width:100%;padding:5px;">
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
            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">CNPJ do Frabricante</label>
        </p>
        <input type="text" name="cnpj_fabricante" value="<?php echo get_post_meta( $post->ID, '_nfe_cnpj_fabricante', true ); ?>" style="width:100%;padding:5px;">
    </div>

    <div class="field">
        <p class="label" style="margin-bottom:8px;">
            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">Indicador de escala relevante</label>
        </p>
        <?php
          $ind_escala = get_post_meta( $post->ID, '_nfe_ind_escala', true );
        ?>
        <select name="ind_escala">
            <option value="" <?php if (!$ind_escala) echo 'selected'; ?> ><?php _e( 'Selecionar', $domain ); ?></option>
            <option value="S" <?php if ($ind_escala == 'S') echo 'selected'; ?> ><?php _e( 'S - Produzido em Escala Relevante', $domain ); ?></option>
            <option value="N" <?php if ($ind_escala == 'N') echo 'selected'; ?> ><?php _e( 'N - Produzido em Escala NÃO Relevante', $domain ); ?></option>
       </select>
			 <input type="hidden" name="wp_admin_nfe" value="1" />
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
			 <input type="hidden" name="wp_admin_nfe" value="1" />
    </div>

    <div class="field">
        <p class="label" style="margin-bottom:8px;">
            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">Informações adicionais</label>
        </p>
        <input type="text" name="produto_informacoes_adicionais" value="<?php echo get_post_meta( $post->ID, '_nfe_produto_informacoes_adicionais', true ); ?>" style="width:100%;padding:5px;">
    </div>

		<div class="field">
        <p class="label" style="margin-bottom:8px;">
            <label style="font-size:13px;line-height:1.5em;font-weight:bold;">Unidade de Medida</label>
        </p>
        <?php
          $unidade = get_post_meta( $post->ID, '_nfe_unidade', true );
					$lista_unidades = array('UN' => 'UNIDADE',
						'AMPOLA' => 'AMPOLA',
						'BALDE' => 'BALDE',
						'BANDEJ' => 'BANDEJA',
						'BARRA' => 'BARRA',
						'BISNAG' => 'BISNAGA',
						'BLOCO' => 'BLOCO',
						'BOBINA' => 'BOBINA',
						'BOMB' => 'BOMBONA',
						'CAPS' => 'CAPSULA',
						'CART' => 'CARTELA',
						'CENTO' => 'CENTO',
						'CJ' => 'CONJUNTO',
						'CM' => 'CENTIMETRO',
						'CM2' => 'CENTIMETRO QUADRADO',
						'CX' => 'CAIXA',
						'CX2' => 'CAIXA COM 2 UNIDADES',
						'CX3' => 'CAIXA COM 3 UNIDADES',
						'CX5' => 'CAIXA COM 5 UNIDADES',
						'CX10' => 'CAIXA COM 10 UNIDADES',
						'CX15' => 'CAIXA COM 15 UNIDADES',
						'CX20' => 'CAIXA COM 20 UNIDADES',
						'CX25' => 'CAIXA COM 25 UNIDADES',
						'CX50' => 'CAIXA COM 50 UNIDADES',
						'CX100' => 'CAIXA COM 100 UNIDADES',
						'DISP' => 'DISPLAY',
						'DUZIA' => 'DUZIA',
						'EMBAL' => 'EMBALAGEM',
						'FARDO' => 'FARDO',
						'FOLHA' => 'FOLHA',
						'FRASCO' => 'FRASCO',
						'GALAO' => 'GALÃO',
						'GF' => 'GARRAFA',
						'GRAMAS' => 'GRAMAS',
						'JOGO' => 'JOGO',
						'KG' => 'QUILOGRAMA',
						'KIT' => 'KIT',
						'LATA' => 'LATA',
						'LITRO' => 'LITRO',
						'M' => 'METRO',
						'M2' => 'METRO QUADRADO',
						'M3' => 'METRO CÚBICO',
						'MILHEI' => 'MILHEIRO',
						'ML' => 'MILILITRO',
						'MWH' => 'MEGAWATT HORA',
						'PACOTE' => 'PACOTE',
						'PALETE' => 'PALETE',
						'PARES' => 'PARES',
						'PC' => 'PEÇA',
						'POTE' => 'POTE',
						'K' => 'QUILATE',
						'RESMA' => 'RESMA',
						'ROLO' => 'ROLO',
						'SACO' => 'SACO',
						'SACOLA' => 'SACOLA',
						'TAMBOR' => 'TAMBOR',
						'TANQUE' => 'TANQUE',
						'TON' => 'TONELADA',
						'TUBO' => 'TUBO',
						'VASIL' => 'VASILHAME',
						'VIDRO' => 'VIDRO'
					);
        ?>
        <select name="unidade">
					<?php
						foreach ($lista_unidades as $k => $v) {
							?>
							<option value="<?php echo $k;?>" <?php if ($unidade == $k) echo 'selected'; ?> ><?php echo $v; ?></option>
							<?php
						}
					?>
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
			// Get the 'NF-e' informations
			$nfe = get_post_meta( $post->ID, 'nfe', true );
			$nfce = get_post_meta( $post->ID, 'nfce', true );

			// Get the order informations
			$order = new WC_Order( $post->ID );
			// If order has the status pending or cancelled, don't print 'NF-e' status
            if ($order->get_status() == 'pending' || $order->get_status() == 'cancelled') {
            	echo '<span class="nfe_none">-</span>';
            // Else if $nfe has information, check status from array
            } elseif ($nfe || $nfce) {

							$data = empty($nfe) ? $nfce : $nfe;

				// Define as false
				$status = 0;

						if ($data)
            	foreach ( $data as $item ) {
            		// If array has any approved document define $status as value according to it
            		if ( $item['status'] == 'aprovado' ) {
									$status = 1;
            		} else if ( $item['status'] == 'processamento' || $item['status'] == 'processando' || $item['status'] == 'contingencia') {
									$status = 2;
								} else if ( $item['status'] == 'reprovado' || $item['status'] == 'cancelado') {
									$status = 3;
								}
            	}

							$tipo = empty($nfce) ? 'NF-e' : 'NFC-e';

							// Print depending of the case
							switch ($status) {
								case 0:
									echo '<div class="nfe_alert">' . $tipo  . ' não emitida</div>';
									break;
								case 1:
									echo '<div class="nfe_success">' . $tipo  . ' emitida</div>';
									break;
								case 2:
									echo '<div class="nfe_alert">' . $tipo  . ' processando</div>';
									break;
								case 3:
									echo '<div class="nfe_error">Sem ' . $tipo  . ' válida</div>';
									break;
							}
            } else {
            	echo '<div class="nfe_alert">NF-e não emitida</div>';
            }
		}
	}
	function add_order_meta_box_actions( $actions ) {
		$actions['wc_nfe_emitir'] = __( 'Emitir NF-e' );
		$actions['wc_nfce_emitir'] = __( 'Emitir NFC-e' );
		return $actions;
	}
	function add_order_bulk_actions() {
		global $post_type, $post_status;
		if ( $post_type == 'shop_order' ) {
			if ($post_status == 'trash' || $post_status == 'wc-cancelled' || $post_status == 'wc-pending') return false;
			?>
			<script type="text/javascript">
				jQuery( document ).ready( function ( $ ) {
					var $emitir_nfe = $('<option>').val('wc_nfe_emitir').text('<?php _e( 'Emitir NF-e' ); ?>');
					$( 'select[name^="action"]' ).append( $emitir_nfe );
						var $emitir_nfce = $('<option>').val('wc_nfce_emitir').text('<?php _e( 'Emitir NFC-e' ); ?>');
						$( 'select[name^="action"]' ).append( $emitir_nfce );
						  });
			</script>
			<?php
		}
	}
	function style(){
		?>
		<style>
		.nfe_alert { display: inline; padding: .2em .6em .3em; font-size: 11px; font-weight: 700; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: .25em; background-color: #c6ab2a; }
		.nfe_error { display: inline; padding: .2em .6em .3em; font-size: 11px; font-weight: 700; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: .25em; background-color: #d9534f; }
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

			if ( ! in_array( $action, array( 'wc_nfe_emitir', 'wc_nfce_emitir') ) ) return false;
			if ( isset( $_REQUEST['post'] ) ) $order_ids = array_map( 'absint', $_REQUEST['post'] );
			if ( empty( $order_ids ) ) return false;
			if ($action == 'wc_nfe_emitir') WC_NFe()->emitirNFe( $order_ids );
			if ($action == 'wc_nfce_emitir') WC_NFe()->emitirNFCe( $order_ids );

		}
	}
	function process_order_meta_box_actions( $post ){
		$order_id = $post->id;
		$post_status = $post->post_status;
		if ($post_status == 'trash' || $post_status == 'wc-cancelled') return false;
		WC_NFe()->emitirNFe( array( $order_id ) );
	}

	function process_order_meta_box_actions2( $post ){

		$order_id = $post->id;
		$post_status = $post->post_status;
		if ($post_status == 'trash' || $post_status == 'wc-cancelled') return false;

		WC_NFe()->emitirNFCe( array( $order_id ) );

	}

    function scripts(){
        wp_register_script( 'woocommercenfe_admin_script', apply_filters( 'woocommercenfe_plugins_url', plugins_url( 'assets/js/admin_scripts.js', __FILE__ ) ), null, self::$version );
        wp_register_style( 'woocommercenfe_admin_style', apply_filters( 'woocommercenfe_plugins_url', plugins_url( 'assets/css/admin_style.css', __FILE__ ) ), null, self::$version );
        wp_enqueue_style( 'woocommercenfe_admin_style' );
        wp_enqueue_script( 'woocommercenfe_admin_script' );
    }
	function global_admin_scripts(){
    wp_register_script( 'woocommercenfe_table_scripts', apply_filters( 'woocommercenfe_plugins_url', plugins_url( 'assets/js/nfe_table.js', __FILE__ ) ) );
    wp_register_style( 'woocommercenfe_table_style', apply_filters( 'woocommercenfe_plugins_url', plugins_url( 'assets/css/nfe_table.css', __FILE__ ) ) );
    wp_enqueue_style( 'woocommercenfe_table_style' );
    wp_enqueue_script( 'woocommercenfe_table_scripts' );
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
		$new_fields['billing']['fields']['billing_phone']    = str_replace("?", "", $fields['billing']['fields']['billing_phone']);
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
		global $domain;
    $shipping_data['first_name'] = array(
			'label' => __( 'Nome', $domain ),
			'show'  => false
		);
		$shipping_data['last_name'] = array(
			'label' => __( 'Sobrenome', $domain ),
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
	function extra_shipping_fields( $data ) {
		global $domain;

    $shipping_data['persontype'] = array(
        'type'    => 'select',
        'label'   => __( 'Tipo Pessoa', $domain ),
        'options' => array(
            '3' => __( 'Utilizar dados de cobrança', $domain ),
            '1' => __( 'Pessoa Física', $domain ),
            '2' => __( 'Pessoa Jurídica', $domain )
        ),
        'show'  => false
    );
    $shipping_data['cpf'] = array(
        'label' => __( 'CPF', $domain ),
        'show'  => false
    );
    $shipping_data['cnpj'] = array(
        'label' => __( 'CNPJ', $domain ),
        'show'  => false
    );
    $shipping_data['ie'] = array(
        'label' => __( 'Inscrição Estadual', $domain ),
        'show'  => false
    );

		return array_merge($shipping_data, $data);

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
		update_post_meta( $post_id, '_billing_cellphone', woocommerce_clean( str_replace("?", "", $_POST['_billing_cellphone']) ) );
	}
	function wc_api_save_custom_shop_data($order_id, $data){
		$billing_address = $data['customer']['billing_address'];
		$shipping_address = $data['customer']['shipping_address'];
		update_post_meta( $order_id, '_billing_number', woocommerce_clean( $billing_address['number'] ) );
		update_post_meta( $order_id, '_billing_neighborhood', woocommerce_clean( $billing_address['neighborhood'] ) );
		update_post_meta( $order_id, '_shipping_number', woocommerce_clean( $shipping_address['number'] ) );
		update_post_meta( $order_id, '_shipping_neighborhood', woocommerce_clean( $shipping_address['neighborhood'] ) );
		update_post_meta( $order_id, '_billing_persontype', woocommerce_clean( $billing_address['persontype'] ) );
		update_post_meta( $order_id, '_billing_cpf', woocommerce_clean( $billing_address['cpf'] ) );
		update_post_meta( $order_id, '_billing_cnpj', woocommerce_clean( $billing_address['cnpj'] ) );
		update_post_meta( $order_id, '_billing_ie', woocommerce_clean( $billing_address['ie'] ) );
		update_post_meta( $order_id, '_billing_birthdate', woocommerce_clean( $billing_address['birthdate'] ) );
		update_post_meta( $order_id, '_billing_sex', woocommerce_clean( $billing_address['sex'] ) );
		update_post_meta( $order_id, '_billing_cellphone', woocommerce_clean( str_replace("?", "", $billing_address['cellphone']) ) );
	}
    function save_informacoes_fiscais( $post_id ){
        if (get_post_type($post_id) == 'product' && $_POST['wp_admin_nfe']){
            $info = array(
						'_nfe_classe_imposto'  => $_POST['classe_imposto'],
						'_nfe_codigo_ean'      => $_POST['codigo_ean'],
						'_nfe_gtin_tributavel' => $_POST['gtin_tributavel'],
						'_nfe_codigo_ncm'      => $_POST['codigo_ncm'],
						'_nfe_codigo_cest'     => $_POST['codigo_cest'],
						'_nfe_cnpj_fabricante' => $_POST['cnpj_fabricante'],
						'_nfe_cnpj_credenciadora' => $_POST['cnpj_credenciadora'],
						'_nfe_ind_escala'      => $_POST['ind_escala'],
						'_nfe_produto_informacoes_adicionais' => $_POST['produto_informacoes_adicionais'],
						'_nfe_unidade'         => $_POST['unidade']
						);
						foreach ($info as $key => $value){
							if (isset($value)) update_post_meta($post_id, $key, $value);
						}
						if ($_POST['ignorar_nfe']){
							update_post_meta( $post_id, '_nfe_ignorar_nfe', $_POST['ignorar_nfe'] );
						}else{
							update_post_meta( $post_id, '_nfe_ignorar_nfe', 0 );
						}
            if (is_numeric($_POST['origem']) || $_POST['origem']) update_post_meta( $post_id, '_nfe_origem', $_POST['origem'] );
        }
				if (get_post_type($post_id) == 'shop_order' && $_POST['wp_admin_nfe']){
					$info = array(
						'_nfe_modalidade_frete' 		=> $_POST['modalidade_frete'],
						'_nfe_transporte_forma_envio'	=> $_POST['transporte_forma_envio'],
						'_nfe_transporte_volume'    	=> $_POST['transporte_volume'],
						'_nfe_transporte_especie'   	=> $_POST['transporte_especie'],
						'_nfe_transporte_peso_bruto'    => $_POST['transporte_peso_bruto'],
						'_nfe_transporte_peso_liquido'  => $_POST['transporte_peso_liquido'],
						'_nfe_natureza_operacao_pedido'	=> $_POST['natureza_operacao_pedido'],
						'_nfe_emitir_homologacao'      	=> $_POST['emitir_homologacao'],
						'_nfe_nao_emitir'             	=> $_POST['nao_emitir'],
						'_nfe_transporte_marca' 		=> $_POST['transporte_marca'],
						'_nfe_transporte_numeracao' 	=> $_POST['transporte_numeracao'],
						'_nfe_transporte_lacres'    	=> $_POST['transporte_lacres'],
						'_nfe_transporte_cnpj'  		=> $_POST['transporte_cnpj'],
						'_nfe_transporte_razao_social'  => $_POST['transporte_razao_social'],
						'_nfe_transporte_ie'    		=> $_POST['transporte_ie'],
						'_nfe_transporte_cpf'    		=> $_POST['transporte_cpf'],
						'_nfe_transporte_nome'    		=> $_POST['transporte_nome'],
						'_nfe_transporte_endereco'  	=> $_POST['transporte_endereco'],
						'_nfe_transporte_estado'    	=> $_POST['transporte_estado'],
						'_nfe_transporte_cidade'    	=> $_POST['transporte_cidade'],
						'_nfe_transporte_cep'   		=> $_POST['transporte_cep'],
						'_nfe_transporte_placa'    		=> $_POST['transporte_placa'],
						'_nfe_transporte_uf_veiculo'    		=> $_POST['transporte_uf_veiculo'],
						'_nfe_transporte_seguro'    	=> str_replace(',', '.', $_POST['transporte_seguro']),
					);
					foreach ($info as $key => $value){
						if (isset($value)) update_post_meta($post_id, $key, $value);
					}
				}
    }

		// Create new fields for variations
		function variation_field_nfe( $loop, $variation_data, $variation ) {
		  echo '<div class="variation-custom-fields">';
					woocommerce_wp_text_input(
		        array(
		          'id'          => '_nfe_codigo_ncm['. $loop .']',
		          'label'       => __( 'Código NCM', $domain ),
		          'placeholder' => '',
		          'wrapper_class' => 'form-row form-row-first',
		          'value'       => get_post_meta($variation->ID, '_nfe_codigo_ncm', true)
		        )
		      );
		  echo "</div>";
		}

		/** Save new fields for variations */
		function save_variation_data( $variation_id, $i) {
		    // Text Field
		    $codigo_ncm = stripslashes( $_POST['_nfe_codigo_ncm'][$i] );
				if( ! empty( $codigo_ncm ) ) {
		    	update_post_meta( $variation_id, '_nfe_codigo_ncm', esc_attr( $text_field ) );
				}
		}
		function add_category_ncm($taxonomy){ ?>

			<div class="form-field term-ncm-wrap">
				<label for="term-ncm">NCM</label>
				<input name="term-ncm" id="term-ncm" type="text" size="40" />
				<p>Este valor será utilizado caso o NCM não esteja definido diretamente no produto. Se vazio, será utilizado o NCM geral definido nas configurações da Nota Fiscal.</p>
			</div>
			<?php
		}
		function edit_category_ncm($term, $taxonomy){
			if(function_exists('get_term_meta')){
				$ncm = get_term_meta($term->term_id, '_ncm', true);
			}
			?>

			<tr class="form-field term-ncm-wrap">
				<th scope="row" valign="top">
					<label>NCM</label>
				</th>
				<td>
					<input name="term-ncm" id="term-ncm" type="text" size="40" value="<?php echo $ncm; ?>"/>
					<p class="description">Este valor será utilizado caso o NCM não esteja definido diretamente no produto. Se vazio, será utilizado o NCM geral definido nas configurações da Nota Fiscal.</p>
				</td>
			</div>
			<?php
		}
		function save_product_cat_ncm( $term_id, $tag_id ){
			if ( isset( $_POST['term-ncm'] ) ) {
        update_term_meta( $term_id, '_ncm', $_POST['term-ncm']);
    	}
		}
		function is_categories_ncm_valid( $post_id ){
			$product_cat = get_the_terms($post_id, 'product_cat');
			$product_ncm = get_post_meta($post_id, '_nfe_codigo_ncm', true);
			if($product_ncm || !is_array($product_cat)) return true;
			$ncm_categories = array();
			foreach($product_cat as $cat){
				if(function_exists('get_term_meta')){
					$ncm = get_term_meta($cat->term_id, '_ncm', true);
				}else{
					$ncm = null;
				}
	      if($ncm) $ncm_categories[] = $ncm;
	    }
			if(count($ncm_categories) > 1){
				return false;
			}
			return true;
		}
		function cat_ncm_warning(){
			global $post;
			$post_type = get_post_type($post);
			if($post_type == 'product' && !$this->is_categories_ncm_valid($post->ID)){ ?>

				<div class="error" style="background-color: #f2dede; color: #a94442;"><p><strong>Atenção:</strong> Duas ou mais categorias deste produto possuem o NCM definido e, caso diferentes, podem ter o valor incorreto durante a emissão da NF-e.</p></div>

			<?php }
		}
	  public function add_admin_menu_item() {

	  	$ids_db = get_option('wmbr_auto_invoice_errors');

      if ( is_array($ids_db) && count($ids_db) > 0 ) {
      	$count = count($ids_db);
      	$page_count = '('.$count.') ';
        $update_count = " <span class='update-plugins rsssl-update-count'><span class='update-count' style='background: red;'>$count</span></span>";
      } else {
          $update_count = " <span class='update-plugins rsssl-update-count'><span class='update-count'>0</span></span>";
      }

	  	$title = 'Notificações Nota Fiscal';
	    $capability = 'manage_options';
	    $menu_slug = 'wmbr_page_auto_invoice_errors';


	    add_submenu_page( 'woocommerce', $page_count . $title, $title . $update_count, $capability, $menu_slug, array($this, 'page_auto_invoice_errors'));

	  }
	  public function page_auto_invoice_errors() {

			$ids_db = get_option('wmbr_auto_invoice_errors');
			include_once(plugin_dir_path(dirname(__FILE__)).'/inc/templates/page-reports.php');

	  }
		/**
		 * Show the list of automatic invoice errors
		 **/
		function alert_auto_invoice_errors() {

			$ids_db = get_option('wmbr_auto_invoice_errors');

			if ( !empty($ids_db) ) {

				$menu_url = menu_page_url('wmbr_page_auto_invoice_errors', false);
				$message = __( '<strong>[WebmaniaBR® Nota Fiscal] Aviso:</strong> Foram localizados pedidos com erros de emissão. <a href="'.$menu_url.'">Visualizar Pedidos</a>');

				WC_NFe()->add_error( $message );

			}

		}
	  public function wmbr_remove_order_id_auto_invoice(){

	    $secure = check_ajax_referer( 'G7EZCEv3tA', 'sec_nonce', false);

	    if($secure){

	      $order_id = $_POST['order_id'];
	      $orders_auto_invoice_errors = get_option('wmbr_auto_invoice_errors', array());

				if ( is_array($orders_auto_invoice_errors) ) {
					if ( !array_key_exists($order_id, $orders_auto_invoice_errors) ) return false;

					unset($orders_auto_invoice_errors[$order_id]);
					update_option( 'wmbr_auto_invoice_errors', $orders_auto_invoice_errors );
				}

	      echo json_encode(array('success' => true));

	    }

	    die();

	  }
		function nfe_callback(){
			if($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['order_key'] && $_GET['order_id']) {
				$nfe_data = $_POST['data'];
				$nfe_order_id = (int) $nfe_data['ID'];
				$order_key = esc_attr($_GET['order_key']);
				$order_id = (int) $_GET['order_id'];
				$order = wc_get_order($order_id);
				if ($order->order_key != $order_key || $nfe_order_id != $order_id || ! $order) {
					header( 'HTTP/1.1 401 Unauthorized' );
					exit;
				}
				$order_nfe_data = get_post_meta($order_id, 'nfe', true);
				$is_new = true;
				if ( is_array($order_nfe_data) ) {
					foreach($order_nfe_data as $key => $order_nfe){
						$current_status = $order_nfe['status'];
						$received_status = $_POST['status'];
						if($order_nfe['uuid'] == $_POST['uuid'] && $current_status != $received_status) {
							$order_nfe_data[$key]['status'] = $received_status;
						}
						if ( $order_nfe['uuid'] == $_POST['uuid'] ) {
							$is_new = false;
						}
					}
				} else {
					$order_nfe_data = array();
				}
				if ( $is_new ) {
					$order_nfe_data[] = array(
						'uuid'   => (string) $_POST['uuid'],
						'status' => (string) $_POST['status'],
						'chave_acesso' => (string) $_POST['chave'],
						'n_recibo' => (int) $_POST['recibo'],
						'n_nfe' => (int) $_POST['nfe'],
						'n_serie' => (int) $_POST['serie'],
						'url_xml' => (string) $_POST['xml'],
						'url_danfe' => (string) $_POST['danfe'],
						'data' => date_i18n('d/m/Y'),
					);
				}
				update_post_meta($order_id, 'nfe', $order_nfe_data);
			}
		}
		function order_data_after_billing_address( $order ){
			global $domain;
			$html = '<div class="clear"></div>';
			$html .= '<div class="wcbcf-address">';
			if ( ! $order->get_formatted_billing_address() ) {
				$html .= '<p class="none_set"><strong>' . __( 'Endereço', $domain ) . ':</strong> ' . __( 'Nenhum endereço de cobrança definido.', $domain ) . '</p>';
			} else {
				$html .= '<p><strong>' . __( 'Endereço', $domain ) . ':</strong><br />';
					$html .= $order->get_formatted_billing_address();
				$html .= '</p>';
			}
			$html .= '<h4>' . __( 'Informações do cliente', $domain ) . '</h4>';
			$html .= '<p>';
			// Person type information.
			if ( 1 == $order->billing_persontype ) $html .= '<strong>' . __( 'CPF', $domain ) . ': </strong>' . esc_html( $order->billing_cpf ) . '<br />';
			if ( 2 == $order->billing_persontype ) {
				$html .= '<strong>' . __( 'Razão Social', $domain ) . ': </strong>' . esc_html( $order->billing_company ) . '<br />';
				$html .= '<strong>' . __( 'CNPJ', $domain ) . ': </strong>' . esc_html( $order->billing_cnpj ) . '<br />';
				if ( ! empty( $order->billing_ie ) ) {
					$html .= '<strong>' . __( 'I.E', $domain ) . ': </strong>' . esc_html( $order->billing_ie ) . '<br />';
				}
			}
			if ( ! empty( $order->billing_birthdate ) ) {
				// Birthdate information.
				$html .= '<strong>' . __( 'Data de nascimento', $domain ) . ': </strong>' . esc_html( $order->billing_birthdate ) . '<br />';
				// Sex Information.
				$html .= '<strong>' . __( 'Sexo', $domain ) . ': </strong>' . esc_html( $order->billing_sex ) . '<br />';
			}
			$html .= '<strong>' . __( 'Telefone', $domain ) . ': </strong>' . esc_html( str_replace("?", "", $order->billing_cellphone) ) . '<br />';
			// Cell Phone Information.
			if ( ! empty( str_replace("?", "", $order->billing_cellphone) ) ) {
				$html .= '<strong>' . __( 'Telefone Cel.', $domain ) . ': </strong>' . esc_html( str_replace("?", "", $order->billing_cellphone) ) . '<br />';
			}
			$html .= '<strong>' . __( 'Email', $domain ) . ': </strong>' . make_clickable( esc_html( $order->billing_email ) ) . '<br />';
			$html .= '</p>';
			$html .= '</div>';
			echo $html;
		}
		public function order_data_after_shipping_address( $order ) {
			global $post, $domain;
			$html = '<div class="clear"></div>';
			$html .= '<div class="wcbcf-address">';
			if ( ! $order->get_formatted_shipping_address() ) {
				$html .= '<p class="none_set"><strong>' . __( 'Endereço', $domain ) . ':</strong> ' . __( 'Nenhum endereço de envio definido.', $domain ) . '</p>';
			} else {
				$html .= '<p><strong>' . __( 'Endereço', $domain ) . ':</strong><br />';
				$html .= $order->get_formatted_shipping_address();
				$html .= '</p>';
			}
			if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' == get_option( 'woocommerce_enable_order_comments', 'yes' ) ) && $post->post_excerpt ) {
				$html .= '<p><strong>' . __( 'Nota do cliente', $domain ) . ':</strong><br />' . nl2br( esc_html( $post->post_excerpt ) ) . '</p>';
			}
			$html .= '</div>';
			echo $html;
		}

		public function nfe_custom_field_bulk_edit_input() {
		    ?>
		    <div class="inline-edit-group">
					<label class="alignleft _nfe_classe_imposto">
		         <span class="title">Classe de Imposto</span>
		         <span class="input-text-wrap">
		            <input type="text" name="_nfe_classe_imposto" class="text" value="">
		         </span>
					</label>
					<label class="alignleft _nfe_classe_imposto">
		         <span class="title">GTIN (antigo código EAN)</span>
		         <span class="input-text-wrap">
		            <input type="text" name="_nfe_codigo_ean" class="text" value="">
		         </span>
					</label>
					<label class="alignleft _nfe_gtin_tributavel">
		         <span class="title">GTIN tributável</span>
		         <span class="input-text-wrap">
		            <input type="text" name="_nfe_gtin_tributavel" class="text" value="">
		         </span>
					</label>
					<label class="alignleft _nfe_codigo_ncm">
		         <span class="title">Código NCM</span>
		         <span class="input-text-wrap">
		            <input type="text" name="_nfe_codigo_ncm" class="text" value="">
		         </span>
					</label>
					<label class="alignleft _nfe_codigo_cest">
		         <span class="title">Código CEST</span>
		         <span class="input-text-wrap">
		            <input type="text" name="_nfe_codigo_cest" class="text" value="">
		         </span>
					</label>
					<label class="alignleft _nfe_cnpj_fabricante">
		         <span class="title">CNPJ do Frabricante</span>
		         <span class="input-text-wrap">
		            <input type="text" name="_nfe_cnpj_fabricante" class="text" value="">
		         </span>
					</label>

		    </div>
		    <?php
		}

		public function nfe_custom_field_bulk_edit_save( $product ) {
			$post_id = $product->get_id();
			if ( isset( $_REQUEST['_nfe_classe_imposto'] ) && !empty($_REQUEST['_nfe_classe_imposto']) ) {
				$custom_field = $_REQUEST['_nfe_classe_imposto'];
				update_post_meta( $post_id, '_nfe_classe_imposto', wc_clean( $custom_field ) );
			}
			if ( isset( $_REQUEST['_nfe_codigo_ean'] ) && !empty($_REQUEST['_nfe_codigo_ean']) ) {
				$custom_field = $_REQUEST['_nfe_codigo_ean'];
				update_post_meta( $post_id, '_nfe_codigo_ean', wc_clean( $custom_field ) );
			}
			if ( isset( $_REQUEST['_nfe_gtin_tributavel'] ) && !empty($_REQUEST['_nfe_gtin_tributavel']) ) {
				$custom_field = $_REQUEST['_nfe_gtin_tributavel'];
				update_post_meta( $post_id, '_nfe_gtin_tributavel', wc_clean( $custom_field ) );
			}
			if ( isset( $_REQUEST['_nfe_codigo_ncm'] ) && !empty($_REQUEST['_nfe_codigo_ncm']) ) {
				$custom_field = $_REQUEST['_nfe_codigo_ncm'];
				update_post_meta( $post_id, '_nfe_codigo_ncm', wc_clean( $custom_field ) );
			}
			if ( isset( $_REQUEST['_nfe_codigo_cest'] ) && !empty($_REQUEST['_nfe_codigo_cest']) ) {
				$custom_field = $_REQUEST['_nfe_codigo_cest'];
				update_post_meta( $post_id, '_nfe_codigo_cest', wc_clean( $custom_field ) );
			}
			if ( isset( $_REQUEST['_nfe_cnpj_fabricante'] ) && !empty($_REQUEST['_nfe_cnpj_fabricante']) ) {
				$custom_field = $_REQUEST['_nfe_cnpj_fabricante'];
				update_post_meta( $post_id, '_nfe_cnpj_fabricante', wc_clean( $custom_field ) );
			}
		}
}
