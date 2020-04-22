<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
<style>
.page-title{
  margin-bottom: 30px;
}

.pending-orders{
  display: inline-block;
  width: 100%;
}

.pending-orders li{
  padding: 10px 15px;

}

tr.manage-column.sortable {
  height: 50px;
}

th.manage-column,
td {
  text-align: center;
}

.order_number_column {
  width: 100px;
}

.order-action{
  display: inline-block;
  text-align: center;
  vertical-align: middle;
  background: #27b574;
  color: #FFF;
  padding: 5px;
  border: 0;
  text-decoration: none;
  cursor: pointer;
  margin-top: 5px;
  border-radius: 4px;
}

.order-action:hover {
  opacity: 0.5;
}

.order-action.disabled{
  color: #c1c1c1;
  cursor: not-allowed;
}

a.order-action:hover{
  color: #FFF;
}

.order-action.view-order,
.order-action.ignore-order{
  width: 150px;
}

.order-action.update-order{
  background: #c33a3a;
}

.order-action.ignore-order{
  background: #868686;
  width: 160px;
  height: 28px;
}

.ignore-order-response {
  color: #2DB979;
  font-size: 14px;
}

.empty-orders{
  font-size: 20px;
  color: #a2a2a2;
}

.widefat td {
  vertical-align: inherit;
}

@media screen and (max-width: 781px) {
  tr.manage-column.sortable {
    display: none;
  }
}

</style>


<div class="wrap-page" style="background:#FFF; padding:15px; margin: 5px 15px 2px;border-radius: 10px;">
  <h1 class="page-title">
    Notificações - Nota Fiscal Eletrônica
  </h1>

<?php if(empty($ids_db)): ?>
  <p class="empty-orders">Tudo certo 😉, as emissões de Nota Fiscal estão ocorrendo normalmente.</p>
<?php else: ?>
  <p>Foram realizadas emissões de Nota Fiscal que resultaram em falha nos seguintes pedidos:</p>
  <ul class="pending-orders">

    <?php

      echo '<table class="wp-list-table widefat fixed striped posts">
        <thead>
          <tr class="manage-column column-order_date sortable desc">
            <th class="manage-column column-order_status order_number_column" scope="col">N. do Pedido</th>
            <th class="manage-column column-order_status" scope="col">Data e Hora da Falha</th>
            <th class="manage-column column-order_status" scope="col">Erro</th>
            <th class="manage-column column-order_status" scope="col" class="actions">Ações</th>
          </tr>
        </thead>
        <tbody class="the-list">';

      foreach( $ids_db as $key => $info ){

        $edit_link = get_edit_post_link($key);
        $order = wc_get_order($key);

        echo '<tr>
            <td scope="row">#'.$key.'</td>
            <td>'.$info["datetime"].'</td>
            <td>'.$info["error"].'</td>
            <td>
              <a href="'.$edit_link.'"  target="_blank" class="order-action view-order">Visualizar Pedido</a>
              <button type="button" class="order-action ignore-order" data-order-id="'.$key.'">Ignorar Pedido</button>
            </td>
          </tr>';

      }

      echo '</tbody>
      </table>';

    ?>

  </ul>
<?php endif; ?>
</div>


<?php $ajax_nonce = wp_create_nonce( 'G7EZCEv3tA' ); ?>
<script>

jQuery(document).ready(function($){

  $('.ignore-order').click(function(){
    var order_id = $(this).attr('data-order-id');
    var result = confirm('Tem certeza que deseja remover o pedido #'+order_id+' da lista?');

    var self = $(this);

    if(result){

      var original_content = $(this).html();
      $(this).html('Aguarde...').addClass('disabled');

      $.ajax({
        method: 'POST',
        url: ajaxurl,
        data:{
          action: 'wmbr_remove_order_id_auto_invoice',
          sec_nonce: '<?php echo $ajax_nonce; ?>',
          order_id : order_id,
        }
      }).done(function(response){
        result = $.parseJSON(response);
        if(typeof result.success != 'undefined'){
          self.parents('td').append('<p class="ignore-order-response">Pedido ignorado</p>');
          self.parents('td').find('button, a').remove();
        }else{
          self.html(original_content);
        }
      });
    }
  });


});

</script>
