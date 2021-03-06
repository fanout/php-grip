<?php

namespace Fanout\Grip\Utils;

use Fanout\Grip\Errors\CStringEncodeInvalidInputError;

class StringUtil {

    static function encode_cstring( $input ): string {

        // This is a cstring, so it's likely to be just
        // fine as iteration as bytes

        $output = '';
        foreach( str_split( $input ) as $ch ) {
            if ($ch === "\\") {
                $output .= "\\\\";
            } else if ($ch === "\r") {
                $output .= "\\r";
            } else if ($ch === "\n") {
                $output .= "\\n";
            } else if ($ch === "\t") {
                $output .= "\\t";
            } else if( ord($ch) < 0x20 ) {
                throw new CStringEncodeInvalidInputError( 'Can\'t encode' );
            } else {
                $output .= $ch;
            }
        }

        return $output;

    }

    static function escape_quotes( $input ): string {

        $output = '';
        foreach( str_split( $input ) as $ch ) {
            if ($ch === "\"") {
                $output .= "\\\"";
            } else {
                $output .= $ch;
            }
        }

        return $output;

    }

    static function string_starts_with( string $haystack, string $needle ): bool {

        if ($needle === '') {
            return true;
        }

        return substr( $haystack, 0, strlen($needle) ) === $needle;

    }

    static function string_ends_with( string $haystack, string $needle ): bool {

        if ($needle === '') {
            return true;
        }

        return substr( $haystack, -strlen($needle) ) === $needle;

    }

}
