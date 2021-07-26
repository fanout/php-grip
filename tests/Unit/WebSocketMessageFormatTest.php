<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Data\WebSockets\WebSocketMessageFormat;
use Fanout\Grip\Tests\Utils\TestStreamData;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class WebSocketMessageFormatTest extends TestCase {

    /**
     * @test
     */
    function shouldConstructString() {
        $format = new WebSocketMessageFormat( 'hello' );
        $this->assertEquals( 'hello', $format->content );
        $this->assertFalse( $format->close );
        $this->assertNull( $format->code );
    }

    /**
     * @test
     */
    function shouldConstructStream() {
        $format = new WebSocketMessageFormat( TestStreamData::$sample_stream );
        $this->assertEquals( TestStreamData::$sample_stream, $format->content );
        $this->assertFalse( $format->close );
        $this->assertNull( $format->code );
    }

    /**
     * @test
     */
    function shouldConstructConverted() {
        $format = new WebSocketMessageFormat( TestStreamData::get_sample_iterator() );

        $this->assertInstanceOf( StreamInterface::class, $format->content );
        $this->assertEquals( TestStreamData::$sample_iterator_values_to_string, $format->content->getContents() );

        $this->assertFalse( $format->close );
        $this->assertNull( $format->code );
    }

    /**
     * @test
     */
    function shoulNotConstructWithEmptyContent() {
        $this->expectException( InvalidArgumentException::class );
        new WebSocketMessageFormat( null );
    }

    /**
     * @test
     */
    function shouldNotConstructWithStringAndClose() {
        $this->expectException( InvalidArgumentException::class );
        new WebSocketMessageFormat( 'hello', true );
    }

    /**
     * @test
     */
    function shouldConstructWithClose() {
        $format = new WebSocketMessageFormat( null, true );
        $this->assertNull( $format->content );
        $this->assertTrue( $format->close );
        $this->assertNull( $format->code );
    }

    /**
     * @test
     */
    function shouldConstructWithCloseAndCode() {
        $format = new WebSocketMessageFormat( null, true, 1009 );
        $this->assertEquals( null, $format->content );
        $this->assertTrue( $format->close );
        $this->assertEquals( 1009, $format->code );
    }

    /**
     * @test
     */
    function shoulNotConstructWithCodeUnlessCloseMessage() {
        $this->expectException( InvalidArgumentException::class );
        new WebSocketMessageFormat( null, false, 1009 );
    }

    /**
     * @test
     */
    function shouldReportName() {
        $format = new WebSocketMessageFormat( 'hello' );
        $this->assertEquals( 'ws-message', $format->name() );
    }

    /**
     * @test
     */
    function shouldExportString() {
        $format = new WebSocketMessageFormat( 'hello' );

        $export = $format->export();
        $this->assertEquals( 'hello', $export['content'] );
        $this->assertArrayNotHasKey( 'content-bin', $export );

        $this->assertArrayNotHasKey( 'close', $export );
        $this->assertArrayNotHasKey( 'code', $export );
    }

    /**
     * @test
     */
    function shouldExportStream() {
        $format = new WebSocketMessageFormat( TestStreamData::$sample_stream );

        $export = $format->export();
        $this->assertEquals( TestStreamData::$sample_stream_value_to_base64, $export['content-bin'] );
        $this->assertArrayNotHasKey( 'content', $export );

        $this->assertArrayNotHasKey( 'close', $export );
        $this->assertArrayNotHasKey( 'code', $export );
    }

    /**
     * @test
     */
    function shouldExportConverted() {
        $format = new WebSocketMessageFormat( TestStreamData::get_sample_iterator() );

        $export = $format->export();
        $this->assertEquals( TestStreamData::$sample_iterator_values_to_base64, $export['content-bin'] );
        $this->assertArrayNotHasKey( 'content', $export );

        $this->assertArrayNotHasKey( 'close', $export );
        $this->assertArrayNotHasKey( 'code', $export );
    }

    /**
     * @test
     */
    function shouldExportWithClose() {
        $format = new WebSocketMessageFormat( null, true );

        $export = $format->export();
        $this->assertEquals( 'close', $export[ 'action' ] );
        $this->assertArrayNotHasKey( 'code', $export );

        $this->assertArrayNotHasKey( 'content', $export );
        $this->assertArrayNotHasKey( 'content-bin', $export );
    }

    /**
     * @test
     */
    function shouldExportWithCloseAndCode() {
        $format = new WebSocketMessageFormat( null, true, 1009 );

        $export = $format->export();
        $this->assertEquals( 'close', $export[ 'action' ] );
        $this->assertEquals( 1009, $export[ 'code' ] );

        $this->assertArrayNotHasKey( 'content', $export );
        $this->assertArrayNotHasKey( 'content-bin', $export );
    }

}
