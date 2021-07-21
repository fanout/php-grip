<?php


namespace Fanout\Grip\Tests\Unit;

use Fanout\Grip\Auth\JwtAuth;
use Fanout\Grip\Data\FormatBase;
use Fanout\Grip\Data\Http\HttpResponseFormat;
use Fanout\Grip\Data\Http\HttpStreamFormat;
use Fanout\Grip\Data\Item;
use Fanout\Grip\Engine\Publisher;
use Fanout\Grip\Engine\PublisherClient;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Promise\RejectionException;
use PHPUnit\Framework\TestCase;

class PublisherTest extends TestCase {

    /**
     * @test
     */
    function shouldConstructEmpty() {

        $publisher = new Publisher();

        $this->assertIsArray( $publisher->clients );
        $this->assertCount( 0, $publisher->clients );

    }

    /**
     * @test
     */
    function shouldConstructOneClient() {

        $publisher = new Publisher([
            'control_uri' => 'http://uri',
            'control_iss' => 'iss',
            'key' => 'key==',
        ]);

        $this->assertIsArray( $publisher->clients );
        $this->assertCount( 1, $publisher->clients );

        $this->assertEquals( 'http://uri', $publisher->clients[0]->uri );
        $this->assertInstanceOf( JwtAuth::class, $publisher->clients[0]->auth );

        /** @var JwtAuth $auth */
        $auth = $publisher->clients[0]->auth;
        $this->assertEquals( [ 'iss' => 'iss' ], $auth->claim );
        $this->assertEquals( 'key==', $auth->key );

    }

    /**
     * @test
     */
    function shouldConstructTwoClients() {

        $publisher = new Publisher([
            [
                'control_uri' => 'http://uri2',
                'control_iss' => 'iss2',
                'key' => 'key==2',
            ],
            [
                'control_uri' => 'http://uri3',
                'control_iss' => 'iss3',
                'key' => 'key==3',
            ],
        ]);

        $this->assertIsArray( $publisher->clients );
        $this->assertCount( 2, $publisher->clients );

        $this->assertEquals( 'http://uri2', $publisher->clients[0]->uri );
        $this->assertInstanceOf( JwtAuth::class, $publisher->clients[0]->auth );

        /** @var JwtAuth $auth */
        $auth = $publisher->clients[0]->auth;
        $this->assertEquals( [ 'iss' => 'iss2' ], $auth->claim );
        $this->assertEquals( 'key==2', $auth->key );

        $this->assertEquals( 'http://uri3', $publisher->clients[1]->uri );
        $this->assertInstanceOf( JwtAuth::class, $publisher->clients[1]->auth );

        /** @var JwtAuth $auth */
        $auth = $publisher->clients[1]->auth;
        $this->assertEquals( [ 'iss' => 'iss3' ], $auth->claim );
        $this->assertEquals( 'key==3', $auth->key );

    }

