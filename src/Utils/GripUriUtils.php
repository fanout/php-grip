<?php


namespace Fanout\Grip\Utils;


class GripUriUtils {
    static function parse( string $uri ) {

        $parsed_uri = parse_url( $uri );

        $control_uri = $parsed_uri[ 'scheme' ] . '://' . $parsed_uri[ 'host' ];

        $port = $parsed_uri[ 'port' ] ?? false;
        if( !empty( $port ) ) {
            $control_uri .= ':' . $port;
        }

        $path = $parsed_uri[ 'path' ];
        if( substr( $path, strlen($path) - 1 ) === '/' ) {
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
        if( !empty( $key ) && substr( $key, 0, 7 ) === 'base64:' ) {
            $key = substr( $key, 7 );
            // When the key contains a '+' character, if the URL is built carelessly
            // and this segment of the URL contained '+' directly instead of properly
            // being URL-encoded as %2B, then they would have turned into spaces at
            // this point. Turn them back into pluses before decoding the key from base64.
            $key = str_replace( ' ', '+', $key );
            $key = base64_decode( $key );
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

        return $out;


    }
}
