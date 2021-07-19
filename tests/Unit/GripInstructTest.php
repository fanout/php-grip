<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Data\Channel;
use Fanout\Grip\Data\GripInstruct;
use PHPUnit\Framework\TestCase;

class GripInstructTest extends TestCase {

    /**
     * @test
     */
    public function shouldConstruct() {

        $grip_instruct = new GripInstruct();
        $this->assertSame( [], $grip_instruct->channels );

        $grip_instruct = new GripInstruct( 'foo' );
        $this->assertCount( 1, $grip_instruct->channels );
        $this->assertSame( 'foo', $grip_instruct->channels[0]->name );

        $grip_instruct = new GripInstruct( [ 'foo', 'bar' ] );
        $this->assertCount( 2, $grip_instruct->channels );
        $this->assertSame( 'foo', $grip_instruct->channels[0]->name );
        $this->assertSame( 'bar', $grip_instruct->channels[1]->name );

        $grip_instruct = new GripInstruct( new Channel( 'foo' ) );
        $this->assertCount( 1, $grip_instruct->channels );
        $this->assertSame( 'foo', $grip_instruct->channels[0]->name );

        $grip_instruct = new GripInstruct( [ new Channel( 'foo' ), new Channel( 'bar' ) ] );
        $this->assertCount( 2, $grip_instruct->channels );
        $this->assertSame( 'foo', $grip_instruct->channels[0]->name );
        $this->assertSame( 'bar', $grip_instruct->channels[1]->name );

        $grip_instruct = new GripInstruct( [ new Channel( 'foo' ), 'bar', null, new Channel( 'baz' ) ] );
        $this->assertCount( 3, $grip_instruct->channels );
        $this->assertSame( 'foo', $grip_instruct->channels[0]->name );
        $this->assertSame( 'bar', $grip_instruct->channels[1]->name );
        $this->assertSame( 'baz', $grip_instruct->channels[2]->name );

    }

    /**
     * @test
     */
    public function shouldBeAbleToAddChannel() {

        $grip_instruct = new GripInstruct( 'foo' );
        $grip_instruct->add_channel( 'bar' );

        $this->assertCount( 2, $grip_instruct->channels );
        $this->assertSame( 'foo', $grip_instruct->channels[0]->name );
        $this->assertSame( 'bar', $grip_instruct->channels[1]->name );

        $grip_instruct->add_channel( new Channel( 'baz' ) );
        $this->assertCount( 3, $grip_instruct->channels );
        $this->assertSame( 'baz', $grip_instruct->channels[2]->name );

    }

    /**
     * @test
     */
    public function shouldBeAbleToSetStatus() {

        $grip_instruct = new GripInstruct();
        $this->assertNull( $grip_instruct->status );
        $grip_instruct->set_status(1);
        $this->assertSame( 1, $grip_instruct->status );

    }

    /**
     * @test
     */
    public function shouldBeAbleToSetHoldType() {

        $grip_instruct = new GripInstruct();
        $this->assertNull( $grip_instruct->hold );

        $grip_instruct->set_hold_long_poll( 5 );
        $this->assertSame( 'response', $grip_instruct->hold );
        $this->assertSame( 5, $grip_instruct->timeout );

        $grip_instruct->set_hold_stream();
        $this->assertSame( 'stream', $grip_instruct->hold );

    }

    /**
     * @test
     */
    public function shouldBeAbleToSetKeepAlive() {

        $grip_instruct = new GripInstruct();
        $this->assertNull( $grip_instruct->keep_alive_data );

        $grip_instruct->set_keep_alive( 'keepalive-data', GripInstruct::KEEPALIVE_DATA_FORMAT_STRING, 5 );
        $this->assertSame( 'keepalive-data', $grip_instruct->keep_alive_data );
        $this->assertSame( GripInstruct::KEEPALIVE_DATA_FORMAT_STRING, $grip_instruct->keep_alive_data_format );
        $this->assertSame( 5, $grip_instruct->keep_alive_timeout );

        $data = pack( 'C*', 0x41, 0x42, 0x43 );
        $grip_instruct->set_keep_alive( $data, GripInstruct::KEEPALIVE_DATA_FORMAT_PACK, 10 );
        $this->assertSame( pack( 'C*', 0x41, 0x42, 0x43 ), $grip_instruct->keep_alive_data );
        $this->assertSame( GripInstruct::KEEPALIVE_DATA_FORMAT_PACK, $grip_instruct->keep_alive_data_format );
        $this->assertSame( 10, $grip_instruct->keep_alive_timeout );

    }

    /**
     * @test
     */
    public function shouldBeAbleToSetNextLink() {

        $grip_instruct = new GripInstruct();
        $this->assertNull( $grip_instruct->next_link_value );

        $grip_instruct->set_next_link( 'next-link', 5 );
        $this->assertSame( 'next-link', $grip_instruct->next_link_value );
        $this->assertSame( 5, $grip_instruct->next_link_timeout );

    }

    /**
     * @test
     */
    public function shouldBeAbleToSetMetaValues() {

        $grip_instruct = new GripInstruct();
        $this->assertIsArray( $grip_instruct->meta );

        $grip_instruct->meta[ 'foo' ] = 'bar';
        $this->assertSame( [ 'foo'=>'bar' ], $grip_instruct->meta );

    }

