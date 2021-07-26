<?php


namespace Fanout\Grip\Tests\Unit;


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
}
