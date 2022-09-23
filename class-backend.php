<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WooCommerceNFeBackend extends WooCommerceNFe {

	function __construct(){

		add_action( 'admin_notices', array($this, 'display_messages') );
		add_action( 'admin_notices', array($this, 'validate_certificate') );
		add_action( 'admin_init', array($this, 'wmbr_compatibility_issues') );
		add_action( 'add_meta_boxes', array($this, 'register_metabox_listar_nfe') );
		add_action( 'add_meta_boxes', array($this, 'register_metabox_nfe_emitida') );
		add_action( 'init', array($this, 'atualizar_status_nota'), 100 );
		add_action( 'woocommerce_api_nfe_callback', array($this, 'nfe_callback') );
		add_action( 'woocommerce_api_nfse_callback', array($this, 'nfse_callback') );
		add_action( 'save_post', array($this, 'save_informacoes_fiscais'), 10, 2);
		add_action( 'admin_head', array($this, 'style') );
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_status_column_header' ), 20 );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'add_order_status_column_content' ) );
		add_action( 'woocommerce_order_actions', array( $this, 'add_order_meta_box_actions' ) );
		add_action( 'woocommerce_order_action_wc_nfe_emitir', array( $this, 'process_order_meta_box_actions' ) );
		add_action( 'admin_footer-edit.php', array( $this, 'add_order_bulk_actions' ) );
		add_action( 'load-edit.php', array( $this, 'process_order_bulk_actions' ) );
		add_filter( 'woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 100 );
		add_action( 'woocommerce_settings_tabs_woocommercenfe_tab', array($this, 'settings_tab'));
		add_action( 'woocommerce_update_options_woocommercenfe_tab', array($this, 'update_settings' ));
		add_action( 'admin_enqueue_scripts', array($this, 'global_admin_scripts') );
		add_action( 'product_cat_add_form_fields', array($this, 'add_category_ncm'));
		add_action( 'product_cat_edit_form_fields', array($this, 'edit_category_ncm'), 10, 2);
		add_action( 'edited_product_cat', array($this, 'save_product_cat_ncm'), 10, 2);
		add_action( 'create_product_cat', array($this, 'save_product_cat_ncm'), 10, 2);
		add_action( 'admin_notices', array($this, 'cat_ncm_warning'));
		add_action( 'admin_menu', array($this, 'add_admin_menu_item'));
		add_action( 'admin_init', array($this, 'alert_auto_invoice_errors'));
		add_action( 'wp_ajax_wmbr_remove_order_id_auto_invoice', array($this, 'wmbr_remove_order_id_auto_invoice'));
		add_filter( 'woocommerce_admin_shipping_fields', array($this, 'extra_shipping_fields') );
		add_action( 'admin_enqueue_scripts', array($this, 'scripts') );
		add_action( 'wp_ajax_force_digital_certificate_update', array($this, 'ajax_force_certificate_update') );
		
		//NCM in product variation
		add_action( 'woocommerce_variation_options_dimensions', array($this, 'add_ncm_field_product_variation'), 10, 3);
		add_action( 'woocommerce_save_product_variation', array($this, 'save_ncm_field_product_variation'), 10, 2 );

		/**
		 * Plugin: Brazilian Market on WooCommerce (Customized)
		 * @author Claudio Sanches
		 * @link https://github.com/claudiosmweb/woocommerce-extra-checkout-fields-for-brazil
		**/
		if (
			!WooCommerceNFe::is_extra_checkout_fields_activated() &&
			get_option('wc_settings_woocommercenfe_tipo_pessoa') == 'yes'
		){

			add_filter( 'woocommerce_customer_meta_fields', array( $this, 'customer_meta_fields' ) );
			add_filter( 'woocommerce_user_column_billing_address', array( $this, 'user_column_billing_address' ), 1, 2 );
			add_filter( 'woocommerce_user_column_shipping_address', array( $this, 'user_column_shipping_address' ), 1, 2 );
			add_filter( 'woocommerce_admin_billing_fields', array( $this, 'shop_order_billing_fields' ) );
			add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'shop_order_shipping_fields' ) );
			add_filter( 'woocommerce_found_customer_details', array( $this, 'customer_details_ajax' ) );
			add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_custom_shop_data' ) );
			add_action( 'woocommerce_api_create_order', array( $this, 'wc_api_save_custom_shop_data' ), 10, 2 );
			add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'order_data_after_billing_address' ) );
			add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'order_data_after_shipping_address' ) );
			add_filter( 'woocommerce_api_order_response', array( $this, 'api_order_response' ), 100, 4 );
			add_filter( 'woocommerce_api_customer_response', array( $this, 'api_customer_response' ), 100, 4 );

		}

	}

	/**
	 * Scripts
	 *
	 * @return void
	 */
  function scripts(){

		global $version_woonfe;

		wp_register_script( 'woocommercenfe_maskedinput', '//cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.js', array('jquery'), $version_woonfe, true );
    wp_register_script( 'woocommercenfe_admin_script', apply_filters( 'woocommercenfe_plugins_url', plugins_url( 'assets/js/admin_scripts.js', __FILE__ ) ), null, $version_woonfe );
    wp_register_style( 'woocommercenfe_admin_style', apply_filters( 'woocommercenfe_plugins_url', plugins_url( 'assets/css/admin_style.css', __FILE__ ) ), null, $version_woonfe );
    wp_enqueue_style( 'woocommercenfe_admin_style' );
		wp_enqueue_script( 'woocommercenfe_admin_script' );
		wp_enqueue_script( 'woocommercenfe_maskedinput' );

	}

	/**
	 * Global Scripts
	 *
	 * @return void
	 */
	function global_admin_scripts(){

    wp_register_script( 'woocommercenfe_table_scripts', apply_filters( 'woocommercenfe_plugins_url', plugins_url( 'assets/js/nfe_table.js', __FILE__ ) ) );
    wp_register_style( 'woocommercenfe_table_style', apply_filters( 'woocommercenfe_plugins_url', plugins_url( 'assets/css/nfe_table.css', __FILE__ ) ) );
    wp_enqueue_style( 'woocommercenfe_table_style' );
		wp_enqueue_script( 'woocommercenfe_table_scripts' );

	}

	/**
	 * Add new settings tag
	 *
	 * @return array
	 */
	function add_settings_tab( $settings_tabs ){

		$settings_tabs['woocommercenfe_tab'] = __( 'Nota Fiscal', $this->domain );

		return $settings_tabs;

	}

	/**
	 * Settings tab content
	 *
	 * @return html
	 */
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
	$certificate = json_decode($this->validate_certificate(false, true));

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

<button type="button" class="button-primary" id="update-digital-certificate">Atualizar Certificado A1</button>

<h3>Informações de Transportadoras</h3>
<p>Cadastre as transportadoras particulares utilizadas em sua loja virtual para identificação na Nota Fiscal Eletrônica. <br>Observação: Para o transporte dos Correios não há necessidade de preenchimento dos dados.</p>

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
					<p><label class="nfe-shipping-label">Razão Social: </label><input type="text" name="shipping_info_rs_0"/></p>
					<p><label class="nfe-shipping-label">CNPJ:</label> <input type="text" name="shipping_info_cnpj_0"/></p>
					<p><label class="nfe-shipping-label">Inscrição estadual:</label> <input type="text" name="shipping_info_ie_0"/></p>
					<p><label class="nfe-shipping-label">Endereço:</label> <input type="text" name="shipping_info_address_0"/></p>
					<p><label class="nfe-shipping-label">CEP:</label> <input type="text" name="shipping_info_cep_0"/></p>
					<p><label class="nfe-shipping-label">Cidade:</label> <input type="text" name="shipping_info_city_0"/></p>
					<p><label class="nfe-shipping-label">UF:</label> <input type="text" name="shipping_info_uf_0"/></p>
			</div>
			<button type="button" class="button wmbr-remove-shipping-info"><span class="dashicons dashicons-no"></span> Remover</button>
		</div>
		<?php echo $this->get_transportadoras_entries(); ?>
	</div>
	<button type="button" class="button-primary" id="wmbr-add-shipping-info">Adicionar Método de Entrega</button>
	<input type="hidden" name="shipping-info-count" value="<?php echo count($transportadoras); ?>" />
