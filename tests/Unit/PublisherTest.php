<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Auth\JwtAuth;
use Fanout\Grip\Engine\Publisher;
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
            'control_uri' => 'uri',
            'control_iss' => 'iss',
            'key' => 'key==',
        ]);

        $this->assertIsArray( $publisher->clients );
        $this->assertCount( 1, $publisher->clients );

        $this->assertEquals( 'uri', $publisher->clients[0]->uri );
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
                'control_uri' => 'uri2',
                'control_iss' => 'iss2',
                'key' => 'key==2',
            ],
            [
                'control_uri' => 'uri3',
                'control_iss' => 'iss3',
                'key' => 'key==3',
            ],
        ]);

        $this->assertIsArray( $publisher->clients );
        $this->assertCount( 2, $publisher->clients );

        $this->assertEquals( 'uri2', $publisher->clients[0]->uri );
        $this->assertInstanceOf( JwtAuth::class, $publisher->clients[0]->auth );

        /** @var JwtAuth $auth */
        $auth = $publisher->clients[0]->auth;
        $this->assertEquals( [ 'iss' => 'iss2' ], $auth->claim );
        $this->assertEquals( 'key==2', $auth->key );

        $this->assertEquals( 'uri3', $publisher->clients[1]->uri );
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
            'control_uri' => 'uri',
            'control_iss' => 'iss',
            'key' => 'key==',
        ]);

        $this->assertIsArray( $publisher->clients );
        $this->assertCount( 1, $publisher->clients );

        $this->assertEquals( 'uri', $publisher->clients[0]->uri );
        $this->assertInstanceOf( JwtAuth::class, $publisher->clients[0]->auth );

        /** @var JwtAuth $auth */
        $auth = $publisher->clients[0]->auth;
        $this->assertEquals( [ 'iss' => 'iss' ], $auth->claim );
        $this->assertEquals( 'key==', $auth->key );

        $publisher->apply_config([
            [
                'control_uri' => 'uri2',
                'control_iss' => 'iss2',
                'key' => 'key==2',
            ],
            [
                'control_uri' => 'uri3',
                'control_iss' => 'iss3',
                'key' => 'key==3',
            ],
        ]);

        $this->assertIsArray( $publisher->clients );
        $this->assertCount( 3, $publisher->clients );

        $this->assertEquals( 'uri', $publisher->clients[0]->uri );
        $this->assertInstanceOf( JwtAuth::class, $publisher->clients[0]->auth );

        /** @var JwtAuth $auth */
        $auth = $publisher->clients[0]->auth;
        $this->assertEquals( [ 'iss' => 'iss' ], $auth->claim );
        $this->assertEquals( 'key==', $auth->key );

        $this->assertEquals( 'uri2', $publisher->clients[1]->uri );
        $this->assertInstanceOf( JwtAuth::class, $publisher->clients[1]->auth );

        /** @var JwtAuth $auth */
        $auth = $publisher->clients[1]->auth;
        $this->assertEquals( [ 'iss' => 'iss2' ], $auth->claim );
        $this->assertEquals( 'key==2', $auth->key );

        $this->assertEquals( 'uri3', $publisher->clients[2]->uri );
        $this->assertInstanceOf( JwtAuth::class, $publisher->clients[2]->auth );

        /** @var JwtAuth $auth */
        $auth = $publisher->clients[2]->auth;
        $this->assertEquals( [ 'iss' => 'iss3' ], $auth->claim );
        $this->assertEquals( 'key==3', $auth->key );

    }

}
