<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Data\Channel;
use PHPUnit\Framework\TestCase;

class ChannelTest extends TestCase {

    /**
     * @test
     */
    public function shouldConstruct() {

        $channel = new Channel( 'name' );
        $this->assertSame( 'name', $channel->name );
        $this->assertEmpty( $channel->prev_id );

        $channel = new Channel( 'name', 'prev_id' );
        $this->assertSame( 'name', $channel->name );
        $this->assertSame( 'prev_id', $channel->prev_id );

    }

    /**
     * @test
     */
    public function shouldExport() {

        $channel = new Channel( 'name' );
        $this->assertSame( [ 'name' => 'name' ], $channel->export() );

        $channel = new Channel( 'name', 'prev_id' );
        $this->assertSame( [ 'name' => 'name', 'prev_id' => 'prev_id' ], $channel->export() );

    }

}
