<?php

namespace Fanout\Grip\Tests\Unit;

use Fanout\Grip\Utils\HttpUtil;
use PHPUnit\Framework\TestCase;

class HttpUtilTest extends TestCase {

    /** @test */
    function should_get_single_header_values() {

        $headers = [ 'foo'  ];
        $value = HttpUtil::flatten_headers( $headers );
        $this->assertSame( 'foo', $value );

    }

    /** @test */
    function should_join_header_values() {

        $headers = [ 'foo', 'bar' ];
        $value = HttpUtil::flatten_headers( $headers );
        $this->assertSame( 'foo,bar', $value );

    }

}
