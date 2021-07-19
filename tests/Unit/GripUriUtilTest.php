<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Utils\GripUriUtil;
use PHPUnit\Framework\TestCase;

class GripUriUtilTest extends TestCase {

    /**
     * @test
     */
    function shouldParseUriSimple() {

        $uri = 'http://api.fanout.io/realm/realm';

        $parsed = GripUriUtil::parse( $uri );

        $this->assertEquals( 'http://api.fanout.io/realm/realm', $parsed[ 'control_uri' ] );

    }

    /**
     * @test
     */
    function shouldParseUriHttps() {

        $uri = 'https://api.fanout.io/realm/realm';

        $parsed = GripUriUtil::parse( $uri );

        $this->assertEquals( 'https://api.fanout.io/realm/realm', $parsed[ 'control_uri' ] );

    }

    /**
     * @test
     */
    function shouldParseUriSimpleWithTrailingSlash() {

        $uri = 'http://api.fanout.io/realm/realm/';

        $parsed = GripUriUtil::parse( $uri );

        $this->assertEquals( 'http://api.fanout.io/realm/realm', $parsed[ 'control_uri' ] );

    }

    /**
     * @test
     */
    function shouldParseUriWithIss() {

        $uri = 'http://api.fanout.io/realm/realm?iss=realm';

        $parsed = GripUriUtil::parse( $uri );

        $this->assertEquals( 'http://api.fanout.io/realm/realm', $parsed[ 'control_uri' ] );
        $this->assertEquals( 'realm', $parsed[ 'control_iss' ] );

    }

    /**
     * @test
     */
    function shouldParseUriWithKey() {

        $uri = 'http://api.fanout.io/realm/realm?key=base64:geag%2B21321=';

        $parsed = GripUriUtil::parse( $uri );

        $this->assertEquals( 'http://api.fanout.io/realm/realm', $parsed[ 'control_uri' ] );
        $this->assertEquals( base64_decode( 'geag+21321=' ), $parsed[ 'key' ] );

    }

    /**
     * @test
     */
    function shouldParseUriWithKeyWithSpace() {

        $uri = 'http://api.fanout.io/realm/realm?key=base64:geag+21321=';

        $parsed = GripUriUtil::parse( $uri );

        $this->assertEquals( 'http://api.fanout.io/realm/realm', $parsed[ 'control_uri' ] );
        $this->assertEquals( base64_decode( 'geag+21321=' ), $parsed[ 'key' ] );

    }

    /**
     * @test
     */
    function shouldParseUriWithIssAndKey() {

        $uri = 'http://api.fanout.io/realm/realm?iss=realm&key=base64:geag121321=';

        $parsed = GripUriUtil::parse( $uri );

        $this->assertEquals( 'http://api.fanout.io/realm/realm', $parsed[ 'control_uri' ] );
        $this->assertEquals( 'realm', $parsed[ 'control_iss' ] );
        $this->assertEquals( base64_decode( 'geag121321=' ), $parsed[ 'key' ] );

    }

    /**
     * @test
     */
    function shouldParseUriWithAdditionalParams() {

        $uri = 'http://api.fanout.io/realm/realm?iss=realm&key=base64:geag121321=&param1=value1&param2=value2';

        $parsed = GripUriUtil::parse( $uri );

        $this->assertEquals( 'http://api.fanout.io/realm/realm?param1=value1&param2=value2', $parsed[ 'control_uri' ] );
        $this->assertEquals( 'realm', $parsed[ 'control_iss' ] );
        $this->assertEquals( base64_decode( 'geag121321=' ), $parsed[ 'key' ] );

    }

    /**
     * @test
     */
    function shouldParseUriWithDefaultPort() {

        $uri = 'http://api.fanout.io:80/realm/realm/';

        $parsed = GripUriUtil::parse( $uri );

        $this->assertEquals( 'http://api.fanout.io:80/realm/realm', $parsed[ 'control_uri' ] );

    }

    /**
     * @test
     */
    function shouldParseUriWithPort() {

        $uri = 'http://api.fanout.io:8080/realm/realm/';

        $parsed = GripUriUtil::parse( $uri );

        $this->assertEquals( 'http://api.fanout.io:8080/realm/realm', $parsed[ 'control_uri' ] );

    }

}
