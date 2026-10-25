<?php
/** Local binary streams and bounded WordPress HTTP/file operations for the PDF adapter. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once dirname( __DIR__ ) . '/class-security.php';
class WooCommerceNFe_PDF_IO {
    public static function open( $path, $mode ) {
        if ( ! is_string( $path ) || strpos( $path, "\0" ) !== false || ! is_string( $mode ) ) { return false; }
        if ( strpos( $path, 'file://' ) === 0 ) { $path = substr( $path, 7 ); }
        if ( strpos( $path, '://' ) !== false || ! preg_match( '/^[rwa][b+]*$/D', $mode ) ) { return false; }
        if ( $mode[0] === 'r' && ! is_readable( $path ) ) { return false; }
        // WP_Filesystem exposes whole-file operations, not seekable binary streams. Failures are handled by the adapter.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Local streams only; required for PDF/PNG random access.
        $stream = @fopen( $path, $mode );
        if ( $stream && $mode[0] !== 'r' && ! WooCommerceNFe_Security::filesystem()->chmod( $path, 0600 ) ) { self::close( $stream ); return false; }
        return $stream;
    }
    public static function read( $stream, $length ) {
        if ( ! is_resource( $stream ) || $length < 0 || $length > 20 * 1024 * 1024 ) { throw new RuntimeException( 'Invalid PDF stream read.' ); }
        if ( $length === 0 ) { return ''; }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Validated binary stream; WordPress has no incremental stream API.
        $bytes = @fread( $stream, $length );
        if ( $bytes === false ) { throw new RuntimeException( 'PDF stream read failed.' ); }
        return $bytes;
    }
    public static function write( $stream, $bytes ) {
        if ( ! is_resource( $stream ) || ! is_string( $bytes ) ) { throw new RuntimeException( 'Invalid PDF stream write.' ); }
        $offset = 0; $size = strlen( $bytes );
        while ( $offset < $size ) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Seekable local output; verify partial writes instead of silently truncating documents.
            $written = @fwrite( $stream, substr( $bytes, $offset ) );
            if ( ! $written ) { throw new RuntimeException( 'PDF stream write failed.' ); }
            $offset += $written;
        }
        return $offset;
    }
    public static function close( $stream ) {
        if ( ! is_resource( $stream ) ) { return false; }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Close the resource owned by this binary stream adapter.
        if ( ! @fclose( $stream ) ) { throw new RuntimeException( 'PDF stream close failed.' ); }
        return true;
    }
    public static function contents( $path ) {
        if ( ! is_string( $path ) || $path === '' || strpos( $path, "\0" ) !== false ) { return false; }
        if ( preg_match( '#^https://#i', $path ) ) {
            $response = wp_safe_remote_get( $path, array( 'timeout' => 30, 'sslverify' => true, 'redirection' => 0, 'reject_unsafe_urls' => true, 'limit_response_size' => 10485761 ) );
            if ( is_wp_error( $response ) || (int) wp_remote_retrieve_response_code( $response ) !== 200 ) { return false; }
            $body = wp_remote_retrieve_body( $response );
            return is_string( $body ) && strlen( $body ) <= 10485760 ? $body : false;
        }
        if ( strpos( $path, 'file://' ) === 0 ) { $path = substr( $path, 7 ); }
        if ( strpos( $path, '://' ) !== false || ! is_file( $path ) || filesize( $path ) > 20 * 1024 * 1024 ) { return false; }
        return WooCommerceNFe_Security::filesystem()->get_contents( $path );
    }
    public static function url_exists( $url ) {
        if ( ! is_string( $url ) || ! preg_match( '#^https://#i', $url ) ) { return false; }
        $response = wp_safe_remote_head( $url, array( 'timeout' => 15, 'sslverify' => true, 'redirection' => 0, 'reject_unsafe_urls' => true ) );
        return ! is_wp_error( $response ) && (int) wp_remote_retrieve_response_code( $response ) === 200;
    }
    public static function output_pdf( $bytes ) {
        if ( ! is_string( $bytes ) || strpos( $bytes, '%PDF-' ) !== 0 || strpos( $bytes, '%%EOF' ) === false ) { throw new RuntimeException( 'Invalid PDF output.' ); }
        if ( ! headers_sent() ) { header( 'Content-Type: application/pdf' ); header( 'X-Content-Type-Options: nosniff' ); }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Validated PDF bytes; escaping corrupts the binary document.
        echo $bytes;
    }
    public static function output_png( $bytes ) {
        if ( ! is_string( $bytes ) || substr( $bytes, 0, 8 ) !== "\x89PNG\r\n\x1a\n" ) { throw new RuntimeException( 'Invalid PNG output.' ); }
        if ( ! headers_sent() ) { header( 'Content-Type: image/png' ); header( 'X-Content-Type-Options: nosniff' ); }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Validated PNG bytes, never HTML.
        echo $bytes;
    }
    public static function output_svg( $svg ) {
        if ( ! is_string( $svg ) ) { throw new RuntimeException( 'Invalid SVG output.' ); }
        $attributes = array_fill_keys( array( 'xmlns', 'version', 'width', 'height', 'viewbox', 'x', 'y', 'fill', 'stroke', 'stroke-width', 'shape-rendering' ), true );
        if ( ! headers_sent() ) { header( 'Content-Type: image/svg+xml' ); header( 'X-Content-Type-Options: nosniff' ); }
        echo wp_kses( $svg, array( 'svg' => $attributes, 'g' => $attributes, 'rect' => $attributes, 'desc' => array() ) );
    }
}
