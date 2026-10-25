<?php
/** Fiscal identifiers: numeric and alphanumeric CNPJ; no platform bootstrap required. */
class WooCommerceNFe_TaxId
{
    /** Normalize document punctuation without discarding letters or invalid characters. */
    public static function normalize_document($document)
    {
        if (!is_string($document) && !is_int($document)) { return ''; }
        return strtoupper(str_replace(array('.', '/', '-', ' ', "\t", "\r", "\n"), '', (string) $document));
    }

    public static function normalize_cnpj_fields($data, $depth = 0)
    {
        if (!is_array($data) || $depth >= 32) { return $data; }
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::normalize_cnpj_fields($value, $depth + 1);
            } elseif ((is_string($value) || is_int($value)) && is_string($key) && (in_array(strtolower($key), array('cnpj', 'cnpj_fabricante', 'cnpj_intermediador', 'cnpj_credenciadora', 'cnpj_beneficiario'), true) ||
                (strtolower($key) === 'documento' && self::is_cnpj($value)))) {
                $data[$key] = self::normalize_document($value);
            }
        }
        return $data;
    }

    public static function normalize_access_key($key)
    {
        $normalized = self::normalize_document($key);
        // Preserve NF-e keys and the 50-character national NFS-e identifiers.
        if (preg_match('/\A(?:[0-9]{6}[A-Z0-9]{12}[0-9]{26}|[A-Z0-9]{50})\z/', $normalized)) { return $normalized; }
        // Historical records and NFS-e identifiers may contain fewer numeric positions.
        return preg_replace('/[^0-9]/', '', is_scalar($key) ? (string) $key : '');
    }

    public static function is_cnpj( $cnpj ) {
        $cnpj = self::normalize_document($cnpj);
        if (!preg_match('/\A[A-Z0-9]{12}[0-9]{2}\z/', $cnpj) || preg_match('/\A([0-9])\1{13}\z/', $cnpj)) {
            return false;
        }
        // Receita Federal: character value is ASCII minus 48; DVs remain numeric.
        for ($length = 12; $length <= 13; $length++) {
            $sum = 0;
            $weight = 2;
            for ($i = $length - 1; $i >= 0; $i--) {
                $sum += (ord($cnpj[$i]) - 48) * $weight;
                $weight = $weight === 9 ? 2 : $weight + 1;
            }
            $remainder = $sum % 11;
            $digit = $remainder < 2 ? 0 : 11 - $remainder;
            if ((int) $cnpj[$length] !== $digit) { return false; }
        }
        return true;
    }
}
