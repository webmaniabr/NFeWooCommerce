jQuery( function ( $ ) {

    load_billing = function(){

        data = {
            user_id:      $( '#customer_user' ).val(),
            type_to_load: 'billing',
            action:       'woocommerce_get_customer_details',
            security:     woocommerce_admin_meta_boxes.get_customer_details_nonce
        };

        $.ajax({
            url: woocommerce_admin_meta_boxes.ajax_url,
            data: data,
            type: 'POST',
            success: function( response ) {
                var info = response;
                if ( info ) {
                    $( '#_billing_persontype' ).val( info.billing_persontype ).change();
                    $( 'input#_billing_cpf' ).val( info.billing_cpf ).change();
                    $( 'input#_billing_cnpj' ).val( info.billing_cnpj ).change();
                    $( 'input#_billing_ie' ).val( info.billing_ie ).change();
                    $( 'input#_billing_birthdate' ).val( '' ).change();
                    $( 'input#_billing_sex' ).val( '' ).change();
                    $( 'input#_billing_number' ).val( info.billing_number ).change();
                    $( 'input#_billing_neighborhood' ).val( info.billing_neighborhood ).change();
                    $( 'input#_billing_address_1' ).val( info.billing_address_1 ).change();
                    $( 'input#_billing_address_2' ).val( info.billing_address_2 ).change();
                    $( 'input#_billing_first_name' ).val( info.billing_first_name ).change();
                    $( 'input#_billing_last_name' ).val( info.billing_last_name ).change();
                    $( 'input#_billing_city' ).val( info.billing_city ).change();
                    $( 'input#_billing_postcode' ).val( info.billing_postcode ).change();
                    $( '#_billing_country' ).val( info.billing_country ).change();
                    $( '#_billing_state' ).val( info.billing_state ).change();
                    $( 'input#_billing_email' ).val( info.billing_email ).change();
                    $( 'input#_billing_phone' ).val( info.billing_phone ).change();
                    $( 'input#_billing_company' ).val( info.billing_company ).change();
                }
            }
        });

    }

    load_shipping = function(){

        data = {
            user_id:      $( '#customer_user' ).val(),
            type_to_load: 'shipping',
            action:       'woocommerce_get_customer_details',
            security:     woocommerce_admin_meta_boxes.get_customer_details_nonce
        };

        $.ajax({
            url: woocommerce_admin_meta_boxes.ajax_url,
            data: data,
            type: 'POST',
            success: function( response ) {
                var info = response;
                if ( info ) {
                    $( 'input#_shipping_number' ).val( info.shipping_number ).change();
                    $( 'input#_shipping_neighborhood' ).val( info.shipping_neighborhood ).change();
                    $( 'input#_shipping_first_name' ).val( info.shipping_first_name ).change();
                    $( 'input#_shipping_last_name' ).val( info.shipping_last_name ).change();
                    $( 'input#_shipping_company' ).val( info.shipping_company ).change();
                    $( 'input#_shipping_address_1' ).val( info.shipping_address_1 ).change();
                    $( 'input#_shipping_address_2' ).val( info.shipping_address_2 ).change();
                    $( 'input#_shipping_city' ).val( info.shipping_city ).change();
                    $( 'input#_shipping_postcode' ).val( info.shipping_postcode ).change();
                    $( '#_shipping_country' ).val( info.shipping_country ).change();
                    $( '#_shipping_state' ).val( info.shipping_state ).change();
                }

            }
        });

    }

    change_customer_user = function(){

        load_billing();
        load_shipping();

    }

    $( 'a.load_customer_billing' ).on( 'click', load_billing );
    $( 'a.load_customer_shipping' ).on( 'click', load_shipping );
    $( '#customer_user' ).on( 'change', change_customer_user );

    $(document).ready(function(){
      $('.single').click(function(e){
        var target = $(e.target);
        var block_classes = ['wrt', 'update-nfe', 'danfe-icon'];
        var toggle = true;

        block_classes.forEach(function(value, index){
          console.log(value);
          if(target.hasClass(value)){
            toggle = false;
          }
        });

        if(toggle){
          var rotate = $(this).find('.extra').css('display');


          if(rotate == 'none'){
            $(this).find('.expand-nfe').css('transform', 'rotate(180deg)').css('-webkit-transform', 'rotate(180deg)');
          }else{
            $(this).find('.expand-nfe').css('transform', 'rotate(0deg)').css('-webkit-transform', 'rotate(0deg)');
          }
          console.log(rotate);

          $(this).find('.extra').slideToggle('fast');
        }



      });
    });

});
