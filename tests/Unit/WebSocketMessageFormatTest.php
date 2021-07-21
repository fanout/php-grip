<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Data\WebSockets\WebSocketMessageFormat;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class WebSocketMessageFormatTest extends TestCase {

    /**
     * @test
     */
    function shouldConstructWithString() {
        $format = new WebSocketMessageFormat( 'hello' );
        $this->assertEquals( 'hello', $format->content );
        $this->assertEquals( WebSocketMessageFormat::WEBSOCKET_MESSAGE_BODY_FORMAT_STRING, $format->content_format );
        $this->assertFalse( $format->close );
        $this->assertNull( $format->code );
    }

    /**
     * @test
     */
    function shouldConstructWithPack() {
        $format = new WebSocketMessageFormat( pack( 'C*', 0x41, 0x42, 0x43 ), WebSocketMessageFormat::WEBSOCKET_MESSAGE_BODY_FORMAT_PACK );
        $this->assertEquals( pack( 'C*', 0x41, 0x42, 0x43 ), $format->content );
        $this->assertEquals( WebSocketMessageFormat::WEBSOCKET_MESSAGE_BODY_FORMAT_PACK, $format->content_format );
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
        new WebSocketMessageFormat( 'hello', WebSocketMessageFormat::WEBSOCKET_MESSAGE_BODY_FORMAT_STRING, true );
    }

    /**
     * @test
     */
    function shouldConstructWithClose() {
        $format = new WebSocketMessageFormat( null, WebSocketMessageFormat::WEBSOCKET_MESSAGE_BODY_FORMAT_STRING, true );
        $this->assertNull( $format->content );
        $this->assertEquals( WebSocketMessageFormat::WEBSOCKET_MESSAGE_BODY_FORMAT_STRING, $format->content_format );
        $this->assertTrue( $format->close );
        $this->assertNull( $format->code );
    }

    /**
     * @test
     */
    function shouldConstructWithCloseAndCode() {
        $format = new WebSocketMessageFormat( null, WebSocketMessageFormat::WEBSOCKET_MESSAGE_BODY_FORMAT_STRING, true, 1009 );
        $this->assertEquals( null, $format->content );
        $this->assertEquals( WebSocketMessageFormat::WEBSOCKET_MESSAGE_BODY_FORMAT_STRING, $format->content_format );
        $this->assertTrue( $format->close );
        $this->assertEquals( 1009, $format->code );
    }

    /**
     * @test
     */
    function shoulNotConstructWithCodeUnlessCloseMessage() {
        $this->expectException( InvalidArgumentException::class );
        new WebSocketMessageFormat( null, WebSocketMessageFormat::WEBSOCKET_MESSAGE_BODY_FORMAT_STRING, false, 1009 );
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
    function shouldExportWithString() {
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
    function shouldExportWithPack() {
        $format = new WebSocketMessageFormat( pack( 'C*', 0x41, 0x42, 0x43 ), WebSocketMessageFormat::WEBSOCKET_MESSAGE_BODY_FORMAT_PACK );

        $export = $format->export();
        $this->assertEquals( 'QUJD', $export['content-bin'] );
        $this->assertArrayNotHasKey( 'content', $export );

        $this->assertArrayNotHasKey( 'close', $export );
        $this->assertArrayNotHasKey( 'code', $export );
    }

    /**
     * @test
     */
    function shouldExportWithClose() {
        $format = new WebSocketMessageFormat( null, WebSocketMessageFormat::WEBSOCKET_MESSAGE_BODY_FORMAT_STRING, true );

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
        $format = new WebSocketMessageFormat( null, WebSocketMessageFormat::WEBSOCKET_MESSAGE_BODY_FORMAT_STRING, true, 1009 );

        $export = $format->export();
        $this->assertEquals( 'close', $export[ 'action' ] );
        $this->assertEquals( 1009, $export[ 'code' ] );

        $this->assertArrayNotHasKey( 'content', $export );
        $this->assertArrayNotHasKey( 'content-bin', $export );
    }

}