    /**
     * @test
     */
    function shouldAllowAdditionalConfigs() {

        $publisher = new Publisher([
            'control_uri' => 'http://uri',
            'control_iss' => 'iss',
            'key' => 'key==',
        ]);

        $this->assertIsArray( $publisher->clients );
        $this->assertCount( 1, $publisher->clients );

        $this->assertEquals( 'http://uri', $publisher->clients[0]->uri );
        $this->assertInstanceOf( JwtAuth::class, $publisher->clients[0]->auth );

        /** @var JwtAuth $auth */
        $auth = $publisher->clients[0]->auth;
        $this->assertEquals( [ 'iss' => 'iss' ], $auth->claim );
        $this->assertEquals( 'key==', $auth->key );

        $publisher->apply_config([
            [
                'control_uri' => 'http://uri2',
                'control_iss' => 'iss2',
                'key' => 'key==2',
            ],
            [
                'control_uri' => 'http://uri3',
                'control_iss' => 'iss3',
                'key' => 'key==3',
            ],
        ]);

        $this->assertIsArray( $publisher->clients );
        $this->assertCount( 3, $publisher->clients );

        $this->assertEquals( 'http://uri', $publisher->clients[0]->uri );
        $this->assertInstanceOf( JwtAuth::class, $publisher->clients[0]->auth );

        /** @var JwtAuth $auth */
        $auth = $publisher->clients[0]->auth;
        $this->assertEquals( [ 'iss' => 'iss' ], $auth->claim );
        $this->assertEquals( 'key==', $auth->key );

        $this->assertEquals( 'http://uri2', $publisher->clients[1]->uri );
        $this->assertInstanceOf( JwtAuth::class, $publisher->clients[1]->auth );

        /** @var JwtAuth $auth */
        $auth = $publisher->clients[1]->auth;
        $this->assertEquals( [ 'iss' => 'iss2' ], $auth->claim );
        $this->assertEquals( 'key==2', $auth->key );

        $this->assertEquals( 'http://uri3', $publisher->clients[2]->uri );
        $this->assertInstanceOf( JwtAuth::class, $publisher->clients[2]->auth );

        /** @var JwtAuth $auth */
        $auth = $publisher->clients[2]->auth;
        $this->assertEquals( [ 'iss' => 'iss3' ], $auth->claim );
        $this->assertEquals( 'key==3', $auth->key );

    }

    /**
     * @test
     */
    function shouldAddClient() {
        $publisher = new Publisher([
            'control_uri' => 'http://uri',
            'control_iss' => 'iss',
            'key' => 'key==',
        ]);

        $this->assertIsArray( $publisher->clients );
        $this->assertCount( 1, $publisher->clients );

        $this->assertEquals( 'http://uri', $publisher->clients[0]->uri );
        $this->assertInstanceOf( JwtAuth::class, $publisher->clients[0]->auth );

        /** @var JwtAuth $auth */
        $auth = $publisher->clients[0]->auth;
        $this->assertEquals( [ 'iss' => 'iss' ], $auth->claim );
        $this->assertEquals( 'key==', $auth->key );

        $new_client = new PublisherClient( 'http://uri' );
        $publisher->add_client( $new_client );

        $this->assertCount( 2, $publisher->clients );
        $this->assertEquals( 'http://uri', $publisher->clients[1]->uri );
        $this->assertNull( $publisher->clients[1]->auth );

    }

    /**
     * @test
     */
    function shouldPublishCallsClientPublishes() {

        $item = new Item([]);

        $mock_object = $this->getMockBuilder( PublisherClient::class )
            ->disableOriginalConstructor()
            ->getMock();

        $mock_object->expects($this->once())
            ->method( 'publish' )
            ->willReturn(new FulfilledPromise(true))
            ->with( 'chan', $item );

        $publisher = new Publisher();
        $publisher->add_client( $mock_object );

        $promise = $publisher->publish( 'chan', $item );

        $promise->wait();

    }

    /**
     * @test
     */
    function shouldPublishCallsAllClientsPublishes() {

        $item = new Item([]);

        $mock_object_1 = $this->getMockBuilder( PublisherClient::class )
            ->disableOriginalConstructor()
            ->getMock();

        $mock_object_1->expects($this->once())
            ->method( 'publish' )
            ->willReturn(new FulfilledPromise(true))
            ->with( 'chan', $item );

        $mock_object_2 = $this->getMockBuilder( PublisherClient::class )
            ->disableOriginalConstructor()
            ->getMock();

        $mock_object_2->expects($this->once())
            ->method( 'publish' )
            ->willReturn(new FulfilledPromise(true))
            ->with( 'chan', $item );

        $publisher = new Publisher();
        $publisher->add_client( $mock_object_1 );
        $publisher->add_client( $mock_object_2 );

        $promise = $publisher->publish( 'chan', $item );

        $promise->wait();

    }

