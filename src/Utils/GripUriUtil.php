<?php


namespace Fanout\Grip\Utils;


class GripUriUtil {
    static function parse( string $uri ): array {

        $parsed_uri = parse_url( $uri );

        $control_uri = $parsed_uri[ 'scheme' ] . '://' . $parsed_uri[ 'host' ];

        $port = $parsed_uri[ 'port' ] ?? false;
        if( !empty( $port ) ) {
            $control_uri .= ':' . $port;
        }

        $path = $parsed_uri[ 'path' ];
        if( StringUtil::string_ends_with( $path, '/' ) ) {
            $path = substr( $path, 0, strlen($path) - 1 );
        }
        $control_uri .= $path;

        $query = $parsed_uri[ 'query' ] ?? false;
        parse_str( $query, $parsed_query );

        $control_iss = $parsed_query[ 'iss' ] ?? false;
        if( !empty( $control_iss ) ) {
            unset( $parsed_query[ 'iss' ] );
        }

        $key = $parsed_query[ 'key' ] ?? false;
        if( !empty( $key ) ) {
            unset( $parsed_query[ 'key' ] );
        }
        if( !empty( $key ) && StringUtil::string_starts_with( $key, 'base64:' ) ) {
            $key = substr( $key, 7 );
            // When the key contains a '+' character, if the URL is built carelessly
            // and this segment of the URL contained '+' directly instead of properly
            // being URL-encoded as %2B, then they would have turned into spaces at
            // this point. Turn them back into pluses before decoding the key from base64.
            $key = str_replace( ' ', '+', $key );
            $key = base64_decode( $key );
        }

        $verify_iss = $parsed_query[ 'verify-iss' ] ?? false;
        if( !empty( $verify_iss ) ) {
            unset ( $parsed_query[ 'verify-iss' ] );
        }

        $verify_key = $parsed_query[ 'verify-key' ] ?? false;
        if( !empty( $verify_key ) ) {
            unset( $parsed_query[ 'verify-key' ] );
        }

        if( !empty( $verify_key ) && StringUtil::string_starts_with( $verify_key, 'base64:' ) ) {
            $verify_key = substr( $verify_key, 7 );
            // When the key contains a '+' character, if the URL is built carelessly
            // and this segment of the URL contained '+' directly instead of properly
            // being URL-encoded as %2B, then they would have turned into spaces at
            // this point. Turn them back into pluses before decoding the key from base64.
            $verify_key = str_replace( ' ', '+', $verify_key );
            $verify_key = base64_decode( $verify_key );
        }

        $rebuilt_query = http_build_query( $parsed_query );

        if( !empty( $rebuilt_query ) ) {
            $control_uri .= '?' . $rebuilt_query;
        }

        $out = [
            'control_uri' => $control_uri,
        ];
        if( !empty( $control_iss ) ) {
            $out[ 'control_iss' ] = $control_iss;
        }
        if( !empty( $key ) ) {
            $out[ 'key' ] = $key;
        }
        if( !empty( $verify_key ) ) {
            $out[ 'verify_key' ] = $verify_key;
        } 
        if( !empty( $verify_iss ) ) {
            $out[ 'verify_iss' ] = $verify_iss;
        }

        return $out;


    }
}
