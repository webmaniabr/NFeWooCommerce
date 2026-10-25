<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once __DIR__ . '/inc/class-tax-id.php';

class WooCommerceNFeFormat extends WooCommerceNFe {

    public static function normalize_document($document) {
        return WooCommerceNFe_TaxId::normalize_document($document);
    }

    public static function normalize_cnpj_fields($data, $depth = 0) {
        return WooCommerceNFe_TaxId::normalize_cnpj_fields($data, $depth);
    }

    public static function normalize_access_key($key) {
        return WooCommerceNFe_TaxId::normalize_access_key($key);
    }

	/**
	 * Formatting helpers must not re-run the plugin bootstrap (WooCommerceNFe::__construct):
	 * each instance would register another woocommerce_order_status_changed handler.
	 */
	public function __construct() {}

	/**
	 * CPF format
	 * 
	 * @return string
	 */
	public static function cpf( $string ){

		if (!$string) return;
		$string = self::clear( $string );
		$string = self::mask($string,'###.###.###-##');
		return $string;

	}

	/**
	 * CNPJ format
	 *
	 * @return string
	 */
	public static function cnpj( $string ){
        $string = self::normalize_document($string);
        if (!preg_match('/\A[A-Z0-9]{12}[0-9]{2}\z/', $string)) { return; }
        return self::mask($string, '##.###.###/####-##');
    }

	/**
	 * Zipcode format
	 *
	 * @return string
	 */
	public static function cep( $string ){

		if (!$string) return;
		$string = self::clear( $string );
		$string = self::mask($string,'#####-###');
		return $string;

	}

	/**
	 * Clear string
	 *
	 * @return string
	 */
	public static function clear( $string ) {
        $string = is_scalar($string) ? (string) $string : '';


		$string = str_replace( array(',', '-', '!', '.', '/', '?', '(', ')', ' ', '$', 'R$', '€'), '', $string );
		return $string;

	}

	/**
	 * Mask string
	 *
	 * @return string
	 */
	public static function mask($val, $mask) {
        $val = is_scalar($val) ? (string) $val : '';


		$maskared = '';
		$k = 0;
		for ($i = 0; $i<=strlen($mask)-1; $i++) {
			if ($mask[$i] == '#'){
				if (isset($val[$k]))
						$maskared .= $val[$k++];
			} else {
				if(isset($mask[$i])) $maskared .= $mask[$i];
			}
		}
		return $maskared;

	}

	/**
	 * Validate CPF
	 *
	 * @return string
	 */
	public static function is_cpf( $cpf ) {
        if (!is_scalar($cpf)) { return false; }
        $cpf = (string) $cpf;


		$cpf = preg_replace( '/[^0-9]/', '', $cpf );

		if ( 11 != strlen( $cpf ) || preg_match( '/^([0-9])\1+$/', $cpf ) ) {
			return false;
		}

		$digit = substr( $cpf, 0, 9 );

		for ( $j = 10; $j <= 11; $j++ ) {
			$sum = 0;

			for( $i = 0; $i< $j-1; $i++ ) {
				$sum += ( $j - $i ) * ( (int) $digit[ $i ] );
			}

			$summod11 = $sum % 11;
			$digit[ $j - 1 ] = $summod11 < 2 ? 0 : 11 - $summod11;
		}

		return $digit[9] == ( (int) $cpf[9] ) && $digit[10] == ( (int) $cpf[10] );

	}

	/**
	 * Validate CNPJ
	 *
	 * @return string
	 */
	public static function is_cnpj( $cnpj ) {
        return WooCommerceNFe_TaxId::is_cnpj($cnpj);
    }

	/**
	 * Format number
	 *
	 * @return string
	**/
	public static function format_number( $string ) {

		return str_replace( array( '.', '-', '/' ), '', is_scalar( $string ) ? (string) $string : '' );

	}

	/**
	 * Format birthdate
	 *
	 * @return string
	**/
	public static function get_formatted_birthdate( $date, $server ) {

		// The birthdate is free text from checkout (e.g. "01/02" or an array).
		$birthdate = explode( '/', is_scalar( $date ) ? (string) $date : '' );

		if ( isset( $birthdate[1], $birthdate[2] ) && ! empty( $birthdate[1] ) ) {
			return $server->format_datetime( $birthdate[1] . '/' . $birthdate[0] . '/' . $birthdate[2] );
		}

		return '';

	}

}
