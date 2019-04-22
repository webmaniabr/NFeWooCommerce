jQuery(function($){

    if (WooCommerceNFe.maskedinput) {

        $('#billing_cpf').mask('999.999.999-99');
        $('#billing_cnpj').mask('99.999.999/9999-99');
        $('#billing_postcode').mask('99999-999');
        $('#shipping_postcode').mask('99999-999');
        $('#billing_birthdate').mask('99/99/9999');

        $('#billing_phone, #billing_cellphone').focusin(function(){
            $(this).unmask().mask('(99) 99999-9999');
        });

        $('#billing_phone, #billing_cellphone').focusout(function(){
          var phone, element;
          element = $(this);
          element.unmask();
          phone = element.val().replace(/\D/g, '');

          if (phone.length > 10) {
              element.mask('(99) 99999-9999');
          } else {
              element.mask('(99) 9999-9999');
          }
        }).trigger('focusout');

    }

    if (WooCommerceNFe.cep) {

        correios.init( 'qS4SKlmAXR21h7wrBMcs0SZyXauLqo5m', 'nkKkInYJ5QvogYn1xj4lk7w3hkhA8qzruoKzuLf6UyBtSIJL' );
        $('#billing_postcode').correios( '#billing_address_1', '#billing_neighborhood', '#billing_city', '#billing_state', '#loading', false );
        $('#shipping_postcode').correios( '#shipping_address_1', '#shipping_neighborhood', '#shipping_city', '#shipping_state', '#loading', false );

    }

    if (WooCommerceNFe.person_type) {

        set_persontype = function(){

            var person_type = $('#billing_persontype').val();

            if (person_type == 1) {

                $("#billing_cpf_field").fadeIn();
                $("#billing_cnpj_field, #billing_ie_field, #billing_company_field").hide();

            }

            if (person_type == 2) {

                $("#billing_cnpj_field, #billing_ie_field, #billing_company_field").fadeIn();
                $("#billing_cpf_field").hide();

            }

        }

        set_persontype();
        $('#billing_persontype').on('change', set_persontype);

    }

});
