<?php
if (!defined('ABSPATH')) { exit; }

/** Native Checkout Block fields; legacy checkout hooks remain independent. */
class WooCommerceNFe_Blocks {
    const PREFIX = 'nota-fiscal-eletronica-woocommerce/';
    private static $registered = false;

    public static function register() {
        if (self::$registered || !function_exists('woocommerce_register_additional_checkout_field')) { return; }
        self::$registered = true;
        woocommerce_register_additional_checkout_field(array(
            'id' => self::PREFIX . 'documento', 'label' => __('CPF ou CNPJ', 'nota-fiscal-eletronica-woocommerce'),
            'location' => 'contact', 'type' => 'text', 'required' => true,
            'sanitize_callback' => array('WooCommerceNFe_TaxId', 'normalize_document'),
            'validate_callback' => array(__CLASS__, 'validate_document'),
        ));
        foreach (array('razao-social' => __('Razão social (preencha para CNPJ)', 'nota-fiscal-eletronica-woocommerce'), 'inscricao-estadual' => __('Inscrição estadual', 'nota-fiscal-eletronica-woocommerce')) as $key => $label) {
            woocommerce_register_additional_checkout_field(array(
                'id' => self::PREFIX . $key, 'label' => $label,
                'location' => 'contact', 'type' => 'text', 'required' => false,
                'sanitize_callback' => array(__CLASS__, 'sanitize_text'),
            ));
        }
        foreach (array('numero' => __('Número do endereço', 'nota-fiscal-eletronica-woocommerce'), 'bairro' => __('Bairro', 'nota-fiscal-eletronica-woocommerce')) as $key => $label) {
            woocommerce_register_additional_checkout_field(array(
                'id' => self::PREFIX . $key, 'label' => $label,
                'location' => 'address', 'type' => 'text', 'required' => true,
                'sanitize_callback' => array(__CLASS__, 'sanitize_text'),
            ));
        }
        add_action('woocommerce_blocks_validate_location_contact_fields', array(__CLASS__, 'validate_contact'), 10, 3);
        add_action('woocommerce_store_api_checkout_update_order_from_request', array(__CLASS__, 'update_order'), 10, 2);
    }

    public static function sanitize_text($value) {
        return is_string($value) ? sanitize_text_field($value) : '';
    }

    public static function validate_document($value) {
        $document = WooCommerceNFe_TaxId::normalize_document($value);
        if ((preg_match('/\A[0-9]{11}\z/', $document) && WooCommerceNFeFormat::is_cpf($document)) || WooCommerceNFe_TaxId::is_cnpj($document)) { return; }
        return new WP_Error('wmbr_invalid_document', __('Informe um CPF ou CNPJ válido.', 'nota-fiscal-eletronica-woocommerce'));
    }

    public static function validate_contact($errors, $fields, $group) {
        $document = isset($fields[self::PREFIX . 'documento']) ? $fields[self::PREFIX . 'documento'] : '';
        if (WooCommerceNFe_TaxId::is_cnpj($document) && empty($fields[self::PREFIX . 'razao-social'])) {
            $errors->add('wmbr_missing_company', __('Informe a razão social para o CNPJ.', 'nota-fiscal-eletronica-woocommerce'), array('key' => self::PREFIX . 'razao-social'));
        }
    }

    private static function field($order, $fields, $key, $group = 'other') {
        $id = self::PREFIX . $key;
        $value = isset($fields[$id]) ? $fields[$id] : $order->get_meta('_wc_' . $group . '/' . $id, true);
        return self::sanitize_text($value);
    }

    public static function update_order($order, $request) {
        if (!is_a($order, 'WC_Order')) { return; }
        $fields = $request->get_param('additional_fields');
        $fields = is_array($fields) ? $fields : array();
        $document = WooCommerceNFe_TaxId::normalize_document(self::field($order, $fields, 'documento'));
        if ($document === '' || is_wp_error(self::validate_document($document))) { return; }
        $company = WooCommerceNFe_TaxId::is_cnpj($document);
        $order->update_meta_data('_billing_persontype', $company ? '2' : '1');
        $order->update_meta_data('_billing_cpf', $company ? '' : $document);
        $order->update_meta_data('_billing_cnpj', $company ? $document : '');
        $order->update_meta_data('_billing_ie', $company ? self::field($order, $fields, 'inscricao-estadual') : '');
        if ($company) { $order->set_billing_company(self::field($order, $fields, 'razao-social')); }
        foreach (array('billing', 'shipping') as $group) {
            $address = $request->get_param($group . '_address');
            $address = is_array($address) ? $address : array();
            foreach (array('numero' => 'number', 'bairro' => 'neighborhood') as $key => $meta) {
                $value = self::field($order, $address, $key, $group);
                if ($value !== '') { $order->update_meta_data('_' . $group . '_' . $meta, $value); }
            }
        }
        // Store API persists the order after this hook and before payment processing.
    }
}
