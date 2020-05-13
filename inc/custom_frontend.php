<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WooCommerceNFe_Frontend extends WooCommerceNFe {

    public static function scripts(){

        $version = self::$version;
        $array = array();

        $tipo_pessoa = get_option('wc_settings_woocommercenfe_tipo_pessoa');
        $mascara_campos = get_option('wc_settings_woocommercenfe_mascara_campos');
        $cep = get_option('wc_settings_woocommercenfe_cep');

        wp_register_script( 'woocommercenfe_maskedinput', '//cdnjs.cloudflare.com/ajax/libs/jquery.maskedinput/1.4.1/jquery.maskedinput.js', array('jquery'), $version, true );
        wp_register_script( 'woocommercenfe_correios', apply_filters( 'woocommercenfe_plugins_url', plugins_url( 'assets/js/correios.min.js', __FILE__ ) ), array('jquery'), $version, true );
        wp_register_script( 'woocommercenfe_scripts', apply_filters( 'woocommercenfe_plugins_url', plugins_url( 'assets/js/scripts.js', __FILE__ ) ), array('jquery'), $version, true );

        if ($mascara_campos == 'yes') $array['maskedinput'] = 1;
        if ($cep == 'yes') $array['cep'] = 1;
        if ($tipo_pessoa == 'yes') $array['person_type'] = 1;

        if ($mascara_campos == 'yes') wp_enqueue_script( 'woocommercenfe_maskedinput' );
        if ($cep == 'yes') wp_enqueue_script( 'woocommercenfe_correios' );
        if ($array) wp_localize_script( 'woocommercenfe_scripts', 'WooCommerceNFe', $array);
        if ($cep == 'yes' || $mascara_campos == 'yes') wp_enqueue_script( 'woocommercenfe_scripts' );

    }

    function billing_fields( $fields ){

      global $domain;

      $new_fields = array(
        'billing_persontype' => array(
          'type'     => 'select',
          'label'    => __( 'Tipo Pessoa', $domain ),
          'class'    => array( 'form-row-wide', 'person-type-field' ),
          'required' => false,
          'options'  => array(
              '1' => __( 'Pessoa Física', $domain ),
              '2' => __( 'Pessoa Jurídica', $domain )
          )
        ),
        'billing_cpf' => array(
          'label'       => __( 'CPF', $domain ),
          'placeholder' => _x( 'CPF', 'placeholder', $domain ),
          'class'       => array( 'form-row-wide', 'person-type-field' ),
          'required'    => false
        ),
        'billing_cnpj' => array(
          'label'       => __( 'CNPJ', $domain ),
          'placeholder' => _x( 'CNPJ', 'placeholder', $domain ),
          'class'       => array( 'form-row-first', 'person-type-field' ),
          'required'    => false
        ),
        'billing_ie' => array(
          'label'       => __( 'Inscrição Estadual', $domain ),
          'placeholder' => _x( 'Inscrição Estadual', 'placeholder', $domain ),
          'class'       => array( 'form-row-last', 'person-type-field' ),
          'required'    => false
        ),
        'billing_company' => array(
      		'label'       => __( 'Razão Social', $domain ),
      		'placeholder' => _x( 'Razão Social', 'placeholder', $domain ),
      		'class'       => array( 'form-row-wide', 'person-type-field' ),
      		'required'    => false
      	),
        'billing_first_name' => array(
      		'label'       => __( 'Nome', $domain ),
      		'placeholder' => _x( 'Nome', 'placeholder', $domain ),
      		'class'       => array( 'form-row-first' ),
      		'required'    => true
      	),
        'billing_last_name' => array(
      		'label'       => __( 'Sobrenome', $domain ),
      		'placeholder' => _x( 'Sobrenome', 'placeholder', $domain ),
      		'class'       => array( 'form-row-last' ),
      		'required'    => true,
          'clear'       => true,
      	),
        'billing_birthdate' => array(
      		'label'       => __( 'Nascimento', $domain ),
      		'placeholder' => _x( 'Nascimento', 'placeholder', $domain ),
      		'class'       => array( 'form-row-first' ),
      		'required'    => false
      	),
        'billing_sex' => array(
      		'type'        => 'select',
      		'label'       => __( 'Sexo', $domain ),
      		'class'       => array( 'form-row-last' ),
      		'clear'       => true,
      		'required'    => true,
      		'options'     => array(
      			__( 'Feminino', $domain ) => __( 'Feminino', $domain ),
      			__( 'Masculino', $domain )   => __( 'Masculino', $domain )
      		)
    	  ),
        'billing_postcode' => array(
          'label'       => __( 'CEP', $domain ),
          'placeholder' => _x( 'CEP', 'placeholder', $domain ),
          'class'       => array( 'form-row-first', 'update_totals_on_change', 'address-field' ),
          'required'    => true
        ),
        'billing_state' => array(
          'type'        => 'state',
          'label'       => __( 'Estado', $domain ),
          'placeholder' => _x( 'Estado', 'placeholder', $domain ),
          'class'       => array( 'form-row-last', 'address-field' ),
          'clear'       => true,
          'required'    => true
        ),
        'billing_city' => array(
          'label'       => __( 'Cidade', $domain ),
          'placeholder' => _x( 'Cidade', 'placeholder', $domain ),
          'class'       => array( 'form-row-first', 'address-field' ),
          'required'    => true
        ),
        'billing_neighborhood' => array(
          'label'       => __( 'Bairro', $domain ),
          'placeholder' => _x( 'Bairro', 'placeholder', $domain ),
          'class'       => array( 'form-row-last', 'address-field' ),
          'clear'       => true,
        ),
        'billing_address_1' => array(
          'label'       => __( 'Endereço', $domain ),
          'placeholder' => _x( 'Endereço', 'placeholder', $domain ),
          'class'       => array( 'form-row-wide', 'address-field' ),
          'required'    => true
        ),
        'billing_number' => array(
          'label'       => __( 'Número', $domain ),
          'placeholder' => _x( 'Número', 'placeholder', $domain ),
          'class'       => array( 'form-row-first', 'address-field' ),
          'required'    => true
        ),
        'billing_address_2' => array(
          'label'       => __( 'Complemento', $domain ),
          'placeholder' => _x( 'Complemento', 'placeholder', $domain ),
          'class'       => array( 'form-row-last', 'address-field' ),
          'clear'       => true,
        ),
        'billing_phone' => array(
      		'label'       => __( 'Telefone Fixo', $domain ),
      		'placeholder' => _x( 'Telefone Fixo', 'placeholder', $domain ),
      		'class'       => array( 'form-row-first' ),
      		'required'    => true
      	),
        'billing_cellphone' => array(
        	'label'       => __( 'Celular', $domain ),
        	'placeholder' => _x( 'Celular', 'placeholder', $domain ),
        	'class'       => array( 'form-row-last' ),
        	'clear'       => true
        ),
        'billing_email' => array(
      		'label'       => __( 'E-mail', $domain ),
      		'placeholder' => _x( 'E-mail', 'placeholder', $domain ),
      		'class'       => array( 'form-row-wide' ),
      		'validate'    => array( 'email' ),
      		'clear'       => true,
      		'required'    => true
      	)
      );

      return $new_fields;

    }

    function shipping_fields( $fields ){

        global $domain;

        $new_fields = array(
            'shipping_first_name' => array(
                'label'       => __( 'Nome', $domain ),
                'placeholder' => _x( 'Nome', 'placeholder', $domain ),
                'class'       => array( 'form-row-first' ),
                'required'    => true
            ),
            'shipping_last_name' => array(
                'label'       => __( 'Sobrenome', $domain ),
                'placeholder' => _x( 'Sobrenome', 'placeholder', $domain ),
                'class'       => array( 'form-row-last' ),
                'clear'       => true,
                'required'    => true
            ),
            'shipping_postcode' => array(
                'label'       => __( 'CEP', $domain ),
                'placeholder' => _x( 'CEP', 'placeholder', $domain ),
                'class'       => array( 'form-row-first', 'update_totals_on_change', 'address-field' ),
                'required'    => true
            ),
            'shipping_state' => array(
                'type'        => 'state',
                'label'       => __( 'Estado', $domain ),
                'placeholder' => _x( 'Estado', 'placeholder', $domain ),
                'class'       => array( 'form-row-last', 'address-field' ),
                'clear'       => true,
                'required'    => true
            ),
            'shipping_city' => array(
                'label'       => __( 'Cidade', $domain ),
                'placeholder' => _x( 'Cidade', 'placeholder', $domain ),
                'class'       => array( 'form-row-first', 'address-field' ),
                'required'    => true
            ),
            'shipping_neighborhood' => array(
                'label'       => __( 'Bairro', $domain ),
                'placeholder' => _x( 'Bairro', 'placeholder', $domain ),
                'class'       => array( 'form-row-last', 'address-field' ),
                'clear'       => true,
            ),
            'shipping_address_1' => array(
                'label'       => __( 'Endereço', $domain ),
                'placeholder' => _x( 'Endereço', 'placeholder', $domain ),
                'class'       => array( 'form-row-wide', 'address-field' ),
                'required'    => true
            ),
            'shipping_number' => array(
                'label'       => __( 'Número', $domain ),
                'placeholder' => _x( 'Número', 'placeholder', $domain ),
                'class'       => array( 'form-row-first', 'address-field' ),
                'required'    => true
            ),
            'shipping_address_2' => array(
                'label'       => __( 'Complemento', $domain ),
                'placeholder' => _x( 'Complemento', 'placeholder', $domain ),
                'class'       => array( 'form-row-last', 'address-field' ),
                'clear'       => true,
            )
        );

        return $new_fields;

    }

    function valide_checkout_fields(){

        $billing_persontype = isset( $_POST['billing_persontype'] ) ? $_POST['billing_persontype'] : 0;

        if ($billing_persontype == 1){

            if (empty( $_POST['billing_cpf'] )){

                wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'CPF', $domain ), __( 'é um campo obrigatório', $domain ) ), 'error' );

            }

            if (!empty( $_POST['billing_cpf'] ) && !WooCommerceNFe_Format::is_cpf( $_POST['billing_cpf'] )){

                wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'CPF', $domain ), __( 'informado não é válido', $domain ) ), 'error' );

            }

        }

        if ($billing_persontype == 2){

            if (empty( $_POST['billing_cnpj'] )){

                wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'CNPJ', $domain ), __( 'é um campo obrigatório', $domain ) ), 'error' );

            }

            if (empty( $_POST['billing_company'] )){

                wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'Razão Social', $domain ), __( 'é um campo obrigatório', $domain ) ), 'error' );

            }

            if (!empty( $_POST['billing_cnpj'] ) && !WooCommerceNFe_Format::is_cnpj( $_POST['billing_cnpj'] )){

                wc_add_notice( sprintf( '<strong>%s</strong> %s.', __( 'CNPJ', $domain ), __( 'informado não é válido', $domain ) ), 'error' );

            }

        }

    }

    function localisation_address_formats( $formats ){

        $formats['BR'] = "Nome: {name}\nEndereço: {address_1}, {number}\nComplemento: {address_2}\nBairro: {neighborhood}\nCidade: {city}\nEstado: {state}\nCEP: {postcode}";
				$formats['default'] = "Nome: {name}\nEndereço: {address_1}, {number}\nComplemento: {address_2}\nBairro: {neighborhood}\nCidade: {city}\nEstado: {state}\nCEP: {postcode}";

		return $formats;

    }

    function formatted_address_replacements( $replacements, $args ) {
		extract( $args );

		$replacements['{number}']       = $number;
		$replacements['{neighborhood}'] = $neighborhood;

		return $replacements;
	}

    function order_formatted_billing_address( $address, $order ) {

        $address['number']       = $order->billing_number;
		$address['neighborhood'] = $order->billing_neighborhood;

		return $address;
	}

    function order_formatted_shipping_address( $address, $order ) {

        $address['number']       = $order->shipping_number;
		$address['neighborhood'] = $order->shipping_neighborhood;

		return $address;
	}

    function my_account_my_address_formatted_address( $address, $customer_id, $name ) {

        $address['number']       = get_user_meta( $customer_id, $name . '_number', true );
		$address['neighborhood'] = get_user_meta( $customer_id, $name . '_neighborhood', true );

		return $address;
	}

}

?>
