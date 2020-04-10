<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<h3>Informações de Pagamento</h3>
<p>Informe a forma de pagamento dos gateways utilizados na loja virtual.</p>

<div class="nfe-shipping-table payment-info">
	<div class="nfe-table-head nfe-table-head--payment">
		<div><h4 style="padding-left:10px">Gateway</h4></div>
		<div><h4>Forma de pagamento</h4></div>
	</div>
	<?php

	$available_gateways = WC()->payment_gateways->payment_gateways();
	$cnpj               = get_option('wc_settings_woocommercenfe_cnpj_payments', array());

	$active_gateways = array();

	foreach($available_gateways as $gateway){
    $active_gateways[] = $gateway;
	}

	?>

	<div class="nfe-table-body nfe-table-body--payment">
	  <div class="entry"></div>
	  <?php

	  foreach($active_gateways as $gateway):

	  	$cnpj_value = '';
	  	if(isset($cnpj[$gateway->id])) $cnpj_value = $cnpj[$gateway->id];

	  ?>

	    <div class="entry" style="margin-top:0">
	    	<div><h4 style="display:inline-block;min-width:250px"><?php echo $gateway->method_title; ?></h4></div>
	    	<div><?php echo $this->get_payment_methods_select($gateway->id); ?></div>
	    </div>
	  <?php endforeach; ?>

	</div>

</div>