    /**
     * @test
     */
    function shouldPublishThrowsInClientsPublish() {

        $item = new Item([]);

        $mock_object_1 = $this->getMockBuilder( PublisherClient::class )
            ->disableOriginalConstructor()
            ->getMock();

        $mock_object_1->expects($this->once())
            ->method( 'publish' )
            ->willReturn(new RejectedPromise('Error'))
            ->with( 'chan', $item );

        $mock_object_2 = $this->getMockBuilder( PublisherClient::class )
            ->disableOriginalConstructor()
            ->getMock();

        $mock_object_2->expects($this->once())
            ->method( 'publish' )
            ->willReturn(new FulfilledPromise(true))
            ->with( 'chan', $item );

        $publisher = new Publisher();
        $publisher->add_client( $mock_object_1 );
        $publisher->add_client( $mock_object_2 );

        $this->expectException(RejectionException::class);

        $promise = $publisher->publish( 'chan', $item );

        $promise->wait();

    }

    /**
     * @test
     */
    function shouldPublishFormatWithIdAndPrevId() {

        $mock_format = $this->getMockBuilder( FormatBase::class )
            ->getMock();

        $mock_publisher_client = $this->getMockBuilder( PublisherClient::class )
            ->disableOriginalConstructor()
            ->getMock();

        $mock_publisher_client->expects($this->once())
            ->method( 'publish' )
            ->willReturn(new FulfilledPromise(true))
            ->with( 'chan', $this->callback(function($item) use ($mock_format) {
                $this->assertInstanceOf(Item::class, $item);
                /** @var Item $item */
                $this->assertEquals( 'id', $item->id );
                $this->assertEquals( 'prev-id', $item->prev_id );
                $this->assertCount( 1, $item->formats );

                $this->assertSame( $mock_format, $item->formats[0] );

                return true;
            }) );


        $publisher = new Publisher();
        $publisher->add_client( $mock_publisher_client );

        $promise = $publisher->publish_formats( 'chan', $mock_format, 'id', 'prev-id' );

        $promise->wait();

    }

    /**
     * @test
     */
    function shouldPublishHttpResponse() {

        $mock_object = $this->getMockBuilder( PublisherClient::class )
            ->disableOriginalConstructor()
            ->getMock();

        $mock_object->expects($this->once())
            ->method( 'publish' )
            ->willReturn(new FulfilledPromise(true))
            ->with( 'chan', $this->callback(function($item) {
                $this->assertInstanceOf(Item::class, $item);
                /** @var Item $item */
                $this->assertCount( 1, $item->formats );
                $this->assertInstanceOf(HttpResponseFormat::class, $item->formats[0]);
                $format = $item->formats[0];
                /** @var HttpResponseFormat $format */
                $this->assertEquals('data', $format->body);
                return true;
            }) );

        $publisher = new Publisher();
        $publisher->add_client( $mock_object );

        $promise = $publisher->publish_http_response( 'chan', 'data' );

        $promise->wait();

    }

    /**
     * @test
     */
    function shouldPublishHttpStream() {

        $mock_object = $this->getMockBuilder( PublisherClient::class )
            ->disableOriginalConstructor()
            ->getMock();

        $mock_object->expects($this->once())
            ->method( 'publish' )
            ->willReturn(new FulfilledPromise(true))
            ->with( 'chan', $this->callback(function($item) {
                $this->assertInstanceOf(Item::class, $item);
                /** @var Item $item */
                $this->assertCount( 1, $item->formats );
                $this->assertInstanceOf(HttpStreamFormat::class, $item->formats[0]);
                $format = $item->formats[0];
                /** @var HttpStreamFormat $format */
                $this->assertEquals('data', $format->content);
                return true;
            }) );

        $publisher = new Publisher();
        $publisher->add_client( $mock_object );

        $promise = $publisher->publish_http_stream( 'chan', 'data' );

        $promise->wait();

    }

}