    /**
     * @test
     */
    public function shouldBuildGripHeadersSimple() {

        $grip_instruct = new GripInstruct( 'foo' );
        $this->assertSame([
            'Grip-Channel' => 'foo',
        ], $grip_instruct->build_headers());

    }

    /**
     * @test
     */
    public function shouldBuildGripHeadersMultipleChannels() {

        $grip_instruct = new GripInstruct( [ 'foo', 'bar', 'baz' ] );
        $this->assertSame([
            'Grip-Channel' => 'foo, bar, baz',
        ], $grip_instruct->build_headers());

    }

    /**
     * @test
     */
    public function shouldBuildGripHeadersChannelObject() {

        $grip_instruct = new GripInstruct( new Channel( 'foo' ) );
        $this->assertSame([
            'Grip-Channel' => 'foo',
        ], $grip_instruct->build_headers());

    }

    /**
     * @test
     */
    public function shouldBuildGripHeadersChannelObjectWithPrevId() {

        $grip_instruct = new GripInstruct( [ new Channel( 'foo', 'bar' ), 'baz' ] );
        $this->assertSame([
            'Grip-Channel' => 'foo; prev-id=bar, baz',
        ], $grip_instruct->build_headers());

    }

    /**
     * @test
     */
    public function shouldBuildGripHeadersStatus() {

        $grip_instruct = new GripInstruct( 'foo' );
        $grip_instruct->set_status( 302 );
        $this->assertSame([
            'Grip-Channel' => 'foo',
            'Grip-Status' => '302',
        ], $grip_instruct->build_headers());

    }

    /**
     * @test
     */
    public function shouldBuildGripHeadersLongPoll() {

        $grip_instruct = new GripInstruct( 'foo' );
        $grip_instruct->set_hold_long_poll( 100 );
        $this->assertSame([
            'Grip-Channel' => 'foo',
            'Grip-Hold' => 'response',
            'Grip-Timeout' => '100',
        ], $grip_instruct->build_headers());

    }

    /**
     * @test
     */
    public function shouldBuildGripHeadersLongPollKeepAliveCString() {

        $grip_instruct = new GripInstruct( 'foo' );
        $grip_instruct->set_hold_long_poll( 100 );
        $grip_instruct->set_keep_alive( 'bar', GripInstruct::KEEPALIVE_DATA_FORMAT_STRING, 150 );
        $this->assertSame([
            'Grip-Channel' => 'foo',
            'Grip-Hold' => 'response',
            'Grip-Timeout' => '100',
            'Grip-Keep-Alive' => 'bar; format=cstring; timeout=150',
        ], $grip_instruct->build_headers());

    }

    /**
     * @test
     */
    public function shouldBuildGripHeadersLongPollKeepAliveBase64() {

        $grip_instruct = new GripInstruct( 'foo' );
        $grip_instruct->set_hold_long_poll( 100 );
        $grip_instruct->set_keep_alive( pack('C*', 0x41, 0x42, 0x43), GripInstruct::KEEPALIVE_DATA_FORMAT_PACK, 150 );
        $this->assertSame([
            'Grip-Channel' => 'foo',
            'Grip-Hold' => 'response',
            'Grip-Timeout' => '100',
            'Grip-Keep-Alive' => 'QUJD; format=base64; timeout=150',
        ], $grip_instruct->build_headers());

    }

    /**
     * @test
     */
    public function shouldBuildGripHeadersLongPollSetMeta() {

        $grip_instruct = new GripInstruct( 'foo' );
        $grip_instruct->set_hold_long_poll( 100 );
        // Meta is set directly
        $grip_instruct->meta = [
            'bar' => 'baz',
            'hoge' => 'piyo',
        ];
        $this->assertSame([
            'Grip-Channel' => 'foo',
            'Grip-Hold' => 'response',
            'Grip-Timeout' => '100',
            'Grip-Set-Meta' => 'bar="baz", hoge="piyo"',
        ], $grip_instruct->build_headers());

    }

    /**
     * @test
     */
    public function shouldBuildGripHeadersStream() {

        $grip_instruct = new GripInstruct( 'foo' );
        $grip_instruct->set_hold_stream();
        $this->assertSame([
            'Grip-Channel' => 'foo',
            'Grip-Hold' => 'stream',
        ], $grip_instruct->build_headers());

    }

    /**
     * @test
     */
    public function shouldBuildGripHeadersNextLink() {

        $grip_instruct = new GripInstruct( 'foo' );
        $grip_instruct->set_next_link( 'https://www.example.com/path/' );
        $this->assertSame([
            'Grip-Channel' => 'foo',
            'Grip-Link' => '<https://www.example.com/path/>; rel=next',
        ], $grip_instruct->build_headers());

    }

    /**
     * @test
     */
    public function shouldBuildGripHeadersNextLinkWithTimeout() {

        $grip_instruct = new GripInstruct( 'foo' );
        $grip_instruct->set_next_link( 'https://www.example.com/path/', 200 );
        $this->assertSame([
            'Grip-Channel' => 'foo',
            'Grip-Link' => '<https://www.example.com/path/>; rel=next; timeout=200',
        ], $grip_instruct->build_headers());

    }
}
