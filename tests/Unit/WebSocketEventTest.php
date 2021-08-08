<?php


namespace Fanout\Grip\Tests\Unit;


use Error;
use Fanout\Grip\Data\WebSockets\WebSocketEvent;
use Fanout\Grip\Tests\Utils\TestStreamData;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class WebSocketEventTest extends TestCase {
    /**
     * @test
     */
    function shouldConstructNoContent() {
        $event = new WebSocketEvent( 'type' );
        $this->assertEquals( 'type', $event->type );
        $this->assertNull( $event->content );
    }

    /**
     * @test
     */
    function shouldConstructStringContent() {
        $event = new WebSocketEvent( 'type', 'content' );
        $this->assertEquals( 'type', $event->type );
        $this->assertEquals( 'content', $event->content );
    }

    /**
     * @test
     */
    function shouldConstructStreamContent() {
        $event = new WebSocketEvent( 'type', TestStreamData::$sample_stream );
        $this->assertEquals( 'type', $event->type );
        $this->assertEquals( TestStreamData::$sample_stream, $event->content );
    }

    /**
     * @test
     */
    function shouldConstructStreamConverted() {
        $event = new WebSocketEvent( 'type', TestStreamData::get_sample_iterator() );
        $this->assertEquals( 'type', $event->type );
        $this->assertInstanceOf( StreamInterface::class, $event->content );
        $this->assertEquals( TestStreamData::$sample_iterator_values_to_string, $event->content->getContents() );
    }

    /**
     * @test
     */
    function shouldReportType() {
        $event = new WebSocketEvent( 'type' );
        $this->assertEquals( 'type', $event->get_type() );
    }

    /**
     * @test
     */
    function shouldReturnContent() {
        $event = new WebSocketEvent( 'type' );
        $this->assertNull( $event->get_content() );

        $event = new WebSocketEvent( 'type', 'content' );
        $this->assertEquals( 'content', $event->get_content() );

        $event = new WebSocketEvent( 'type', TestStreamData::$sample_stream );
        $this->assertEquals( TestStreamData::$sample_stream, $event->get_content() );

        $event = new WebSocketEvent( 'type', TestStreamData::get_sample_iterator() );
        $this->assertInstanceOf( StreamInterface::class, $event->get_content() );
        $this->assertEquals( TestStreamData::$sample_iterator_values_to_string, $event->get_content()->getContents() );
    }

    /**
     * @test
     */
    function shouldEncodeEvents() {
        $events = [
            new WebSocketEvent('TEXT', 'Hello'),
            new WebSocketEvent('TEXT', ''),
            new WebSocketEvent('TEXT', null),
        ];

        $events_encoded = WebSocketEvent::encode_events( $events );
        $this->assertSame(
            "TEXT 5\r\nHello\r\nTEXT 0\r\n\r\nTEXT\r\n",
            $events_encoded->getContents()
        );
    }

    /**
     * @test
     */
    function shouldEncodeOpenEvent() {
        $events = [
            new WebSocketEvent('OPEN'),
        ];

        $events_encoded = WebSocketEvent::encode_events( $events );
        $this->assertSame(
            "OPEN\r\n",
            $events_encoded->getContents()
        );
    }

    /**
     * @test
     */
    function shouldEncodeBinaryEvent() {
        $events = [
            new WebSocketEvent('BINARY', TestStreamData::get_sample_iterator()),
            new WebSocketEvent('BINARY', ''),
            new WebSocketEvent('BINARY', null),
        ];

        $events_encoded = WebSocketEvent::encode_events( $events );
        $this->assertSame(
            "BINARY " . strlen(TestStreamData::$sample_iterator_values_to_string) . "\r\n" . TestStreamData::$sample_iterator_values_to_string . "\r\nBINARY 0\r\n\r\nBINARY\r\n",
            $events_encoded->getContents()
        );

    }

    /**
     * @test
     */
    function shouldDecodeEventWithoutContent() {
        $body = "OPEN\r\n";
        $events = WebSocketEvent::decode_events( $body );
        
        $this->assertCount( 1, $events );
        $this->assertSame( 'OPEN', $events[0]->get_type() );
        $this->assertNull( $events[0]->get_content() );
    }

    /**
     * @test
     */
    function shouldDecodeEventWithLength() {
        $body = "TEXT 5\r\nHello\r\n";
        $events = WebSocketEvent::decode_events( $body );

        $this->assertCount( 1, $events );
        $this->assertSame( 'TEXT', $events[0]->get_type() );
        $this->assertSame( 'Hello', $events[0]->get_content() );
    }

    /**
     * @test
     */
    function shouldDecodeMultipleEvents() {
        $body = "OPEN\r\nTEXT 5\r\nHello\r\nTEXT 0\r\n\r\nCLOSE\r\nTEXT\r\nCLOSE\r\n";
        $events = WebSocketEvent::decode_events( $body );

        $this->assertCount( 6, $events );

        $this->assertSame( 'OPEN', $events[0]->get_type() );
        $this->assertNull( $events[0]->get_content() );

        $this->assertSame( 'TEXT', $events[1]->get_type() );
        $this->assertSame( 'Hello', $events[1]->get_content() );

        $this->assertSame( 'TEXT', $events[2]->get_type() );
        $this->assertSame( '', $events[2]->get_content() );

        $this->assertSame( 'CLOSE', $events[3]->get_type() );
        $this->assertNull( $events[3]->get_content() );

        $this->assertSame( 'TEXT', $events[4]->get_type() );
        $this->assertNull( $events[4]->get_content() );

        $this->assertSame( 'CLOSE', $events[5]->get_type() );
        $this->assertNull( $events[5]->get_content() );
    }

    /**
     * @test
     */
    function shouldDecodeEventsThrow1() {

        $this->expectException( Error::class );

        $body = "TEXT 5\r\n";
        WebSocketEvent::decode_events( $body );

    }

    /**
     * @test
     */
    function shouldDecodeEventsThrow2() {

        $this->expectException( Error::class );

        $body = "OPEN\r\nTEXT";
        WebSocketEvent::decode_events( $body );

    }
}