</div>

	<?php

	include_once(plugin_dir_path(dirname(__FILE__)).'nota-fiscal-eletronica-woocommerce/templates/payment-setting.php');

	}

	/**
	 * Update Certificate A1
	 *
	 * @return script
	 */
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

	/**
	 * Update Certificate A1
	 *
	 * @return script
	 */
	function update_settings(){

		woocommerce_update_options( $this->get_settings() );

		// Vars
		$count = (int) $_POST['shipping-info-count'];
		$transportadoras = array();
		$payment_methods = array();
		$patment_descs = array();
		$cnpj_payment_methods = array();

		if ($method = @$_POST['payment_method']){
			$desc = @$_POST['payment_desc'];
			foreach($method as $key => $value){
				$payment_methods[$key] = sanitize_text_field($value);
				$payment_descs[$key] = sanitize_text_field($desc[$key]);
			}
		}

		// Mount carriers
		if ($_POST){
			for ($i = 1; $i < $count+1; $i++) {

				$id = $_POST['shipping_info_method_'.$i];
				if (!$id) continue;
				$transportadoras[$id] = array();
				$keys = array(
					'razao_social' => 'rs',
					'cnpj'         => 'cnpj',
					'ie'           => 'ie',
					'address'      => 'address',
					'cep'          => 'cep',
					'city'         => 'city',
					'uf'           => 'uf'
				);

				foreach($keys as $name => $post_key){
					$transportadoras[$id][$name] = sanitize_text_field($_POST['shipping_info_'.$post_key.'_'.$i]);
				}

			}
		}

		// Update
		update_option('wc_settings_woocommercenfe_transportadoras', $transportadoras);
		update_option('wc_settings_woocommercenfe_payment_methods', $payment_methods);
		update_option('wc_settings_woocommercenfe_payment_descs', $payment_descs);
		update_option('wc_settings_woocommercenfe_cnpj_payments', $cnpj_payment_methods);

	}

	/**
	 * WP-Admin plugin settings
	 *
	 * @return array
	 */
	function get_settings(){

		$auto_invoice_report_url = get_admin_url(get_current_blog_id(), '/admin.php?page=wmbr_page_auto_invoice_errors');

		$settings = array(
			'title' => array(
				'name'     => __( 'Credenciais de Acesso (Nota Fiscal de Produto)', $this->domain ),
				'type'     => 'title',
				'desc'     => 'Informe os acessos da sua aplicação - API 1.0'
			),
			'consumer_key' => array(
				'name' => __( 'Consumer Key', $this->domain ),
				'type' => 'text',
				'css' => 'width:300px;',
				'id'   => 'wc_settings_woocommercenfe_consumer_key'
			),
			'consumer_secret' => array(
				'name' => __( 'Consumer Secret', $this->domain ),
				'type' => 'text',
				'css' => 'width:300px;',
				'id'   => 'wc_settings_woocommercenfe_consumer_secret'
			),
			'access_token' => array(
				'name' => __( 'Access Token', $this->domain ),
				'type' => 'text',
				'css' => 'width:300px;',
				'id'   => 'wc_settings_woocommercenfe_access_token'
			),
			'access_token_secret' => array(
				'name' => __( 'Access Token Secret', $this->domain ),
				'type' => 'text',
				'css' => 'width:300px;',
				'id'   => 'wc_settings_woocommercenfe_access_token_secret'
			),
			'section_end_nfe' => array(
				'type' => 'sectionend',
				'id' => 'wc_settings_woocommercenfe_end'
			),
			'title_nfse_credentials' => array(
				'name'     => __( 'Credenciais de Acesso (Nota Fiscal de Serviço)', $this->domain ),
				'type'     => 'title',
				'desc'     => 'Informe os acessos da sua aplicação - API 2.0'
			),
			'bearer_access_token' => array(
				'name' => __( 'Bearer Access Token', $this->domain ),
				'type' => 'text',
				'css' => 'width:300px;',
				'id'   => 'wc_settings_woocommercenfe_bearer_access_token'
			),
			'section_end_nfse' => array(
				'type' => 'sectionend',
				'id' => 'wc_settings_woocommercenfe_end'
			),
			'title_environment' => array(
				'name'     => __( 'Ambiente de emissão', $this->domain ),
				'type'     => 'title',
				'desc'     => 'Informe o ambiente de emissão. Para validade fiscal (produção) ou para testes (desenvolvimento).'
			),
			'ambiente' => array(
				'name' => __( 'Ambiente', $this->domain ),
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
				'name'     => __( 'Configuração Padrão', $this->domain ),
				'type'     => 'title',
				'desc'     => 'A configuração padrão será utilizada para todos os produtos.<br>Caso deseje a configuração também pode ser personalizada em cada produto ou categoria.'
			),
			'emissao_automatica' => array(
				'name' => __( 'Emissão automática', $this->domain ),
				'type' => 'radio',
				'options' => array(
					'0' => 'Não emitir automaticamente',
					'1' => 'Sempre que o status do pedido é alterado para Processando (Pagamento confirmado)',
					'2' => 'Sempre que o status do pedido é alterado para Concluído'
			),
				'default' => '0',
				'id'   => 'wc_settings_woocommercenfe_emissao_automatica'
			),
			'envio_email' => array(
			'name' => __( 'Envio automático de E-mail', $this->domain ),
			'type' =>'checkbox',
			'desc' => __( 'Enviar e-mail para o cliente após a emissão da Nota Fiscal'),
			'default' => 'yes',
			'id'   => __('wc_settings_woocommercenfe_envio_email'),
			),
			'data_emissao' => array(
				'name' => __( 'Emissão com Data do Pedido', $this->domain ),
				'type' =>'checkbox',
			'desc' => __( 'Emissão de Nota Fiscal com a data do pedido (retroativa)'),
				'default' => 'no',
				'id'   => 'wc_settings_woocommercenfe_data_emissao'
			),
			'email_notification' => array(
				'name' => __( 'Notificação de erros', $this->domain ),
				'type' => 'email',
				'desc' => __( 'Informe um e-mail para notificações de erros na emissão ou <a target="_blank" href="'.$auto_invoice_report_url.'">visualize as notificações</a>.'),
				'css' => 'width:300px;',
				'id'   => 'wc_settings_woocommercenfe_email_notification'
			),
			'section_end_3' => array(
				'type' => 'sectionend',
				'id' => 'wc_settings_woocommercenfe_end2'
			),
			'title_nfse' => array(
				'name'     => __( 'Configurações (Nota Fiscal de Serviço)', $this->domain ),
				'type'     => 'title',
				'desc'     => 'Configuração de campos específicos para a emissão de NFS-e.'
			),
			'imposto_nfse' => array(
				'name' => __( 'Classe de Imposto (NFS-e)', $this->domain ),
				'type' => 'text',
				'id'   => 'wc_settings_woocommercenfe_imposto_nfse'
			),
			'section_end_nfse2' => array(
				'type' => 'sectionend',
				'id' => 'wc_settings_woocommercenfe_end_nfse'
			),
			'title_nfe' => array(
				'name'     => __( 'Configurações (Nota Fiscal de Produto)', $this->domain ),
				'type'     => 'title',
				'desc'     => 'Configuração de campos específicos para a emissão de NF-e.'
			),
			'natureza_operacao' => array(
				'name' => __( 'Natureza da Operação', $this->domain ),
				'type' => 'text',
				'css' => 'width:300px;',
				'id'   => 'wc_settings_woocommercenfe_natureza_operacao'
			),
			'imposto' => array(
				'name' => __( 'Classe de Imposto (NF-e)', $this->domain ),
				'type' => 'text',
				'id'   => 'wc_settings_woocommercenfe_imposto'
			),
			'ncm' => array(
				'name' => __( 'Código NCM', $this->domain ),
				'type' => 'text',
				'id'   => 'wc_settings_woocommercenfe_ncm'
			),
			'cest' => array(
				'name' => __( 'Código CEST', $this->domain ),
				'type' => 'text',
				'id'   => 'wc_settings_woocommercenfe_cest'
			),
			'origem' => array(
				'name' => __( 'Origem dos Produtos', $this->domain ),
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
			'section_end_nfe2' => array(
				'type' => 'sectionend',
				'id' => 'wc_settings_woocommercenfe_end_nfe'
			),
			'title_intermediador' => array(
				'name'     => __( 'Indicativo de Intermediador', $this->domain ),
				'type'     => 'title',
				'desc'     => 'Campos para indicar o intermediador da operação.'
			),
			'intermediador' => array(
				'name' => __( 'Intermediador da operação', $this->domain ),
				'type' => 'select',
				'options' => array(
						'0' => '0 - Operação sem intermediador (em site ou plataforma própria)',
						'1' => '1 - Operação em site ou plataforma de terceiros (intermediadores/marketplace)'
				),
				'css' => 'width:300px;',
				'id'   => 'wc_settings_woocommercenfe_intermediador'
			),
			'cnpj_intermediador' => array(
				'name' => __( 'CNPJ do Intermediador', $this->domain ),
				'type' => 'text',
				'css' => 'width:300px;',
				'id'   => 'wc_settings_woocommercenfe_cnpj_intermediador'
			),
			'id_intermediador' => array(
				'name' => __( 'ID do intermediador', $this->domain ),
				'type' => 'text',
				'css' => 'width:300px;',
				'id'   => 'wc_settings_woocommercenfe_id_intermediador'
			),
			'section_end_intermediador' => array(
				'type' => 'sectionend',
				'id' => 'wc_settings_woocommercenfe_end_intermediador'
			),
			'title4' => array(
			'name'     => __( 'Informações Complementares (Opcional)', $this->domain ),
			'type'     => 'title',
			'desc'     => 'Informações fiscais complementares.'
			),
			'fisco_inf' => array(
				'name' => __( 'Informações ao Fisco', $this->domain ),
				'type' => 'textarea',
				'id'   => 'wc_settings_woocommercenfe_fisco_inf',
			'class' => 'nfe_textarea',
			),
			'cons_inf' => array(
				'name' => __( 'Informações Complementares ao Consumidor', $this->domain ),
				'type' => 'textarea',
				'id'   => 'wc_settings_woocommercenfe_cons_inf',
			'class' => 'nfe_textarea',
			),
			'section_ebanx' => array(
				'type' => 'sectionend',
				'id' => 'wc_settings_woocommercenfe_ebanx'
			),
			'ebanx_title' => array(
				'name'     => __( 'Gateways de Pagamento', $this->domain ),
				'type'     => 'title',
				'desc'     => 'Compatibilidade com EBANX, Pagar.me, PagSeguro e Paypal Plus.'
			),
			'ebanx_parcelas' => array(
				'name' => __( 'Emitir pagamento parcelado como duplicata na Nota Fiscal', $this->domain ),
				'type' => 'checkbox',
				'desc' => __( 'Pagamento parcelado como duplicata na Nota Fiscal', $this->domain ),
				'id'   => 'wc_settings_parcelas_ebanx',
				'default' => 'no',
			),
			'section_end3' => array(
				'type' => 'sectionend',
				'id' => 'wc_settings_woocommercenfe_end3'
			),
			'title6' => array(
				'name'     => __( 'Campos Personalizados no Checkout', $this->domain ),
				'type'     => 'title',
				'desc'     => 'Informe se deseja mostrar os campos na página de Finalizar Compra.'
			),
			'tipo_pessoa' => array(
				'name' => __( 'Exibir Tipo de Pessoa', $this->domain ),
				'type' => 'checkbox',
				'desc' => __( 'Caso esteja marcado exibe os campos de Tipo de Pessoa, CPF, CNPJ e Empresa nas informações de cobrança.', $this->domain ),
				'id'   => 'wc_settings_woocommercenfe_tipo_pessoa',
				'default' => 'yes',
			),
			'mascara_campos' => array(
				'name' => __( 'Habilitar Máscara de Campos', $this->domain ),
				'type' => 'checkbox',
				'desc' => __( 'Caso esteja marcado adiciona máscaras de preenchimento para os campos de CPF e CNPJ.', $this->domain ),
				'id'   => 'wc_settings_woocommercenfe_mascara_campos',
				'default' => 'yes',
			),
			'cep' => array(
				'name' => __( 'Preenchimento automático do Endereço', $this->domain ),
				'type' => 'checkbox',
				'desc' => __( 'Caso esteja marcado o endereço será automaticamente preenchido quando o usuário informar o CEP.', $this->domain ),
				'id'   => 'wc_settings_woocommercenfe_cep',
				'default' => 'yes',
			),
			'section_end4' => array(
				'type' => 'sectionend',
				'id' => 'wc_settings_woocommercenfe_end4'
			)
		);

		// WooCommerce Extra Checkout Fields for Brazil
		if ( WooCommerceNFe::is_extra_checkout_fields_activated() ) {
			unset($settings['title5']);
			unset($settings['tipo_pessoa']);
			unset($settings['mascara_campos']);
			unset($settings['cep']);
		}

		if (
			!NFeGatewayEbanx::is_activated() &&
			!NFeGatewayPagarme::is_activated() &&
			!NFeGatewayPagSeguro::is_activated() &&
			!NFeGatewayPaypal::is_activated()
		) {
			unset($settings['section_ebanx']);
			unset($settings['ebanx_title']);
			unset($settings['ebanx_parcelas']);
		}

		// Return
		return $settings;

	}

	/**
	 * Display Carriers
	 *
	 * @return string
	 */
	function get_transportadoras_entries(){

		$transportadoras = get_option('wc_settings_woocommercenfe_transportadoras', array());
		$html  = '';
		$i = 1;

		foreach ($transportadoras as $key => $transp) {

			$html .= '<div class="entry">';
			$html .= '<div class="shipping-method-col">'.$this->get_shipping_methods_select($i, $key).'</div>';
			$html .= '<div class="shipping-info-col">';
			$html .= '<p><label class="nfe-shipping-label">Razão Social: </label><input type="text" name="shipping_info_rs_'.$i.'" value="'.$transp['razao_social'].'"/></p>';
			$html .= '<p><label class="nfe-shipping-label">CNPJ: </label><input type="text" name="shipping_info_cnpj_'.$i.'" value="'.$transp['cnpj'].'"/></p>';
			$html .= '<p><label class="nfe-shipping-label">Inscrição estadual: </label><input type="text" name="shipping_info_ie_'.$i.'" value="'.$transp['ie'].'"/></p>';
			$html .= '<p><label class="nfe-shipping-label">Endereço: </label><input type="text" name="shipping_info_address_'.$i.'" value="'.$transp['address'].'"/></p>';
			$html .= '<p><label class="nfe-shipping-label">CEP: </label><input type="text" name="shipping_info_cep_'.$i.'" value="'.$transp['cep'].'"/></p>';
			$html .= '<p><label class="nfe-shipping-label">Cidade: </label><input type="text" name="shipping_info_city_'.$i.'" value="'.$transp['city'].'"/></p>';
			$html .= '<p><label class="nfe-shipping-label">UF: </label><input type="text" name="shipping_info_uf_'.$i.'" value="'.$transp['uf'].'"/></p>';
			$html .= '<button type="button" class="button wmbr-remove-shipping-info"><span class="dashicons dashicons-no"></span> Remover</button>';
			$html .= '</div>';
			$html .= '</div>';
			$i++;

		}

		return $html;

	}

	/**
	 * Display Shipping Methods selected
	 *
	 * @return string
	 */
	function get_shipping_methods_select($index = 0, $id = ''){

		// Vars
		$carriers = get_option('wc_settings_woocommercenfe_transportadoras', array());
		$html = '<select class="nfe-shipping-methods-sel" name="shipping_info_method_'.$index.'">';
		$html .= '<option value="">Selecionar</option>';

		// Shipping Methods
		$shipping = new WC_Shipping();
		$shipping->load_shipping_methods();
		$shipping_methods = $shipping->get_shipping_methods();

		// Display options
		foreach ($shipping_methods as $method) {

			// Skip
			if ($method->id == 'correios') {
				continue;
			}

			// Mount HTML Frenet
			if ($method->id == 'frenet'){

				$frenet = NFeUtils::get_frenet_carriers();

				if (!$frenet)
					continue;

				foreach ($frenet->ShippingSeviceAvailableArray as $var){

					$selected = '';

					if ($id){
						((isset($carriers['FRENET_'.$var->ServiceCode]) && $id == 'FRENET_'.$var->ServiceCode) ? $selected = 'selected' : $selected = '');
					}
					$html .= '<option value="FRENET_'.$var->ServiceCode.'" '.$selected.'>Frenet - '.$var->Carrier.' ('.$var->ServiceDescription.')</option>';

				}

				continue;

			}

			// Mount HTML Others Carriers
			($method->id == $id ? $selected = 'selected' : $selected = '');
			$title = $method->get_title();

			if (!$title && isset($method->method_title)){

				$title = $method->method_title;

			}

			$html .= '<option value="'.$method->id.'" '.$selected.'>'.$title.'</option>';

		}

		$html .= '</select>';
		return $html;

	}

	/**
	 * Display Payment Methods selected
	 *
	 * @return string
	 */
	function get_payment_methods_select($method, $index = 0, $id = ''){

		$saved_values = get_option('wc_settings_woocommercenfe_payment_methods', array());

		$options = array(
			'01' => 'Dinheiro',
			'02' => 'Cheque',
			'03' => 'Cartão de Crédito',
			'04' => 'Cartão de Débito',
			'05' => 'Crédito Loja',
			'10' => 'Vale Alimentação',
			'11' => 'Vale Refeição',
			'12' => 'Vale Presente',
			'13' => 'Vale Combustível',
			'15' => 'Boleto Bancário',
			'16' => 'Depósito Bancário',
			'17' => 'Pagamento Instantâneo (PIX)',
			'18' => 'Transferência bancária, Carteira Digital',
			'19' => 'Programa de fidelidade, Cashback, Crédito Virtual',
			'90' => 'Sem pagamento',
			'99' => 'Outros',
		);

		$html = '<select class="nfe-payment-methods-sel" name="payment_method['.$method.']">';
		$html .= '<option value="">Selecionar</option>';

		foreach ($options as $value => $label) {

			$selected = '';

			if (isset($saved_values[$method]) && $saved_values[$method] == $value){
				$selected = 'selected';
			}

			$html .= '<option value="'.$value.'" '.$selected.'>'.$label.'</option>';

		}

		$html .= '</select>';
		return $html;

	}

	/**
	 * Display Payment Desc
	 *
	 * @return string
	 */
	function get_payment_desc_input($method, $index = 0, $id = ''){

		$payment_methods = get_option('wc_settings_woocommercenfe_payment_methods', array());
		$is_method_99 = (isset($payment_methods[$method]) && $payment_methods[$method] == 99) ? true : false;

		$saved_values = get_option('wc_settings_woocommercenfe_payment_descs', array());

		$html = '<input type="text" class="nfe-payment-desc" name="payment_desc['.$method.']" style="width: 400px; ';

		if (!$is_method_99) {
			$html .= 'display: none;';
		} 

		if (isset($saved_values[$method]) && $is_method_99) {
			$html .= '" value="'.$saved_values[$method].'">';
		}
		else {
			$html .= '">';
		}

		return $html;

	}

	/**
	 * Register Metabox
	 *
	 * @return void
	 */
	function register_metabox_nfe_emitida() {

		add_meta_box(
			'woocommernfe_nfe_emitida',
			'Nota Fiscal do Pedido',
			array($this, 'metabox_content_woocommernfe_nfe_emitida'),
			'shop_order',
			'normal',
			'high'
		);
		add_meta_box(
			'woocommernfe_informacoes_adicionais',
			'Informações Fiscais',
			array($this, 'metabox_content_woocommernfe_informacoes_adicionais'),
			'shop_order',
			'side',
			'high'
		);

	}

	/**
	 * Update status
	 *
	 * @return void
	 */
	function atualizar_status_nota() {

		if (!is_admin()) {
			return false;
		}

		if ( isset($_GET['atualizar_nfe']) && $_GET['post'] && $_GET['chave']) {

			$this->get_credentials();
			$post_id = (int) sanitize_text_field($_GET['post']);
			$chave = sanitize_text_field($_GET['chave']);
			$webmaniabr = new NFe($this->settings);
			$response = $webmaniabr->consultaNotaFiscal($chave);
			if (isset($response->error)){

				$this->add_error( __('Erro: '.$response->error, $this->domain) );
				return false;

			} else {

				$new_status = $response->status;
				$nfe_data = get_post_meta($post_id, 'nfe', true);

				foreach ($nfe_data as &$order_nfe) {

					if ($order_nfe['chave_acesso'] == $chave) {
						$order_nfe['status'] = $new_status;
					}

				}

				update_post_meta($post_id, 'nfe', $nfe_data);
				$this->add_success( 'NF-e atualizada com sucesso' );

			}

		}

	}

	/**
	 * Metabox content
	 *
	 * @return html
	 */
	function metabox_content_woocommernfe_nfe_emitida( $post ) {

		$nfe_data = get_post_meta($post->ID, 'nfe', true);
		if (empty($nfe_data)):

	?>

<p>Nenhuma nota emitida para este pedido</p>

<?php else:
	$nfe_data = array_reverse($nfe_data);
?>
<div class="all-nfe-info">
<div class="head">
<h4 class="head-title">Data</h4>
<h4 class="head-title modelo-column">Modelo</h4>
<h4 class="head-title n-column">Nº</h4>
<h4 class="head-title danfe-column">PDF</h4>
<h4 class="head-title status-column">Status</h4>
</div>
<div class="body">
<?php foreach($nfe_data as $order_nfe):
	(isset($order_nfe['data']) ? $data_nfe = $order_nfe['data'] : $data_nfe = '' );
	if (isset($order_nfe['modelo']) && $order_nfe['modelo'] == 'nfse') {
		$modelo_nfe = 'NFS-e';
	}
	else if (isset($order_nfe['modelo']) && $order_nfe['modelo'] == 'lote_rps') {
		$modelo_nfe = 'Lote RPS';
	}
	else {
		$modelo_nfe = 'NF-e';
	}
	(isset($order_nfe['n_nfe']) ? $numero_nfe = $order_nfe['n_nfe'] : $numero_nfe = '---' );
	(isset($order_nfe['chave_acesso']) ? $chave_acesso_nfe = $order_nfe['chave_acesso'] : $chave_acesso_nfe = '' );
	(isset($order_nfe['status']) ? $status_nfe = $order_nfe['status'] : $status_nfe = '' );
	(isset($order_nfe['url_xml']) ? $xml_nfe = $order_nfe['url_xml'] : $xml_nfe = '' );
	(isset($order_nfe['n_recibo']) ? $recibo_nfe = $order_nfe['n_recibo'] : $recibo_nfe = '' );
	(isset($order_nfe['n_serie']) ? $serie_nfe = $order_nfe['n_serie'] : $serie_nfe = '' );
	if ($status_nfe == 'processando') $status_nfe = 'processamento';
	if (!$order_nfe['url_danfe_simplificada']) $order_nfe['url_danfe_simplificada'] = str_replace('/danfe/', '/danfe/simples/', $order_nfe['url_danfe']);
	if (!$order_nfe['url_danfe_etiqueta']) $order_nfe['url_danfe_etiqueta'] = str_replace('/danfe/', '/danfe/etiqueta/', $order_nfe['url_danfe']);
	if ($modelo_nfe == 'Lote RPS' && $status_nfe == 'processado') continue;
	?>
	<div class="single">
		<div>
		<h4 class="body-info"><?php echo $data_nfe; ?></h4>
		<h4 class="body-info modelo-column"><?php echo $modelo_nfe; ?></h4>
		<h4 class="body-info n-column"><?php echo $numero_nfe; ?></h4>
		<h4 class="body-info danfe-column">
			<?php if ($modelo_nfe == 'Lote RPS') { ?>
			<span>---</span>
			<?php } else if ($modelo_nfe == 'NFS-e') {
				if (isset($order_nfe['url_pdf']) && !empty($order_nfe['url_pdf'])) { ?>
				<a class="unstyled" target="_blank" href="<?php echo $order_nfe['url_pdf'] ?>"><span class="wrt">PDF </span><span class="dashicons dashicons-media-text danfe-icon"></span></a>|
			<?php }
				if (isset($order_nfe['pdf_rps']) && !empty($order_nfe['pdf_rps'])) { ?>
				<a class="unstyled" target="_blank" href="<?php echo $order_nfe['pdf_rps'] ?>"><span class="wrt"> Darps </span><span class="dashicons dashicons-media-text danfe-icon"></span></a>
			<?php }
			} else { ?>
			<a class="unstyled" target="_blank" href="<?php echo $order_nfe['url_danfe'] ?>"><span class="wrt">Danfe </span><span class="dashicons dashicons-media-text danfe-icon"></span></a>|
			<a class="unstyled" target="_blank" href="<?php echo $order_nfe['url_danfe_simplificada'] ?>"><span class="wrt"> Danfe Simples </span><span class="dashicons dashicons-media-text danfe-icon"></span></a>|
			<a class="unstyled" target="_blank" href="<?php echo $order_nfe['url_danfe_etiqueta'] ?>"><span class="wrt"> Danfe Etiqueta</span><span class="dashicons dashicons-media-text danfe-icon"></span></a>
			<?php } ?>
		</h4>
		<?php
			$post_url = get_edit_post_link($post->ID);
			$update_url = $post_url.'&atualizar_nfe=true&chave='.$chave_acesso_nfe;
		?>
		<h4 class="body-info status-column"><span class="nfe-status <?php echo $status_nfe; ?>"><?php echo $status_nfe; ?></span>
		<?php if (!in_array($modelo_nfe, ['NFS-e', 'Lote RPS'])) { ?><a class="unstyled" href="<?php echo $update_url; ?>"><span class="dashicons dashicons-image-rotate update-nfe"></span></a><?php } ?>
		</h4></div>
		<div class="extra">
			<ul>
			  <?php if ($chave_acesso_nfe) { ?><li><strong>Chave:</strong> <?php echo $chave_acesso_nfe; ?></li><?php } ?>
				<?php if ($recibo_nfe) { ?><li><strong>Recibo:</strong> <?php echo $recibo_nfe; ?></li><?php } ?>
				<?php if ($modelo_nfe != 'Lote RPS') { ?>
					<li><strong>Série:</strong> <?php echo $serie_nfe ?></li>
				<?php } ?>
				<li><strong>Arquivo XML:</strong> <a target="_blank" href="<?php echo $xml_nfe.'?download=1'; ?>">Download XML</a></li>
				<?php if ($status_nfe == 'reprovado' && isset($order_nfe['motivo'])) { ?>
					<li><strong>Motivo:</strong> <?php echo $order_nfe['motivo']; ?></li>
				<?php } ?>
			</ul>
		</div>
		<span class="dashicons dashicons-arrow-down-alt2 expand-nfe"></span>
	</div>
<?php endforeach; ?>
</div>
</div>

		<?php endif;

	}

	/**
	 * Metabox content
	 *
	 * @return html
	 */
	function metabox_content_woocommernfe_informacoes_adicionais( $post ) {

		// Vars
		$contribuinte = get_post_meta($post->ID, '_nfe_contribuinte', true);
		$modalidade_frete = get_post_meta($post->ID, '_nfe_modalidade_frete', true);
		$volume_checked = get_post_meta($post->ID, '_nfe_volume_weight', true);
		$installments_checked = get_post_meta($post->ID, '_nfe_installments', true);
		$nfe_installments_n = get_post_meta($post->ID, '_nfe_installments_n', true);
		$nfe_installments_n = ($nfe_installments_n) ? $nfe_installments_n : '1';
		$nfe_installments_due_date = get_post_meta( $post->ID, '_nfe_installments_due_date', true );
		$nfe_installments_value = get_post_meta( $post->ID, '_nfe_installments_value', true );
		$additional_info_checked = get_post_meta( $post->ID, '_nfe_additional_info', true );
		$nfe_additional_info_text = get_post_meta( $post->ID, '_nfe_additional_info_text', true );
		$info_intermediador_checked = get_post_meta( $post->ID, '_nfe_info_intermediador', true );
		$info_intermediador_type = get_post_meta( $post->ID, '_nfe_info_intermediador_type', true );
		$info_intermediador_cnpj = get_post_meta( $post->ID, '_nfe_info_intermediador_cnpj', true );
		$info_intermediador_id = get_post_meta( $post->ID, '_nfe_info_intermediador_id', true );

	?>
	<script>
		jQuery(function($){

			<?php if ($volume_checked && $volume_checked == 'on'){ ?>
				$('.transporte').show();
			<?php } ?>
			<?php if ($installments_checked && $installments_checked == 'on'){ ?>
				$('.nfe_installments').show();
			<?php } ?>
			<?php if ($additional_info_checked && $additional_info_checked == 'on'){ ?>
				$('.nfe_additional_info_text').show();
			<?php } ?>
			<?php if ($info_intermediador_checked && $info_intermediador_checked == 'on'){ ?>
				$('.nfe_info_intermediador').show();
			<?php } ?>
			
		});
	</script>

	<div class="inside" style="padding:0!important;">
		<div class="field emitir_ambiente" style="margin-bottom:10px;">
			<p class="label" style="margin-bottom:8px;">
			<input type="checkbox" name="emitir_homologacao"/>
			<label class="title">Emitir em homologação</label>
			</p>
		</div>
		<div class="field emitir_ambiente" style="margin-bottom:10px;">
			<p class="label" style="margin-bottom:8px;">
			<input type="checkbox" name="previa_danfe"/>
			<label class="title">Pré-visualizar Danfe</label>
			</p>
		</div>
		<hr>
		<div class="field outras_informacoes">
			<p class="label" style="margin-bottom:8px;">
					<label class="title">Natureza da Operação</label>
			</p>
			<input type="text" name="natureza_operacao_pedido" value="<?php echo get_post_meta( $post->ID, '_nfe_natureza_operacao_pedido', true ); ?>" style="width:100%;padding:5px;">
		</div>
		<div class="field outras_informacoes">
			<p class="label" style="margin-bottom:8px;">
					<label class="title">Benefício Fiscal</label>
			</p>
			<input type="text" name="beneficio_fiscal_pedido" value="<?php echo get_post_meta( $post->ID, '_nfe_beneficio_fiscal_pedido', true ); ?>" style="width:100%;padding:5px;">
		</div>
		<input type="hidden" name="wp_admin_nfe" value="1" />
	</div>
	<div class="inside" style="padding:0!important;">
		<div class="field contribuinte">
			<p class="label" style="margin-bottom:8px;">
				<label class="title">Contribuinte ICMS</label>
			</p>
			<select name="nfe_contribuinte" id="nfe_contribuinte">
				<option value="null" <?php if (!is_numeric($modalidade_frete)) echo 'selected'; ?> ><?php _e( 'Selecionar', $this->domain ); ?></option>
				<option value="1" <?php if (is_numeric($contribuinte) && $contribuinte == '1') echo 'selected'; ?> ><?php _e( 'Contribuinte ICMS', $this->domain ); ?></option>
				<option value="2" <?php if (is_numeric($contribuinte) && $contribuinte == '2') echo 'selected'; ?> ><?php _e( 'Contribuinte isento de Inscrição no cadastro de Contribuintes do ICMS', $this->domain ); ?></option>
				<option value="9" <?php if (is_numeric($contribuinte) && $contribuinte == '9') echo 'selected'; ?> ><?php _e( 'Não Contribuinte, que pode ou não possuir Inscrição Estadual no Cadastro de Contribuintes do ICMS', $this->domain ); ?></option>
			</select>
		</div>
		<div class="field">
			<p class="label" style="margin-bottom:8px;">
				<label class="title">Modalidade do frete</label>
			</p>
			<select name="modalidade_frete" id="modalidade_frete">
				<option value="null" <?php if (!is_numeric($modalidade_frete)) echo 'selected'; ?> ><?php _e( 'Contratação do Frete por conta do Remetente (CIF)', $this->domain ); ?></option>
				<option value="1" <?php if (is_numeric($modalidade_frete) && $modalidade_frete == '1') echo 'selected'; ?> ><?php _e( 'Contratação do Frete por conta do Destinatário (FOB)', $this->domain ); ?></option>
				<option value="2" <?php if (is_numeric($modalidade_frete) && $modalidade_frete == '2') echo 'selected'; ?> ><?php _e( 'Contratação do Frete por conta de Terceiros', $this->domain ); ?></option>
				<option value="3" <?php if (is_numeric($modalidade_frete) && $modalidade_frete == '3') echo 'selected'; ?> ><?php _e( 'Transporte Próprio por conta do Remetente', $this->domain ); ?></option>
				<option value="4" <?php if (is_numeric($modalidade_frete) && $modalidade_frete == '4') echo 'selected'; ?> ><?php _e( 'Transporte Próprio por conta do Destinatário', $this->domain ); ?></option>
				<option value="9" <?php if (is_numeric($modalidade_frete) && $modalidade_frete == '9') echo 'selected'; ?> ><?php _e( 'Sem Ocorrência de Transporte', $this->domain ); ?></option>
			</select>
		</div>
		<div class="field nfe_volume_weight" style="margin-bottom:10px;">
			<p class="label" style="margin-bottom:8px;">
			<input type="checkbox" name="nfe_volume_weight" <?php if ($volume_checked) echo 'checked'; ?>>
			<label class="title">Informar Volume e Peso</label>
			</p>
		</div>
		<div class="field transporte">
			<p class="label" style="margin-bottom:8px;">
				<label class="title">Volume</label>
			</p>
			<input type="text" name="transporte_volume" value="<?php echo get_post_meta( $post->ID, '_nfe_transporte_volume', true ); ?>" style="width:100%;padding:5px;">
		</div>
		<div class="field transporte">
			<p class="label" style="margin-bottom:8px;">
				<label class="title">Espécie</label>
			</p>
			<input type="text" name="transporte_especie" value="<?php echo get_post_meta( $post->ID, '_nfe_transporte_especie', true ); ?>" style="width:100%;padding:5px;">
		</div>
		<div class="field transporte">
			<p class="label" style="margin-bottom:8px;">
				<label class="title">Peso Bruto</label> (KG)
			</p>
			<input type="text" name="transporte_peso_bruto" value="<?php echo get_post_meta( $post->ID, '_nfe_transporte_peso_bruto', true ); ?>" style="width:100%;padding:5px;" placeholder="Ex: 50.210 = 50,210KG">
		</div>
		<div class="field transporte">
			<p class="label" style="margin-bottom:8px;">
				<label class="title">Peso Líquido</label> (KG)
			</p>
			<input type="text" name="transporte_peso_liquido" value="<?php echo get_post_meta( $post->ID, '_nfe_transporte_peso_liquido', true ); ?>" style="width:100%;padding:5px;" placeholder="Ex: 50.210 = 50,210KG">
		</div>
	</div>
	<div class="field" style="margin-bottom:10px;">
		<p class="label" style="margin-bottom:8px;">
		<input type="checkbox" name="nfe_installments" <?php if ($installments_checked) echo 'checked'; ?>>
		<label class="title">Informar Parcelas</label>
		</p>
	</div>
	<div class="field nfe_installments">
		<p class="label" style="margin-bottom:8px;">
			<label class="title">Parcelas</label>
		</p>
		<input type="number" name="nfe_installments_n" min="1" value="<?php echo $nfe_installments_n; ?>" style="width:100%;padding:5px;">
	</div>
	<div class="nfe_installments block">

		<div class="nfe_installments row-first" data-row="1">
			<div class="field">
				<p class="label">
					<label class="title">Vencimento (DD/MM/AAAA)</label>
				</p>
				<input type="text" name="nfe_installments_due_date[]" value="<?php echo ($nfe_installments_due_date) ? $nfe_installments_due_date[0] : ''; ?>" style="width:100%;">
			</div>
			<div class="field">
				<p class="label">
					<label class="title">Valor (R$)</label>
				</p>
				<input type="text" name="nfe_installments_value[]" value="<?php echo ($nfe_installments_value) ? $nfe_installments_value[0] : ''; ?>" style="width:100%;">
			</div>
		</div>

		<?php
		if ($nfe_installments_n > 1) {
			for ($i = 1; $i < $nfe_installments_n; $i++){
		?>

		<div class="nfe_installments row">
			<div class="field">
				<p class="label">
					<label class="title">Vencimento (DD/MM/AAAA)</label>
				</p>
				<input type="text" name="nfe_installments_due_date[]" value="<?php echo $nfe_installments_due_date[$i]; ?>" style="width:100%;">
			</div>
			<div class="field">
				<p class="label">
					<label class="title">Valor (R$)</label>
				</p>
				<input type="text" name="nfe_installments_value[]" value="<?php echo $nfe_installments_value[$i]; ?>" style="width:100%;">
			</div>
		</div>

		<?php
			} // end loop
		} // end if ?>


	</div>

	<div class="field" style="margin-bottom:10px;">
		<p class="label" style="margin-bottom:8px;">
		<input type="checkbox" name="nfe_info_intermediador" <?php if ($info_intermediador_checked) echo 'checked'; ?>>
		<label class="title">Informar Intermediador</label>
		</p>
	</div>
	<div class="field nfe_info_intermediador" style="display: none;">
		<div class="field">
			<p class="label">
				<label class="title">Intermediador da operação</label>
			</p>
			<select name="nfe_info_intermediador_type">
				<option value="0" <?php if ($info_intermediador_type == '0') echo 'selected'; ?>>0 - Operação sem intermediador (em site ou plataforma própria)</option>
				<option value="1" <?php if ($info_intermediador_type == '1') echo 'selected'; ?>>1 - Operação em site ou plataforma de terceiros (intermediadores/marketplace)</option>
			</select>
		</div>
		<div class="field">
			<p class="label">
				<label class="title">CNPJ do Intermediador</label>
			</p>
			<input type="text" name="nfe_info_intermediador_cnpj" style="width:100%;" value="<?php echo $info_intermediador_cnpj ?>" />
		</div>
		<div class="field">
			<p class="label">
				<label class="title">ID do Intermediador</label>
			</p>
			<input type="text" name="nfe_info_intermediador_id" style="width:100%;" value="<?php echo $info_intermediador_id ?>" />
		</div>
	</div>

	<div class="field" style="margin-bottom:10px;">
		<p class="label" style="margin-bottom:8px;">
		<input type="checkbox" name="nfe_additional_info" <?php if ($additional_info_checked) echo 'checked'; ?>>
		<label class="title">Informações complementares</label>
		</p>
	</div>
	<div class="field nfe_additional_info_text">
		<textarea type="text" name="nfe_additional_info_text" rows="6" style="width:100%;padding:5px;"><?php echo $nfe_additional_info_text; ?></textarea>
	</div>

			<?php
	}

	/**
	 * Register metabox
	 *
	 * @return void
	 */
	function register_metabox_listar_nfe() {

		add_meta_box(
			'woocommernfe_informacoes',
			'Informações Fiscais',
			array($this, 'metabox_content_woocommernfe_informacoes'),
			'product',
			'side',
			'high'
		);

	}

	/**
	 * Metabox content
	 *
	 * @return html
	 */
	function metabox_content_woocommernfe_informacoes( $post ){

		// Vars
		$product_type = get_post_meta( $post->ID, '_nfe_tipo_produto', true );
		$others_checked = get_post_meta( $post->ID, '_nfe_product_others', true );

	?>
	<script>
		jQuery(function($){

			<?php if ($product_type == 2){ ?>
				$('.nfe_fields').hide();
			<?php } ?>
			<?php if ($others_checked && $others_checked == 'on'){ ?>
				$('.product_others').show();
			<?php } ?>

			$('select[name="tipo_produto"]').on('change', function(){
				(this.value == 2) ? $('.nfe_fields').hide() : $('.nfe_fields').show();
			});
			$('input[name="product_others"]').on('change', function(){
				if ($(this).is(':checked')){
					$('.product_others').show();
				} else {
					$('.product_others').hide();
				}
			});

		});
	</script>

<div class="inside" style="padding:0!important;">
	<div class="field emitir_ambiente" style="margin-bottom:10px;">
		<p class="label" style="margin-bottom:8px;">
		<input type="checkbox" name="ignorar_nfe" value="1" <?php if(get_post_meta( $post->ID, '_nfe_ignorar_nfe', true ) == 1) echo 'checked'; ?> />
		<label class="title">Ignorar produto Nota Fiscal</label>
		</p>
	</div>
	<hr>
	<div class="field">
			<p class="label" style="margin-bottom:8px;">
					<label class="title">Tipo de produto</label>
			</p>
			<select name="tipo_produto" value="<?php echo get_post_meta( $post->ID, '_nfe_tipo_produto', true ); ?>" style="width:100%;padding:5px;">
				<option value="1" <?php if (get_post_meta( $post->ID, '_nfe_tipo_produto', true ) == 1) echo 'selected'; ?>>Produto físico</option>
				<option value="2" <?php if (get_post_meta( $post->ID, '_nfe_tipo_produto', true ) == 2) echo 'selected'; ?>>Prestação de serviço</option>
			</select>
	</div>
	<div class="field">
			<p class="label" style="margin-bottom:8px;">
					<label class="title">Classe de Imposto</label>
			</p>
			<input type="text" name="classe_imposto" value="<?php echo get_post_meta( $post->ID, '_nfe_classe_imposto', true ); ?>" style="width:100%;padding:5px;">
	</div>
	<div class="nfe_fields">
	<div class="field">
			<p class="label" style="margin-bottom:8px;">
					<label class="title">Código NCM</label>
			</p>
			<input type="text" name="codigo_ncm" value="<?php echo get_post_meta( $post->ID, '_nfe_codigo_ncm', true ); ?>" style="width:100%;padding:5px;">
	</div>
	<div class="field">
			<p class="label" style="margin-bottom:8px;">
					<label class="title">Código CEST</label>
			</p>
			<input type="text" name="codigo_cest" value="<?php echo get_post_meta( $post->ID, '_nfe_codigo_cest', true ); ?>" style="width:100%;padding:5px;">
	</div>
	<div class="field">
		<p class="label" style="margin-bottom:8px;">
			<label style="font-size:13px;line-height:1.5em;font-weight:bold;">Unidade de Medida</label>
		</p>
		<?php
		$unidade = get_post_meta( $post->ID, '_nfe_unidade', true );
		$lista_unidades = array('UN' => 'UNIDADE',
			'UNI' => 'UNIDADE (UNI)',
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
	<div class="field">
			<p class="label" style="margin-bottom:8px;">
					<label class="title">Origem</label>
			</p>
			<?php
				$origem = get_post_meta( $post->ID, '_nfe_origem', true );
			?>
			<select name="origem">
					<option value="null" <?php if (!is_numeric($origem)) echo 'selected'; ?> ><?php _e( 'Selecionar Origem do Produto', $this->domain ); ?></option>
					<option value="0" <?php if (is_numeric($origem) && $origem == 0) echo 'selected'; ?> ><?php _e( '0 - Nacional, exceto as indicadas nos códigos 3, 4, 5 e 8', $this->domain ); ?></option>
					<option value="1" <?php if ($origem == 1) echo 'selected'; ?> ><?php _e( '1 - Estrangeira - Importação direta, exceto a indicada no código 6', $this->domain ); ?></option>
					<option value="2" <?php if ($origem == 2) echo 'selected'; ?> ><?php _e( '2 - Estrangeira - Adquirida no mercado interno, exceto a indicada no código 7', $this->domain ); ?></option>
					<option value="3" <?php if ($origem == 3) echo 'selected'; ?> ><?php _e( '3 - Nacional, mercadoria ou bem com Conteúdo de Importação superior a 40% e inferior ou igual a 70%', $this->domain ); ?></option>
					<option value="4" <?php if ($origem == 4) echo 'selected'; ?> ><?php _e( '4 - Nacional, cuja produção tenha sido feita em conformidade com os processos produtivos básicos de que tratam as legislações citadas nos Ajustes', $this->domain ); ?></option>
					<option value="5" <?php if ($origem == 5) echo 'selected'; ?> ><?php _e( '5 - Nacional, mercadoria ou bem com Conteúdo de Importação inferior ou igual a 40%', $this->domain ); ?></option>
					<option value="6" <?php if ($origem == 6) echo 'selected'; ?> ><?php _e( '6 - Estrangeira - Importação direta, sem similar nacional, constante em lista da CAMEX e gás natural', $this->domain ); ?></option>
					<option value="7" <?php if ($origem == 7) echo 'selected'; ?> ><?php _e( '7 - Estrangeira - Adquirida no mercado interno, sem similar nacional, constante lista CAMEX e gás natural' ,$this->domain ); ?></option>
					<option value="8" <?php if ($origem == 8) echo 'selected'; ?> ><?php _e( '8 - Nacional, mercadoria ou bem com Conteúdo de Importação superior a 70%', $this->domain ); ?></option>
			</select>
			<input type="hidden" name="wp_admin_nfe" value="1" />
	</div>
	<div class="field">
			<p class="label" style="margin-bottom:8px;">
					<label class="title">Informações adicionais do produto</label>
			</p>
			<input type="text" name="produto_informacoes_adicionais" value="<?php echo get_post_meta( $post->ID, '_nfe_produto_informacoes_adicionais', true ); ?>" style="width:100%;padding:5px;">
	</div>
	<div class="field" style="margin-bottom:10px;">
		<p class="label" style="margin-bottom:8px;">
		<input type="checkbox" name="product_others" <?php if ($others_checked) echo 'checked'; ?>>
		<label class="title">Outras opções</label>
		</p>
	</div>
	<div class="product_others">
		<div class="field">
				<p class="label" style="margin-bottom:8px;">
						<label class="title">GTIN</label>
				</p>
				<input type="text" name="codigo_ean" value="<?php echo get_post_meta( $post->ID, '_nfe_codigo_ean', true ); ?>" style="width:100%;padding:5px;">
		</div>
		<div class="field">
				<p class="label" style="margin-bottom:8px;">
						<label class="title">GTIN tributável</label>
				</p>
				<input type="text" name="gtin_tributavel" value="<?php echo get_post_meta( $post->ID, '_nfe_gtin_tributavel', true ); ?>" style="width:100%;padding:5px;">
		</div>
		<div class="field">
				<p class="label" style="margin-bottom:8px;">
						<label class="title">Indicador de escala relevante</label>
				</p>
				<?php
					$ind_escala = get_post_meta( $post->ID, '_nfe_ind_escala', true );
				?>
				<select name="ind_escala">
						<option value="" <?php if (!$ind_escala) echo 'selected'; ?> ><?php _e( 'Selecionar', $this->domain ); ?></option>
						<option value="S" <?php if ($ind_escala == 'S') echo 'selected'; ?> ><?php _e( 'S - Produzido em Escala Relevante', $this->domain ); ?></option>
						<option value="N" <?php if ($ind_escala == 'N') echo 'selected'; ?> ><?php _e( 'N - Produzido em Escala NÃO Relevante', $this->domain ); ?></option>
				</select>
				<input type="hidden" name="wp_admin_nfe" value="1" />
		</div>
		<div class="field">
				<p class="label" style="margin-bottom:8px;">
						<label class="title">CNPJ do Fabricante</label>
				</p>
				<input type="text" name="cnpj_fabricante" value="<?php echo get_post_meta( $post->ID, '_nfe_cnpj_fabricante', true ); ?>" style="width:100%;padding:5px;">
		</div>
	</div>
	</div>

</div>

	<?php

	}

	/**
	 * Order status column
	 *
	 * @return array
	 */
	function add_order_status_column_header( $columns ) {

		$new_columns = array();

		foreach ( $columns as $column_name => $column_info ) {

			$new_columns[ $column_name ] = $column_info;

			if ( 'order_status' == $column_name ) {
				$new_columns['nfe'] = __( 'Status Fiscal' );
			}

		}

		return $new_columns;

	}

	/**
	 * Order status column content
	 *
	 * @return html
	 */
	function add_order_status_column_content( $column ) {

		global $post;
		if ( 'nfe' == $column ) {

			// vars
			$nfe = get_post_meta( $post->ID, 'nfe', true );
			$order = new WC_Order( $post->ID );

			// If order has the status pending or cancelled, don't print 'NF-e' status
			if ($order->get_status() == 'pending' || $order->get_status() == 'cancelled') {

				echo '<span class="nfe_none">-</span>';

			// Else if $nfe has information, check status from array
			} elseif ($nfe) {

				$nfe_emitida = false;

				foreach ( $nfe as $item ) {

					if ( $item['status'] == 'aprovado' ) {
						$nfe_emitida = true;
					}

				}

				if ( $nfe_emitida ) {
					echo '<div class="nfe_success">Emitida</div>';
				} else {
					echo '<div class="nfe_alert">Não emitida</div>';
				}

			} else {

				echo '<div class="nfe_alert">Não emitida</div>';

			}

		}
	}

	/**
	 * Metabox content
	 *
	 * @return array
	 */
	function add_order_meta_box_actions( $actions ) {

		$actions['wc_nfe_emitir'] = __( 'Emitir Nota Fiscal' );

		return $actions;

	}

	/**
	 * Menu bulk actions
	 *
	 * @return html
	 */
	function add_order_bulk_actions() {

		global $post_type, $post_status;

		if ( $post_type == 'shop_order' ) {

			if ($post_status == 'trash' || $post_status == 'wc-cancelled' || $post_status == 'wc-pending')
				return false;
			?>
			<script type="text/javascript">
				jQuery( document ).ready( function ( $ ) {
					var $emitir_nfe = $('<option>').val('wc_nfe_emitir').text('<?php _e( 'Emitir Nota Fiscal' ); ?>');
					var $imprimir_danfe = $('<option>').val('wc_nfe_imprimir_danfe').text('<?php _e( 'Imprimir Danfe' ); ?>');
					var $imprimir_simplificada = $('<option>').val('wc_nfe_imprimir_simplificada').text('<?php _e( 'Imprimir Danfe Simples' ); ?>');
					var $imprimir_etiqueta = $('<option>').val('wc_nfe_imprimir_etiqueta').text('<?php _e( 'Imprimir Danfe Etiqueta' ); ?>');
					$( 'select[name^="action"]' ).append( $emitir_nfe );
					$( 'select[name^="action"]' ).append( $imprimir_danfe );
					$( 'select[name^="action"]' ).append( $imprimir_simplificada );
					$( 'select[name^="action"]' ).append( $imprimir_etiqueta );
				});
			</script>
			<?php

		}

	}

	/**
	 * Style
	 *
	 * @return html
	 */
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

	/**
	 * Process Bulk actions
	 *
	 * @return void
	 */
	function process_order_bulk_actions(){

		global $typenow;

		if ( 'shop_order' == $typenow ) {

			$wp_list_table = _get_list_table( 'WP_Posts_List_Table' );
			$action        = $wp_list_table->current_action();

			if ( ! in_array( $action, array( 'wc_nfe_emitir', 'wc_nfe_imprimir_danfe', 'wc_nfe_imprimir_simplificada', 'wc_nfe_imprimir_etiqueta') ) )
				return false;

			if ( isset( $_REQUEST['post'] ) )
				$order_ids = array_map( 'absint', $_REQUEST['post'] );

			if ( empty( $order_ids ) )
				return false;

			if ($action == 'wc_nfe_emitir'){
				$nf = new WooCommerceNFeIssue;
				$nf->send( $order_ids, true );
			}

			// Adicionado módulo de impressão DANFE Normal
			if ($action == 'wc_nfe_imprimir_danfe'){
				$nf = new WooCommerceNFePrint;
				$result = $nf->print( $order_ids, 'normal' );
			}
			
			// Adicionado módulo de impressão DANFE Simplificada
			if ($action == 'wc_nfe_imprimir_simplificada'){
				$nf = new WooCommerceNFePrint;
				$result = $nf->print( $order_ids, 'simplificada' );
			}

			// Adicionado módulo de impressão DANFE Etiqueta
			if ($action == 'wc_nfe_imprimir_etiqueta'){
				$nf = new WooCommerceNFePrint;
				$result = $nf->print( $order_ids, 'etiqueta' );
			}

		}

	}

	/**
	 * Proccess metabox actions
	 *
	 * @return void
	 */
	function process_order_meta_box_actions( $post ){

		$order_id = $post->id;
		$post_status = $post->post_status;

		if ($post_status == 'trash' || $post_status == 'wc-cancelled')
			return false;

		$nf = new WooCommerceNFeIssue;
		$nf->send( array( $order_id ) );

	}

	/**
	 * Customer meta fields
	 *
	 * @return array
	 */
  function customer_meta_fields( $fields ) {

		// Billing fields
		$new_fields['billing']['title'] = __( 'Endereço de Cobrança', $this->domain );
		$new_fields['billing']['fields']['billing_first_name'] = $fields['billing']['fields']['billing_first_name'];
		$new_fields['billing']['fields']['billing_last_name']  = $fields['billing']['fields']['billing_last_name'];
		$new_fields['billing']['fields']['billing_cpf'] = array(
				'label' => __( 'CPF', $this->domain ),
				'description' => ''
		);
		$new_fields['billing']['fields']['billing_cnpj'] = array(
				'label' => __( 'CNPJ', $this->domain ),
				'description' => ''
		);
		$new_fields['billing']['fields']['billing_company'] = $fields['billing']['fields']['billing_company'];
		$new_fields['billing']['fields']['billing_ie'] = array(
				'label' => __( 'Inscrição Estadual', $this->domain ),
				'description' => ''
		);
		$new_fields['billing']['fields']['billing_birthdate'] = array(
				'label' => __( 'Nascimento', $this->domain ),
				'description' => ''
		);
		$new_fields['billing']['fields']['billing_sex'] = array(
				'label' => __( 'Sexo', $this->domain ),
				'description' => ''
		);
		$new_fields['billing']['fields']['billing_address_1'] = $fields['billing']['fields']['billing_address_1'];
		$new_fields['billing']['fields']['billing_number'] = array(
			'label' => __( 'Número', $this->domain ),
			'description' => ''
		);
		$new_fields['billing']['fields']['billing_address_2'] = $fields['billing']['fields']['billing_address_2'];
		$new_fields['billing']['fields']['billing_neighborhood'] = array(
			'label' => __( 'Bairro', $this->domain ),
			'description' => ''
		);
		$new_fields['billing']['fields']['billing_city']     = $fields['billing']['fields']['billing_city'];
		$new_fields['billing']['fields']['billing_postcode'] = $fields['billing']['fields']['billing_postcode'];
		$new_fields['billing']['fields']['billing_country']  = $fields['billing']['fields']['billing_country'];
		$new_fields['billing']['fields']['billing_state']    = $fields['billing']['fields']['billing_state'];
		$new_fields['billing']['fields']['billing_phone']    = str_replace("?", "", $fields['billing']['fields']['billing_phone']);
		if ( isset( $settings['cell_phone'] ) ) {
			$new_fields['billing']['fields']['billing_cellphone'] = array(
				'label' => __( 'Celular', $this->domain ),
				'description' => ''
			);
		}
		$new_fields['billing']['fields']['billing_email'] = $fields['billing']['fields']['billing_email'];

		// Shipping fields.
		$new_fields['shipping']['title'] = __( 'Customer Shipping Address', $this->domain );
		$new_fields['shipping']['fields']['shipping_first_name'] = $fields['shipping']['fields']['shipping_first_name'];
		$new_fields['shipping']['fields']['shipping_last_name']  = $fields['shipping']['fields']['shipping_last_name'];
		$new_fields['shipping']['fields']['shipping_company']    = $fields['shipping']['fields']['shipping_company'];
		$new_fields['shipping']['fields']['shipping_address_1']  = $fields['shipping']['fields']['shipping_address_1'];
		$new_fields['shipping']['fields']['shipping_number'] = array(
			'label' => __( 'Número', $this->domain ),
			'description' => ''
		);
		$new_fields['shipping']['fields']['shipping_address_2']  = $fields['shipping']['fields']['shipping_address_2'];
		$new_fields['shipping']['fields']['shipping_neighborhood'] = array(
			'label' => __( 'Bairro', $this->domain ),
			'description' => ''
		);
		$new_fields['shipping']['fields']['shipping_city']     = $fields['shipping']['fields']['shipping_city'];
		$new_fields['shipping']['fields']['shipping_postcode'] = $fields['shipping']['fields']['shipping_postcode'];
		$new_fields['shipping']['fields']['shipping_country']  = $fields['shipping']['fields']['shipping_country'];
		$new_fields['shipping']['fields']['shipping_state']    = $fields['shipping']['fields']['shipping_state'];

		// Return
		return $new_fields;

	}

	/**
	 * Billing address user column
	 *
	 * @return array
	 */
	function user_column_billing_address( $address, $user_id ) {

		$address['number']       = get_user_meta( $user_id, 'billing_number', true );
		$address['neighborhood'] = get_user_meta( $user_id, 'billing_neighborhood', true );

		return $address;

	}

	/**
	 * Shipping address user column
	 *
	 * @return array
	 */
	function user_column_shipping_address( $address, $user_id ) {

		$address['number']       = get_user_meta( $user_id, 'shipping_number', true );
		$address['neighborhood'] = get_user_meta( $user_id, 'shipping_neighborhood', true );

		return $address;

	}

	/**
	 * Billing Fields
	 *
	 * @return array
	 */
  function shop_order_billing_fields( $data ) {

		$billing_data['first_name'] = array(
			'label' => __( 'Nome', $this->domain ),
			'show'  => false
		);
		$billing_data['last_name'] = array(
			'label' => __( 'Sobrenome', $this->domain ),
			'show'  => false
		);
    $billing_data['persontype'] = array(
			'type'    => 'select',
			'label'   => __( 'Tipo Pessoa', $this->domain ),
			'options' => array(
				'0' => __( 'Selecionar', $this->domain ),
				'1' => __( 'Pessoa Física', $this->domain ),
				'2' => __( 'Pessoa Jurídica', $this->domain )
			),
			'show'  => false
    );
    $billing_data['cpf'] = array(
			'label' => __( 'CPF', $this->domain ),
			'show'  => false
    );
    $billing_data['cnpj'] = array(
			'label' => __( 'CNPJ', $this->domain ),
			'show'  => false
    );
    $billing_data['ie'] = array(
			'label' => __( 'Inscrição Estadual', $this->domain ),
			'show'  => false
    );
    $billing_data['company'] = array(
			'label' => __( 'Empresa', $this->domain ),
    );
    $billing_data['birthdate'] = array(
			'label' => __( 'Nascimento', $this->domain ),
			'show'  => false
    );
    $billing_data['sex'] = array(
			'label' => __( 'Sexo', $this->domain ),
			'show'  => false
    );
		$billing_data['address_1'] = array(
			'label' => __( 'Endereço', $this->domain ),
			'show'  => false
		);
		$billing_data['number'] = array(
			'label' => __( 'Número', $this->domain ),
			'show'  => false
		);
		$billing_data['address_2'] = array(
			'label' => __( 'Complemento', $this->domain ),
			'show'  => false
		);
		$billing_data['neighborhood'] = array(
			'label' => __( 'Bairro', $this->domain ),
			'show'  => false
		);
		$billing_data['city'] = array(
			'label' => __( 'Cidade', $this->domain ),
			'show'  => false
		);
		$billing_data['state'] = array(
			'label' => __( 'Estado', $this->domain ),
			'show'  => false
		);
		$billing_data['country'] = array(
			'label'   => __( 'País', $this->domain ),
			'show'    => false,
			'type'    => 'select',
			'options' => array(
				'' => __( 'Selecione um País&hellip;', $this->domain )
			) + WC()->countries->get_allowed_countries()
		);
		$billing_data['postcode'] = array(
			'label' => __( 'CEP', $this->domain ),
			'show'  => false
		);
		$billing_data['phone'] = array(
			'label' => __( 'Telefone Fixo', $this->domain ),
		);
		if ( isset( $settings['cell_phone'] ) ) {
			$billing_data['cellphone'] = array(
				'label' => __( 'Celular', $this->domain ),
			);
		}
		$billing_data['email'] = array(
			'label' => __( 'E-mail', $this->domain ),
		);

		// Return
		return $billing_data;

	}

	/**
	 * Shipping Fields
	 *
	 * @return array
	 */
  function shop_order_shipping_fields( $data ) {

    $shipping_data['first_name'] = array(
			'label' => __( 'Nome', $this->domain ),
			'show'  => false
		);
		$shipping_data['last_name'] = array(
			'label' => __( 'Sobrenome', $this->domain ),
			'show'  => false
		);
		$shipping_data['address_1'] = array(
			'label' => __( 'Endereço', $this->domain ),
			'show'  => false
		);
		$shipping_data['number'] = array(
			'label' => __( 'Número', $this->domain ),
			'show'  => false
		);
		$shipping_data['address_2'] = array(
			'label' => __( 'Complemento', $this->domain ),
			'show'  => false
		);
		$shipping_data['neighborhood'] = array(
			'label' => __( 'Bairro', $this->domain ),
			'show'  => false
		);
		$shipping_data['city'] = array(
			'label' => __( 'Cidade', $this->domain ),
			'show'  => false
		);
		$shipping_data['state'] = array(
			'label' => __( 'Estado', $this->domain ),
			'show'  => false
		);
		$shipping_data['country'] = array(
			'label'   => __( 'País', $this->domain ),
			'show'    => false,
			'type'    => 'select',
			'options' => array(
				'' => __( 'Selecione um País&hellip;', $this->domain )
			) + WC()->countries->get_allowed_countries()
		);
		$shipping_data['postcode'] = array(
			'label' => __( 'CEP', $this->domain ),
			'show'  => false
		);

		return $shipping_data;

	}

	/**
	 * Extra Shipping Fields
	 *
	 * @return array
	 */
	function extra_shipping_fields( $data ) {

    $shipping_data['persontype'] = array(
			'type'    => 'select',
			'label'   => __( 'Tipo Pessoa', $this->domain ),
			'options' => array(
				'3' => __( 'Utilizar dados de cobrança', $this->domain ),
				'1' => __( 'Pessoa Física', $this->domain ),
				'2' => __( 'Pessoa Jurídica', $this->domain )
			),
			'show'  => false
    );
    $shipping_data['cpf'] = array(
			'label' => __( 'CPF', $this->domain ),
			'show'  => false
    );
    $shipping_data['cnpj'] = array(
			'label' => __( 'CNPJ', $this->domain ),
			'show'  => false
    );
    $shipping_data['ie'] = array(
			'label' => __( 'Inscrição Estadual', $this->domain ),
			'show'  => false
    );

		return array_merge($shipping_data, $data);

	}

	/**
	 * Customer details Ajax
	 *
	 * @return array
	 */
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

	/**
	 * Save custom shop data
	 *
	 * @return void
	 */
  function save_custom_shop_data( $post_id ) {

		update_post_meta( $post_id, '_billing_number', wc_clean( $_POST['_billing_number'] ) );
		update_post_meta( $post_id, '_billing_neighborhood', wc_clean( $_POST['_billing_neighborhood'] ) );
		update_post_meta( $post_id, '_shipping_number', wc_clean( $_POST['_shipping_number'] ) );
		update_post_meta( $post_id, '_shipping_neighborhood', wc_clean( $_POST['_shipping_neighborhood'] ) );
		update_post_meta( $post_id, '_billing_persontype', wc_clean( $_POST['_billing_persontype'] ) );
		update_post_meta( $post_id, '_billing_cpf', wc_clean( $_POST['_billing_cpf'] ) );
		update_post_meta( $post_id, '_billing_cnpj', wc_clean( $_POST['_billing_cnpj'] ) );
		update_post_meta( $post_id, '_billing_ie', wc_clean( $_POST['_billing_ie'] ) );
		update_post_meta( $post_id, '_billing_birthdate', wc_clean( $_POST['_billing_birthdate'] ) );
		update_post_meta( $post_id, '_billing_sex', wc_clean( $_POST['_billing_sex'] ) );
		update_post_meta( $post_id, '_billing_cellphone', wc_clean( str_replace("?", "", $_POST['_billing_cellphone']) ) );

	}

	/**
	 * Save custom shop data (API)
	 *
	 * @return void
	 */
	function wc_api_save_custom_shop_data($order_id, $data){

		$billing_address = $data['customer']['billing_address'];
		$shipping_address = $data['customer']['shipping_address'];
		update_post_meta( $order_id, '_billing_number', wc_clean( $billing_address['number'] ) );
		update_post_meta( $order_id, '_billing_neighborhood', wc_clean( $billing_address['neighborhood'] ) );
		update_post_meta( $order_id, '_shipping_number', wc_clean( $shipping_address['number'] ) );
		update_post_meta( $order_id, '_shipping_neighborhood', wc_clean( $shipping_address['neighborhood'] ) );
		update_post_meta( $order_id, '_billing_persontype', wc_clean( $billing_address['persontype'] ) );
		update_post_meta( $order_id, '_billing_cpf', wc_clean( $billing_address['cpf'] ) );
		update_post_meta( $order_id, '_billing_cnpj', wc_clean( $billing_address['cnpj'] ) );
		update_post_meta( $order_id, '_billing_ie', wc_clean( $billing_address['ie'] ) );
		update_post_meta( $order_id, '_billing_birthdate', wc_clean( $billing_address['birthdate'] ) );
		update_post_meta( $order_id, '_billing_sex', wc_clean( $billing_address['sex'] ) );
		update_post_meta( $order_id, '_billing_cellphone', wc_clean( str_replace("?", "", $billing_address['cellphone']) ) );

	}

	/**
	 * Save custom shop data
	 *
	 * @return void
	 */
    function save_informacoes_fiscais( $post_id ){

			if (get_post_type($post_id) == 'product' && $_POST['wp_admin_nfe']){

					$info = array(
					'_nfe_tipo_produto'    => $_POST['tipo_produto'],
					'_nfe_classe_imposto'  => $_POST['classe_imposto'],
					'_nfe_codigo_ean'      => $_POST['codigo_ean'],
					'_nfe_gtin_tributavel' => $_POST['gtin_tributavel'],
					'_nfe_codigo_ncm'      => $_POST['codigo_ncm'],
					'_nfe_codigo_cest'     => $_POST['codigo_cest'],
					'_nfe_cnpj_fabricante' => $_POST['cnpj_fabricante'],
					'_nfe_ind_escala'      => $_POST['ind_escala'],
					'_nfe_produto_informacoes_adicionais' => $_POST['produto_informacoes_adicionais'],
					'_nfe_unidade'         => $_POST['unidade'],
					'_nfe_product_others'  => $_POST['product_others']
					);

					foreach ($info as $key => $value){
						if (isset($value))
							update_post_meta($post_id, $key, $value);
					}

					if ($_POST['ignorar_nfe']){
						update_post_meta( $post_id, '_nfe_ignorar_nfe', $_POST['ignorar_nfe'] );
					} else {
						update_post_meta( $post_id, '_nfe_ignorar_nfe', 0 );
					}

					if (!$info['_nfe_product_others']){
						delete_post_meta( $post_id, '_nfe_product_others' );
					}

					if (is_numeric($_POST['origem']) || $_POST['origem'])
						update_post_meta( $post_id, '_nfe_origem', $_POST['origem'] );

			}

			if (get_post_type($post_id) == 'shop_order' && $_POST && $_POST['wp_admin_nfe']){

				$info = array(
					'_nfe_natureza_operacao_pedido'	=> $_POST['natureza_operacao_pedido'],
					'_nfe_beneficio_fiscal_pedido'	=> $_POST['beneficio_fiscal_pedido'],
					'_nfe_contribuinte' => $_POST['nfe_contribuinte'],
					'_nfe_modalidade_frete' 		=> $_POST['modalidade_frete'],
					'_nfe_volume_weight' => isset($_POST['nfe_volume_weight']) ? $_POST['nfe_volume_weight'] : false,
					'_nfe_transporte_volume'    	=> $_POST['transporte_volume'],
					'_nfe_transporte_especie'   	=> $_POST['transporte_especie'],
					'_nfe_transporte_peso_bruto'    => $_POST['transporte_peso_bruto'],
					'_nfe_transporte_peso_liquido'  => $_POST['transporte_peso_liquido'],
					'_nfe_installments'  => isset($_POST['nfe_installments']) ? $_POST['nfe_installments'] : false,
					'_nfe_installments_n'  => $_POST['nfe_installments_n'],
					'_nfe_installments_due_date'  => $_POST['nfe_installments_due_date'],
					'_nfe_installments_value'  => $_POST['nfe_installments_value'],
					'_nfe_additional_info' => isset($_POST['nfe_additional_info']) ? $_POST['nfe_additional_info'] : false,
					'_nfe_additional_info_text' => $_POST['nfe_additional_info_text'],
					'_nfe_info_intermediador' => isset($_POST['nfe_info_intermediador']) ? $_POST['nfe_info_intermediador'] : false,
					'_nfe_info_intermediador_type' => $_POST['nfe_info_intermediador_type'],
					'_nfe_info_intermediador_cnpj' => $_POST['nfe_info_intermediador_cnpj'],
					'_nfe_info_intermediador_id' => $_POST['nfe_info_intermediador_id'],
				);

				if (!$info['_nfe_volume_weight']){
					delete_post_meta( $post_id, '_nfe_volume_weight' );
				}

				if (!$info['_nfe_installments']){
					delete_post_meta( $post_id, '_nfe_installments' );
				}

				if (!$info['_nfe_additional_info']) {
					delete_post_meta( $post_id, '_nfe_additional_info' );
				}

				//Intermediador fields
				if (!$info['_nfe_info_intermediador']) {
					unset($info['_nfe_info_intermediador_type']);
					unset($info['_nfe_info_intermediador_cnpj']);
					unset($info['_nfe_info_intermediador_id']);

					delete_post_meta( $post_id, '_nfe_info_intermediador' );
					delete_post_meta( $post_id, '_nfe_info_intermediador_type' );
					delete_post_meta( $post_id, '_nfe_info_intermediador_cnpj' );
					delete_post_meta( $post_id, '_nfe_info_intermediador_id' );
				}

				foreach ($info as $key => $value){

					if (isset($value))
						update_post_meta($post_id, $key, $value);

				}

			}

	}

	/**
	 * Add NCM to category
	 *
	 * @return void
	 */
	function add_category_ncm($taxonomy){

		?>

		<div class="form-field term-ncm-wrap">
			<label for="term-ncm">NCM</label>
			<input name="term-ncm" id="term-ncm" type="text" size="40" />
			<p>Este valor será utilizado caso o NCM não esteja definido diretamente no produto. Se vazio, será utilizado o NCM geral definido nas configurações da Nota Fiscal.</p>
		</div>

		<?php

	}

	/**
	 * Edit NCM in category
	 *
	 * @return void
	 */
	function edit_category_ncm($term, $taxonomy){

		if (function_exists('get_term_meta')) {
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

	/**
	 * Save NCM in category
	 *
	 * @return void
	 */
	function save_product_cat_ncm( $term_id, $tag_id ){

		if ( isset( $_POST['term-ncm'] ) ) {
			update_term_meta( $term_id, '_ncm', $_POST['term-ncm']);
		}

	}

	/**
	 * Save NCM in category
	 *
	 * @return void
	 */
	function is_categories_ncm_valid( $post_id ){

			$product_cat = get_the_terms($post_id, 'product_cat');
			$product_ncm = get_post_meta($post_id, '_nfe_codigo_ncm', true);

			if ($product_ncm || !is_array($product_cat))
				return true;

			$ncm_categories = array();

			foreach ($product_cat as $cat) {

				if (function_exists('get_term_meta')) {
					$ncm = get_term_meta($cat->term_id, '_ncm', true);
				} else {
					$ncm = null;
				}

				if ($ncm)
					$ncm_categories[] = $ncm;
			}

			if (count($ncm_categories) > 1){
				return false;
			}

			return true;

	}

	/**
	 * Warning NCM in category
	 *
	 * @return void
	 */
	function cat_ncm_warning(){

		global $post;

		$post_type = get_post_type($post);

		if ($post_type == 'product' && !$this->is_categories_ncm_valid($post->ID)){ ?>

			<div class="error" style="background-color: #f2dede; color: #a94442;"><p><strong>Atenção:</strong> Duas ou mais categorias deste produto possuem o NCM definido e, caso diferentes, podem ter o valor incorreto durante a emissão da NF-e.</p></div>

		<?php }

	}

	/**
	 * New admin menu item
	 *
	 * @return void
	 */
	public function add_admin_menu_item() {

		$ids_db = get_option('wmbr_auto_invoice_errors');

		if ( is_array($ids_db) && count($ids_db) > 0 ) {

			$count = count($ids_db);
			$page_count = '('.$count.') ';
			$update_count = " <span class='update-plugins rsssl-update-count'><span class='update-count' style='background: red;'>$count</span></span>";

		} else {

			$page_count = '';
			$update_count = " <span class='update-plugins rsssl-update-count'><span class='update-count'>0</span></span>";

		}

		$title = 'Notificações Nota Fiscal';
		$capability = 'manage_options';
		$menu_slug = 'wmbr_page_auto_invoice_errors';

		add_submenu_page( 'woocommerce', $page_count . $title, $title . $update_count, $capability, $menu_slug, array($this, 'page_auto_invoice_errors'));

	}

	/**
	 * Display NFe errors
	 *
	 * @return void
	 */
	public function page_auto_invoice_errors() {

		$ids_db = get_option('wmbr_auto_invoice_errors');
		include_once(plugin_dir_path(dirname(__FILE__)).'nota-fiscal-eletronica-woocommerce/templates/page-reports.php');

	}

	/**
	 * Show the list of automatic invoice errors
	 **/
	function alert_auto_invoice_errors() {

		$ids_db = get_option('wmbr_auto_invoice_errors');

		if ( !empty($ids_db) ) {

			$menu_url = get_admin_url(get_current_blog_id(), '/admin.php?page=wmbr_page_auto_invoice_errors');
			$message = __( '<strong>[WebmaniaBR® Nota Fiscal] Aviso:</strong> Foram localizados pedidos com erros de emissão. <a href="'.$menu_url.'">Visualizar Pedidos</a>');

			$this->add_error( $message );

		}

	}

	/**
	 * Remover warning in OrderID
	 *
	 * @return json
	 */
	public function wmbr_remove_order_id_auto_invoice(){

		$secure = check_ajax_referer( 'G7EZCEv3tA', 'sec_nonce', false);

		if ($secure) {

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

	/**
	 * Callback
	 *
	 * @return void
	 */
	function nfe_callback(){

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['order_key'] && $_GET['order_id']) {

			$nfe_data = $_POST['data'];
			$nfe_order_id = (int) $nfe_data['ID'];
			$order_key = esc_attr($_GET['order_key']);
			$order_id = (int) $_GET['order_id'];
			$order = wc_get_order($order_id);

			if ($order->get_order_key() != $order_key || $nfe_order_id != $order_id || ! $order) {
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
					'url_danfe_simplificada' => (string) $_POST['danfe_simples'],
					'url_danfe_etiqueta' => (string) $_POST['danfe_etiqueta'],
					'data' => date_i18n('d/m/Y'),
				);
			}

			update_post_meta($order_id, 'nfe', $order_nfe_data);

		}

	}

	/**
	 * Callback NFS-e
	 *
	 * @return void
	 */
	function nfse_callback(){

		if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['order_key'] && $_GET['order_id']) {

			$order_key = esc_attr($_GET['order_key']);
			$order_id = (int) $_GET['order_id'];
			$order = wc_get_order($order_id);
			$response = json_decode(file_get_contents("php://input"));

			if (!$order || $order->get_order_key() != $order_key || !$response) {
				header( 'HTTP/1.1 401 Unauthorized' );
				exit;
			}

			$order_nfe_data = get_post_meta($order_id, 'nfe', true);
			$is_new = true;
			$is_lote_update = false;

			if ( is_array($order_nfe_data) ) {

				foreach($order_nfe_data as $key => $order_nfe){
					$current_status = $order_nfe['status'];
					$received_status = $response->status;
					if($order_nfe['uuid'] == $response->uuid && $current_status != $received_status) {
						$order_nfe_data[$key]['status'] = $received_status;
						if ($response->modelo == 'lote_rps' && $response->status == 'processado') $is_lote_update = true;
					}
					if ( $order_nfe['uuid'] == $response->uuid ) {
						$is_new = false;
					}
				}

			} else {

				$order_nfe_data = array();

			}

			if ( $is_new ) {
				$order_nfe_data[] = array(
					'uuid'   => (string) $response->uuid,
					'status' => (string) $response->status,
					'modelo' => (string) $response->modelo,
					'n_nfe' => (int) $response->numero ?: $response->numero_lote,
					'n_serie' => "{$response->serie_rps}:{$response->numero_rps}",
					'url_xml' => (string) $response->xml,
					'url_pdf' => (string) $response->url_pdf ?? '', 
					'pdf_rps' => (string) $response->pdf_rps ?? '',
					'data' => date_i18n('d/m/Y'),
					'motivo' => is_array($response->motivo) ? implode(' | ', $response->motivo) : $response->motivo
				);
				if ($response->modelo == 'lote_rps' && $response->status == 'processado') $is_lote_update = true;
			}

			if ($is_lote_update) {
				$this->get_credentials();
				$webmaniabr_nfse = new NFSe($this->settings['bearer_access_token']);
				foreach ($response->info_nfse as $nfse) {
					$result = $webmaniabr_nfse->consultaNotaFiscal($nfse->uuid);
					if ($result->status == 'aprovado') {
						$order_nfe_data[] = array(
							'uuid'   => (string) $result->uuid,
							'status' => (string) $result->status,
							'modelo' => (string) $result->modelo,
							'n_nfe' => (int) $result->numero,
							'n_serie' => "{$result->serie_rps}:{$result->numero_rps}",
							'url_xml' => (string) $result->xml,
							'url_pdf' => (string) $result->url_pdf ?? '', 
							'pdf_rps' => (string) $result->pdf_rps ?? '',
							'data' => date_i18n('d/m/Y')
						);
					} 
				}
			}

			update_post_meta($order_id, 'nfe', $order_nfe_data);

		}

	}

	/**
	 * Display Billing Address
	 *
	 * @return string
	 */
	function order_data_after_billing_address( $order ){

		$html = '<style>.address{display:none;}</style>';
		$html .= '<script>(function( $ ){
				$(".edit_address").on("click", function(){
					$(this).parent().parent().find(".wcbcf-address").hide();
				});
		})( jQuery );</script>';
		$html .= '<div class="clear"></div>';
		$html .= '<div class="wcbcf-address">';
		if ( ! $order->get_formatted_billing_address() ) {
			$html .= '<p class="none_set"><strong>' . __( 'Endereço', $this->domain ) . ':</strong> ' . __( 'Nenhum endereço de cobrança definido.', $this->domain ) . '</p>';
		} else {
			$html .= '<br />';
			$html .= $order->get_formatted_billing_address();
			$html .= '</p>';
		}
		$html .= '<h4>' . __( 'Informações do cliente', $this->domain ) . '</h4>';
		$html .= '<p>';
		// Person type information.
		if ( 1 == get_post_meta( $order->get_id(), '_billing_persontype', true ) ) $html .= '<strong>' . __( 'CPF', $this->domain ) . ': </strong>' . esc_html( get_post_meta( $order->get_id(), '_billing_cpf', true ) ) . '<br />';
		if ( 2 == get_post_meta( $order->get_id(), '_billing_persontype', true ) ) {
			$html .= '<strong>' . __( 'Razão Social', $this->domain ) . ': </strong>' . esc_html( get_post_meta( $order->get_id(), '_billing_company', true ) ) . '<br />';
			$html .= '<strong>' . __( 'CNPJ', $this->domain ) . ': </strong>' . esc_html( get_post_meta( $order->get_id(), '_billing_cnpj', true ) ) . '<br />';
			if ( ! empty( get_post_meta( $order->get_id(), '_billing_ie', true ) ) ) {
				$html .= '<strong>' . __( 'I.E', $this->domain ) . ': </strong>' . esc_html( get_post_meta( $order->get_id(), '_billing_ie', true ) ) . '<br />';
			}
		}
		if ( ! empty( get_post_meta( $order->get_id(), '_billing_birthdate', true ) ) ) {
			// Birthdate information.
			$html .= '<strong>' . __( 'Data de nascimento', $this->domain ) . ': </strong>' . esc_html( get_post_meta( $order->get_id(), '_billing_birthdate', true ) ) . '<br />';
			// Sex Information.
			$html .= '<strong>' . __( 'Sexo', $this->domain ) . ': </strong>' . esc_html( get_post_meta( $order->get_id(), '_billing_sex', true ) ) . '<br />';
		}
		$html .= '<strong>' . __( 'Telefone', $this->domain ) . ': </strong>' . esc_html( str_replace("?", "", get_post_meta( $order->get_id(), '_billing_cellphone', true ) ) ) . '<br />';
		// Cell Phone Information.
		if ( ! empty( str_replace("?", "", get_post_meta( $order->get_id(), '_billing_cellphone', true ) ) ) ) {
			$html .= '<strong>' . __( 'Telefone Cel.', $this->domain ) . ': </strong>' . esc_html( str_replace("?", "", get_post_meta( $order->get_id(), '_billing_cellphone', true ) ) ) . '<br />';
		}
		$html .= '<strong>' . __( 'Email', $this->domain ) . ': </strong>' . make_clickable( esc_html( $order->get_billing_email() ) ) . '<br />';
		$html .= '</p>';
		$html .= '</div>';

		echo $html;

	}

	/**
	 * Display Shipping Address
	 *
	 * @return string
	 */
	public function order_data_after_shipping_address( $order ) {

		global $post;

		$html = '<div class="clear"></div>';
		$html .= '<script>(function( $ ){
				$(".edit_address").on("click", function(){
					$(this).parent().parent().find(".wcbcf-address").hide();
				});
		})( jQuery );</script>';
		$html .= '<div class="wcbcf-address">';
		if ( ! $order->get_formatted_shipping_address() ) {
			$html .= '<p class="none_set"><strong>' . __( 'Endereço', $this->domain ) . ':</strong> ' . __( 'Nenhum endereço de envio definido.', $this->domain ) . '</p>';
		} else {
			$html .= '<br />';
			$html .= $order->get_formatted_shipping_address();
			$html .= '</p>';
		}
		if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' == get_option( 'woocommerce_enable_order_comments', 'yes' ) ) && $post->post_excerpt ) {
			$html .= '<p><strong>' . __( 'Nota do cliente', $this->domain ) . ':</strong><br />' . nl2br( esc_html( $post->post_excerpt ) ) . '</p>';
		}
		$html .= '</div>';

		echo $html;

	}

	/**
	 * Display Messages
	 *
	 * @return void
	 */
	function display_messages(){

		if (get_option('woocommercenfe_error_messages')){
			?>
			<div class="error">
				<?php foreach (get_option('woocommercenfe_error_messages') as $message) { echo '<p>'.$message.'</p>'; } ?>
			</div>
			<?php
			delete_option('woocommercenfe_error_messages');
		}

		if (get_option('woocommercenfe_success_messages')){
			?>
			<div class="updated notice notice-success">
				<?php foreach (get_option('woocommercenfe_success_messages') as $message) { echo '<p>'.$message.'</p>'; } ?>
			</div>
			<?php
			delete_option('woocommercenfe_success_messages');
		}

	}

	/**
	 * Certificate A1 validate
	 *
	 * @return void
	 */
	function validate_certificate( $force_update = false, $return_ajax = false ){

		// Looking for credentials
		$old_credentials = '';
		$this->get_credentials();

		if (!$this->settings['consumer_key'] && !$this->settings['consumer_secret'] && !$this->settings['oauth_access_token'] && !$this->settings['oauth_access_token_secret']){
			return;
		}

		// Credentials are empty
		if ( !$this->settings ) {

			if ($return_ajax)
				return json_encode( array( 'status' => 'null_credentials', 'msg' => 'Por favor, informe as credenciais de acesso para obter a validade do Certificado Digital A1.' ), JSON_UNESCAPED_UNICODE );

			return false;

		}
		
		// Credentials are invalid
		if (!$this->validate_credentials($this->settings)) {

			$msg = 'Credenciais de acesso inválidas. Por favor, informe credenciais de acesso válidas para obter a validade do Certificado Digital A1.';

			if ($return_ajax)
				return json_encode( array( 'status' => 'invalid_credentials', 'msg' => $msg ), JSON_UNESCAPED_UNICODE );

			$this->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> ' . $msg, $this->domain) );
			return false;

		}

		//Check if credentials are in cache
		if (get_transient('validadeCertificado') && $old_credentials = get_option('old_credentials') && !$force_update) {

			$response = get_transient('validadeCertificado');
			$cached = true;

		} 

		// If credentials have been changed or are not in cache, connect to API
		if ($old_credentials != $this->settings || !$cached) {

			$webmaniabr = new NFe( $this->settings );
			$response = $webmaniabr->validadeCertificado();

		}

		// Error
		if (isset($response->error)){

			if (!$cached){

				set_transient( 'validadeCertificado', $response, 24 * HOUR_IN_SECONDS );
				if ($old_credentials != $this->settings) {
					update_option('old_credentials', $this->settings);
				}

			}

			if (strpos(strtolower($response->error), 'não encontrado') === false && strpos(strtolower($response->error), 'obrigatório') === false){

				$this->add_error( __('Erro: '.$response->error, $this->domain) );

			}

			if ($return_ajax) {

				return json_encode( array( 'status' => 'error', 'msg' => $response->error ), JSON_UNESCAPED_UNICODE );

			}

			return false;

		} else {

			// Sucess
			set_transient( 'validadeCertificado', $response, 24 * HOUR_IN_SECONDS );
			if ($old_credentials != $this->settings) {
				update_option('old_credentials', $this->settings);
			}

			if ($return_ajax)
				return json_encode( array( 'status' => 'success', 'msg' => $response ), JSON_UNESCAPED_UNICODE );

			if ($response < 45 && $response >= 1){
				$this->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> Emita um novo Certificado Digital A1 - vencerá em '.$response.' dias.', $this->domain) );
				return false;
			}

			if (!$response) {
				$this->add_error( __('<strong>Nota Fiscal WebmaniaBR®:</strong> Certificado Digital A1 vencido. Emita um novo para continuar operando.', $this->domain) );
				return false;
			}

		}

	}

	/**
	 * Return alerts to users from plugins
	 * that has incompatibility
	 *
	 * @return void
	**/
	public function wmbr_compatibility_issues() {

		if ( isset($_POST['action']) )
			return;

		if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
			return;

		$plugins_list = array(
			//'plugin-name/plugin.php' => 'Plugin name'
		);

		if ($plugins_list && count($plugins_list) > 0){
			foreach ( $plugins_list as $plugin_path => $plugin_name ) {

				if ( self::wmbr_is_plugin_active($plugin_path) ) {
	
					echo '<div class="error">
							<p>O plugin <b>'.$plugin_name.'</b> não possui compatibilidade com os plugins <b>WooCommerce</b> e <b>Nota Fiscal Eletrônica WooCommerce</b>.</p>
							<p>Por favor, desative-o para prosseguir com as emissões de Nota Fiscal.</p>
						</div>';
	
				}
	
			}
		}
		

	}

	/**
	 * Function to handle ajax requisistion
	 * to force digital certificate update
	 *
	 * @return void
	**/
	public function ajax_force_certificate_update() {

		echo $this->validate_certificate( true, true );
		die();

	}

	/**
	 * WooCommerce API :: Order
	 *
	 * @return array
	**/
	function api_order_response( $order_data, $order, $fields, $server ) {

		// Vars
		$format = new WooCommerceNFeFormat;

		// Billing fields.
		$order_data['billing_address']['persontype']   = $this->get_person_type( get_post_meta( $order->get_id(), '_billing_persontype', true ) );
		$order_data['billing_address']['cpf']          = $format->format_number( get_post_meta( $order->get_id(), '_billing_cpf', true ) );
		$order_data['billing_address']['cnpj']         = $format->format_number( get_post_meta( $order->get_id(), '_billing_cnpj', true ) );
		$order_data['billing_address']['ie']           = $format->format_number( get_post_meta( $order->get_id(), '_billing_ie', true ) );
		$order_data['billing_address']['birthdate']    = $format->get_formatted_birthdate( get_post_meta( $order->get_id(), '_billing_birthdate', true ), $server );
		$order_data['billing_address']['sex']          = substr( get_post_meta( $order->get_id(), '_billing_sex', true ), 0, 1 );
		$order_data['billing_address']['number']       = get_post_meta( $order->get_id(), '_billing_number', true );
		$order_data['billing_address']['neighborhood'] = get_post_meta( $order->get_id(), '_billing_neighborhood', true );
		$order_data['billing_address']['cellphone']    = str_replace("?", "", get_post_meta( $order->get_id(), '_billing_cellphone', true ));

		// Shipping fields.
		$order_data['shipping_address']['number']       = get_post_meta( $order->get_id(), '_shipping_number', true );
		$order_data['shipping_address']['neighborhood'] = get_post_meta( $order->get_id(), '_shipping_neighborhood', true );

		// Customer fields.
		if ( 0 == $order->customer_user && isset( $order_data['customer'] ) ) {
			// Customer billing fields.
			$order_data['customer']['billing_address']['persontype']   = $this->get_person_type( get_post_meta( $order->get_id(), '_billing_persontype', true ) );
			$order_data['customer']['billing_address']['cpf']          = $format->format_number( get_post_meta( $order->get_id(), '_billing_cpf', true ) );
			$order_data['customer']['billing_address']['cnpj']         = $format->format_number( get_post_meta( $order->get_id(), '_billing_cnpj', true ) );
			$order_data['customer']['billing_address']['ie']           = $format->format_number( get_post_meta( $order->get_id(), '_billing_ie', true ) );
			$order_data['customer']['billing_address']['birthdate']    = $format->get_formatted_birthdate( get_post_meta( $order->get_id(), '_billing_birthdate', true ), $server );
			$order_data['customer']['billing_address']['sex']          = substr( get_post_meta( $order->get_id(), '_billing_sex', true ), 0, 1 );
			$order_data['customer']['billing_address']['number']       = get_post_meta( $order->get_id(), '_billing_number', true );
			$order_data['customer']['billing_address']['neighborhood'] = get_post_meta( $order->get_id(), '_billing_neighborhood', true );
			$order_data['customer']['billing_address']['cellphone']    = str_replace("?", "", get_post_meta( $order->get_id(), '_billing_cellphone', true ));

			// Customer shipping fields.
			$order_data['customer']['shipping_address']['number']       = get_post_meta( $order->get_id(), '_shipping_number', true );
			$order_data['customer']['shipping_address']['neighborhood'] = get_post_meta( $order->get_id(), '_shipping_neighborhood', true );
		}

		if ( $fields ) {
			$order_data = WC()->api->WC_API_Customers->filter_response_fields( $order_data, $order, $fields );
		}

		return $order_data;

	}

	/**
	 * WooCommerce API :: Customer
	 *
	 * @return array
	**/
	function api_customer_response( $customer_data, $customer, $fields, $server ) {

        // Billing fields.
		$customer_data['billing_address']['persontype']   = $this->get_person_type( $customer->billing_persontype );
		$customer_data['billing_address']['cpf']          = $format->format_number( $customer->billing_cpf );
		$customer_data['billing_address']['cnpj']         = $format->format_number( $customer->billing_cnpj );
		$customer_data['billing_address']['ie']           = $format->format_number( $customer->billing_ie );
		$customer_data['billing_address']['birthdate']    = $format->get_formatted_birthdate( $customer->billing_birthdate, $server );
		$customer_data['billing_address']['sex']          = substr( $customer->billing_sex, 0, 1 );
		$customer_data['billing_address']['number']       = $customer->billing_number;
		$customer_data['billing_address']['neighborhood'] = $customer->billing_neighborhood;
		$customer_data['billing_address']['cellphone']    = str_replace("?", "", $order->billing_cellphone);

		// Shipping fields.
		$customer_data['shipping_address']['number']       = $customer->shipping_number;
		$customer_data['shipping_address']['neighborhood'] = $customer->shipping_neighborhood;

		if ( $fields ) {
			$customer_data = WC()->api->WC_API_Customers->filter_response_fields( $customer_data, $customer, $fields );
		}

		return $customer_data;

	}

	/**
	 * Add NCM field to product variation
	 * 
	 * @return void
	 */
	function add_ncm_field_product_variation( $loop, $variation_data, $variation ) {

		$value = get_post_meta( $variation->ID, 'variable_ncm', true );

		woocommerce_wp_text_input(
			array(
				'id'            => "variable_ncm{$loop}",
				'name'          => "variable_ncm[{$loop}]",
				'class'					=> "short",
				'value'         => $value,
				'type'     			=> "number",
				'label'         => "NCM",
				'wrapper_class' => "form-row form-row-full"
			)
		);
		
	}

	/**
	 * Save NCM value of product variation
	 * 
	 * @return void
	 */
	function save_ncm_field_product_variation( $variation_id, $i ) {
		
		$ncm = $_POST['variable_ncm'][$i];
		update_post_meta( $variation_id, 'variable_ncm', $ncm );

	}

}

new WooCommerceNFeBackend;
