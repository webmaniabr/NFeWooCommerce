<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<h3>Informações de Pagamento</h3>
<p>Informe a forma de pagamento dos gateways utilizados na loja virtual.<br>
Observação: A descrição do pagamento é obrigatória para o método de pagamento "Outros".
</p>

<div class="nfe-shipping-table payment-info">
	<div class="nfe-table-head nfe-table-head--payment">
		<div><h4 style="padding-left:10px">Gateway</h4></div>
		<div><h4 style="padding-left:10px">Forma de pagamento</h4></div>
		<div><h4 style="padding-left:10px" class="payment-desc-title">Descrição do pagamento</h4></div>
	</div>
	<?php

	$woocommercenfe_available_gateways = WC()->payment_gateways->payment_gateways();
	$woocommercenfe_cnpj               = get_option('wc_settings_woocommercenfe_cnpj_payments', array());

	$woocommercenfe_active_gateways = array();

	foreach($woocommercenfe_available_gateways as $woocommercenfe_gateway){
    if ( isset( $woocommercenfe_gateway->enabled ) && 'yes' === $woocommercenfe_gateway->enabled ) {
      $woocommercenfe_active_gateways[] = $woocommercenfe_gateway;
    }
	}

	?>

	<div class="nfe-table-body nfe-table-body--payment">
	  <div class="entry"></div>
	  <?php

	  foreach($woocommercenfe_active_gateways as $woocommercenfe_gateway):

	  	$woocommercenfe_cnpj_value = '';
	  	if(isset($woocommercenfe_cnpj[$woocommercenfe_gateway->id])) $woocommercenfe_cnpj_value = $woocommercenfe_cnpj[$woocommercenfe_gateway->id];

	  ?>

	    <div class="entry" style="margin-top:0">
	    	<div><h4 style="display:inline-block;min-width:250px"><?php echo esc_html( $woocommercenfe_gateway->method_title ); ?></h4></div>
	    	<div><?php echo wp_kses( $this->get_payment_methods_select( $woocommercenfe_gateway->id ), WooCommerceNFe_Security::form_html_tags() ); ?></div>
				<div><?php echo wp_kses( $this->get_payment_desc_input( $woocommercenfe_gateway->id ), WooCommerceNFe_Security::form_html_tags() ); ?></div>
	    </div>
	  <?php endforeach; ?>

	</div>

</div>
