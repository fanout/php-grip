<?php


namespace Fanout\Grip\Tests\Unit;


use Error;
use Fanout\Grip\Data\WebSockets\WebSocketContext;
use Fanout\Grip\Data\WebSockets\WebSocketEvent;
use Fanout\Grip\Tests\Utils\TestStreamData;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class WebSocketContextTest extends TestCase {
    /**
     * @test
     */
    function shouldOpen() {

        $ws = new WebSocketContext('conn-1', [], [new WebSocketEvent('OPEN')]);
        $this->assertEquals( 'conn-1', $ws->id );
        $this->assertTrue( $ws->is_opening() );
        $this->assertFalse( $ws->can_recv() );
        $this->assertFalse( $ws->is_accepted() );

        $out_events = $ws->get_outgoing_events();
        $this->assertCount( 0, $out_events );

        $ws->accept();
        $this->assertTrue( $ws->is_accepted() );

        $out_events = $ws->get_outgoing_events();
        $this->assertCount( 1, $out_events );

        $this->assertSame( 'OPEN', $out_events[0]->get_type() );
        $this->assertNull( $out_events[0]->get_content() );


    }

    /**
     * @test
     */
    function shouldReceiveText() {
        $ws = new WebSocketContext('conn-1', [], [
            new WebSocketEvent( 'TEXT', 'Hello' ),
            new WebSocketEvent( 'TEXT' ),
        ]);
        $this->assertFalse( $ws->is_opening() );
        $this->assertTrue( $ws->can_recv() );

        $msg = $ws->recv();
        $this->assertSame( 'Hello', $msg );
        $this->assertTrue( $ws->can_recv() );

        $msg = $ws->recv();
        $this->assertSame( '', $msg );
        $this->assertFalse( $ws->can_recv() );
    }

    /**
     * @test
     */
    function shouldReceiveBinary() {
        $ws = new WebSocketContext('conn-1', [], [
            new WebSocketEvent( 'BINARY', TestStreamData::get_sample_iterator() ),
            new WebSocketEvent( 'BINARY' ),
        ]);
        $this->assertFalse( $ws->is_opening() );
        $this->assertTrue( $ws->can_recv() );

        $msg = $ws->recv_raw();
        $this->assertInstanceOf( StreamInterface::class, $msg );
        $this->assertEquals( TestStreamData::$sample_iterator_values_to_string, $msg->getContents() );
        $this->assertTrue( $ws->can_recv() );

        $msg = $ws->recv_raw();
        $this->assertInstanceOf( StreamInterface::class, $msg );
        $this->assertSame( 0, $msg->getSize() );
        $this->assertFalse( $ws->can_recv() );
    }

    /**
     * @test
     */
    function shouldReceiveDisconnect() {

        $this->expectException( Error::class );

        $ws = new WebSocketContext('conn-1', [], [
            new WebSocketEvent( 'DISCONNECT' ),
        ]);
        $this->assertFalse( $ws->is_opening() );
        $this->assertTrue( $ws->can_recv() );

        $ws->recv_raw();

    }

    /**
     * @test
     */
    function shouldSendText() {
        $ws = new WebSocketContext('conn-1', [], []);

        $out_events = $ws->get_outgoing_events();
		$this->assertCount( 0, $out_events );

		$ws->send( Utils::streamFor( 'apple' ) );
		$ws->send( 'banana' );

		$out_events = $ws->get_outgoing_events();
		$this->assertCount( 2, $out_events );

		$this->assertSame( 'TEXT', $out_events[0]->get_type() );
		$this->assertSame( 'm:apple', $out_events[0]->get_content()->getContents() );

		$this->assertSame( 'TEXT', $out_events[1]->get_type() );
		$this->assertSame( 'm:banana', $out_events[1]->get_content()->getContents() );
    }

    /**
     * @test
     */
    function shouldSendBinary() {
        $ws = new WebSocketContext('conn-1', [], []);

        $out_events = $ws->get_outgoing_events();
		$this->assertCount( 0, $out_events);

		$ws->send_binary( Utils::streamFor( TestStreamData::get_sample_iterator() ) );

        $out_events = $ws->get_outgoing_events();
		$this->assertCount( 1, $out_events );

		$this->assertSame( 'BINARY', $out_events[0]->get_type() );
		$this->assertSame( 'm:' . TestStreamData::$sample_iterator_values_to_string, $out_events[0]->get_content()->getContents() );
    }

    /**
     * @test
     */
    function shouldSendControlRaw() {
        $ws = new WebSocketContext('conn-1', [], []);

        $out_events = $ws->get_outgoing_events();
        $this->assertCount( 0, $out_events );

		$ws->send_control( 'foo' );
		$ws->send_control( 'bar' );

        $out_events = $ws->get_outgoing_events();
        $this->assertCount( 2, $out_events );

        $this->assertSame( 'TEXT', $out_events[0]->get_type() );
        $this->assertSame( 'c:foo', $out_events[0]->get_content()->getContents() );

        $this->assertSame( 'TEXT', $out_events[1]->get_type() );
        $this->assertSame( 'c:bar', $out_events[1]->get_content()->getContents() );
    }

    /**
     * @test
     */
    function shouldSendControl() {
        $ws = new WebSocketContext('conn-1', [], []);

        $out_events = $ws->get_outgoing_events();
        $this->assertCount( 0, $out_events );

		$ws->subscribe( 'foo' );
		$ws->unsubscribe( 'bar' );
		$ws->detach();

        $out_events = $ws->get_outgoing_events();
        $this->assertCount( 3, $out_events );

        $this->assertSame( 'TEXT', $out_events[0]->get_type() );
        $c = $out_events[0]->get_content()->getContents();
        $this->assertSame( 'c:', substr( $c, 0, 2 ) );
        $o = substr( $c, 2 );
        $o = json_decode( $o, true );
        $this->assertEquals( [ 'type' => 'subscribe', 'channel' => 'foo', ], $o );

        $this->assertSame( 'TEXT', $out_events[1]->get_type() );
        $c = $out_events[1]->get_content()->getContents();
        $this->assertSame( 'c:', substr( $c, 0, 2 ) );
        $o = substr( $c, 2 );
        $o = json_decode( $o, true );
        $this->assertEquals( [ 'type' => 'unsubscribe', 'channel' => 'bar', ], $o );

        $this->assertSame( 'TEXT', $out_events[2]->get_type() );
        $c = $out_events[2]->get_content()->getContents();
        $this->assertSame( 'c:', substr( $c, 0, 2 ) );
        $o = substr( $c, 2 );
        $o = json_decode( $o, true );
        $this->assertEquals( [ 'type' => 'detach', ], $o );
    }

    /**
     * @test
     */
    function shouldSendControlWithPrefix() {
        $ws = new WebSocketContext('conn-1', [], [], 'prefix');

        $out_events = $ws->get_outgoing_events();
        $this->assertCount( 0, $out_events );
		$ws->subscribe( 'foo' );
		$ws->unsubscribe( 'bar' );

        $out_events = $ws->get_outgoing_events();
        $this->assertCount( 2, $out_events );

        $this->assertSame( 'TEXT', $out_events[0]->get_type() );
        $c = $out_events[0]->get_content()->getContents();
        $this->assertSame( 'c:', substr( $c, 0, 2 ) );
        $o = substr( $c, 2 );
        $o = json_decode( $o, true );
        $this->assertEquals( [ 'type' => 'subscribe', 'channel' => 'prefixfoo', ], $o );

        $this->assertSame( 'TEXT', $out_events[1]->get_type() );
        $c = $out_events[1]->get_content()->getContents();
        $this->assertSame( 'c:', substr( $c, 0, 2 ) );
        $o = substr( $c, 2 );
        $o = json_decode( $o, true );
        $this->assertEquals( [ 'type' => 'unsubscribe', 'channel' => 'prefixbar', ], $o );
    }

    /**
     * @test
     */
    function shouldReceiveClose() {
        $ws = new WebSocketContext('conn-1', [], [
            new WebSocketEvent( 'CLOSE' ),
        ]);
        $this->assertFalse( $ws->is_opening() );
        $this->assertTrue( $ws->can_recv() );

        $msg = $ws->recv_raw();
        $this->assertNull($msg);
        $this->assertSame( 0, $ws->get_close_code() );
        $this->assertFalse( $ws->can_recv() );
    }

    /**
     * @test
     */
    function shouldReceiveCloseWithCode() {
        $ws = new WebSocketContext('conn-1', [], [
            new WebSocketEvent( 'CLOSE', Utils::streamFor( pack( 'n', 100 ) ) ),
        ]);
        $this->assertFalse( $ws->is_opening() );
        $this->assertTrue( $ws->can_recv() );

        $msg = $ws->recv_raw();
        $this->assertNull($msg);
        $this->assertSame( 100, $ws->get_close_code() );
        $this->assertFalse( $ws->can_recv() );
    }

    /**
     * @test
     */
    function shouldCloseWithCode() {
        $ws = new WebSocketContext('conn-1', [], []);
        $this->assertFalse( $ws->is_opening() );
        $this->assertFalse( $ws->can_recv() );
        $this->assertFalse( $ws->is_closed() );

        $out_events = $ws->get_outgoing_events();
        $this->assertCount( 0, $out_events );

        $ws->close( 100 );
        $this->assertTrue( $ws->is_closed() );

        $out_events = $ws->get_outgoing_events();
        $this->assertCount( 1, $out_events );

        $this->assertSame( 'CLOSE', $out_events[0]->get_type() );
        $this->assertSame( 100, unpack( 'n', $out_events[0]->get_content() )[1] );
    }

    /**
     * @test
     */
    function shouldDisconnect() {
        $ws = new WebSocketContext('conn-1', [], []);

        $out_events = $ws->get_outgoing_events();
        $this->assertCount( 0, $out_events );
        $ws->disconnect();

        $out_events = $ws->get_outgoing_events();
        $this->assertCount( 1, $out_events );

        $this->assertSame( 'DISCONNECT', $out_events[0]->get_type() );
        $this->assertNull( $out_events[0]->get_content() );
    }

    /**
     * @test
     */
    function shouldCreateHeadersBasic() {
        $ws = new WebSocketContext('conn-1', [], []);
        $headers = $ws->to_headers();

        $this->assertEquals([
            'Content-Type' => 'application/websocket-events',
        ], $headers);
    }

    /**
     * @test
     */
    function shouldCreateHeadersAccepted() {
        $ws = new WebSocketContext('conn-1', [], []);
        $ws->accept();

        $headers = $ws->to_headers();

        $this->assertEquals([
            'Content-Type' => 'application/websocket-events',
            'Sec-WebSocket-Extensions' => 'grip',
        ], $headers);
    }

    /**
     * @test
     */
    function shouldCreateHeadersMetas() {
        $ws = new WebSocketContext('conn-1', [
            'goo' => 'hoo',
            'foo' => 'hello, world',
        ], []);

        $ws->meta = [
            'foo' => 'hi',
            'bar' => 'ho',
            'baz' => 'hello',
        ];

        $headers = $ws->to_headers();

        $this->assertEquals([
            'Content-Type' => 'application/websocket-events',
            'Set-Meta-goo' => '',
            'Set-Meta-foo' => 'hi',
            'Set-Meta-bar' => 'ho',
            'Set-Meta-baz' => 'hello',
        ], $headers);
    }

}
