<?php


namespace Fanout\Grip\Utils;


class ArrayUtil {
    static function is_numeric_array( $data ) {
        if ( ! is_array( $data ) ) {
            return false;
        }

        $keys        = array_keys( $data );
        $string_keys = array_filter( $keys, 'is_string' );

        return count( $string_keys ) === 0;
    }
}
