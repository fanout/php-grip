<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Data\Http\HttpStreamFormat;
use Fanout\Grip\Tests\Utils\TestStreamData;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class HttpStreamFormatTest extends TestCase {

    /**
     * @test
     */
    function shouldConstructString() {
        $format = new HttpStreamFormat( 'content' );

        $this->assertEquals( 'content', $format->content );
        $this->assertFalse( $format->close );
    }

    /**
     * @test
     */
    function shouldConstructStream() {
        $format = new HttpStreamFormat( TestStreamData::$sample_stream );

        $this->assertEquals( TestStreamData::$sample_stream, $format->content );
        $this->assertFalse( $format->close );
    }

    /**
     * @test
     */
    function shouldConstructConverted() {
        $format = new HttpStreamFormat( TestStreamData::get_sample_iterator() );

        $this->assertInstanceOf( StreamInterface::class, $format->content );
        $this->assertEquals( TestStreamData::$sample_iterator_values_to_string, $format->content->getContents() );

        $this->assertFalse( $format->close );
    }

    /**
     * @test
     */
    function shouldConstructClose() {
        $format = new HttpStreamFormat( 'content', true );

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
    function shouldExportString() {
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
    function shouldExportStream() {
        $format = new HttpStreamFormat( TestStreamData::$sample_stream );

        $export = $format->export();

        $this->assertIsArray( $export );
        $this->assertArrayNotHasKey( 'action', $export );
        $this->assertArrayNotHasKey( 'content', $export );
        $this->assertEquals( TestStreamData::$sample_stream_value_to_base64, $export[ 'content-bin' ] );
    }

    /**
     * @test
     */
    function shouldExportConverted() {
        $format = new HttpStreamFormat( TestStreamData::get_sample_iterator() );

        $export = $format->export();

        $this->assertIsArray( $export );
        $this->assertArrayNotHasKey( 'action', $export );
        $this->assertArrayNotHasKey( 'content', $export );
        $this->assertEquals( TestStreamData::$sample_iterator_values_to_base64, $export[ 'content-bin' ] );
    }

    /**
     * @test
     */
    function shouldExportClose() {
        $format = new HttpStreamFormat( null, true );

        $export = $format->export();

        $this->assertIsArray( $export );
        $this->assertEquals( 'close', $export[ 'action' ] );
        $this->assertEquals( '', $export[ 'content' ] );
    }

}
