<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Data\Http\HttpStreamFormat;
use PHPUnit\Framework\TestCase;

class HttpStreamFormatTest extends TestCase {

    /**
     * @test
     */
    function shouldConstructSimple() {
        $format = new HttpStreamFormat( 'content' );

        $this->assertEquals( 'content', $format->content );
        $this->assertEquals( HttpStreamFormat::HTTP_RESPONSE_BODY_FORMAT_STRING, $format->content_format );
        $this->assertFalse( $format->close );
    }

    /**
     * @test
     */
    function shouldConstructPack() {
        $format = new HttpStreamFormat( pack( 'C*', 0x41, 0x42, 0x43 ), HttpStreamFormat::HTTP_RESPONSE_BODY_FORMAT_PACK );

        $this->assertEquals( pack( 'C*', 0x41, 0x42, 0x43 ), $format->content );
        $this->assertEquals( HttpStreamFormat::HTTP_RESPONSE_BODY_FORMAT_PACK, $format->content_format );
        $this->assertFalse( $format->close );
    }

    /**
     * @test
     */
    function shouldConstructClose() {
        $format = new HttpStreamFormat( 'content', HttpStreamFormat::HTTP_RESPONSE_BODY_FORMAT_STRING, true );

        $this->assertEquals( 'content', $format->content );
        $this->assertTrue( $format->close );
    }

    /**
     * @test
     */
    function shouldReportName() {
        $format = new HttpStreamFormat( 'content' );

        $this->assertEquals( 'http-stream', $format->name() );
    }

    /**
     * @test
     */
    function shouldExportSimple() {
        $format = new HttpStreamFormat( 'message' );

        $export = $format->export();

        $this->assertIsArray( $export );
        $this->assertArrayNotHasKey( 'action', $export );
        $this->assertEquals( 'message', $export[ 'content' ] );
        $this->assertArrayNotHasKey( 'content-bin', $export );
    }

    /**
     * @test
     */
    function shouldExportPack() {
        $format = new HttpStreamFormat( pack( 'C*', 0x41, 0x42, 0x43 ), HttpStreamFormat::HTTP_RESPONSE_BODY_FORMAT_PACK );

        $export = $format->export();

        $this->assertIsArray( $export );
        $this->assertArrayNotHasKey( 'action', $export );
        $this->assertArrayNotHasKey( 'content', $export );
        $this->assertEquals( 'QUJD', $export[ 'content-bin' ] );
    }

    /**
     * @test
     */
    function shouldExportClose() {
        $format = new HttpStreamFormat( null, null, true );

        $export = $format->export();

        $this->assertIsArray( $export );
        $this->assertEquals( 'close', $export[ 'action' ] );
        $this->assertEquals( '', $export[ 'content' ] );
    }

}
