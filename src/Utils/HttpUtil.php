<?php

namespace Fanout\Grip\Utils;

class HttpUtil {

    static function flatten_headers( array $headers ) {

        if( count( $headers ) === 0 ) {
            return false;
        }

        return join( ',', $headers );

    }

}
