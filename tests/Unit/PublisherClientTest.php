<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Auth\BasicAuth;
use Fanout\Grip\Auth\JwtAuth;
use Fanout\Grip\Engine\PublisherClient;
use PHPUnit\Framework\TestCase;

class PublisherClientTest extends TestCase {

    /**
     * @test
     */
    function shouldConstructWithUriOnly() {
        $client = new PublisherClient( 'uri' );

        $this->assertEquals( 'uri', $client->uri );
        $this->assertNull( $client->auth );
    }

    /**
     * @test
     */
    function shouldConstructWithUriWithTrailingSlash() {
        $client = new PublisherClient( 'uri/' );

        $this->assertEquals( 'uri', $client->uri );
        $this->assertNull( $client->auth );
    }

    /**
     * @test
     */
    function shouldSetBasicAuth() {
        $client = new PublisherClient( 'uri' );
        $client->set_auth_basic( 'user', 'pass' );

        $this->assertInstanceOf( BasicAuth::class, $client->auth );

        /** @var BasicAuth $auth */
        $auth = $client->auth;
        $this->assertEquals( 'user', $auth->user );
        $this->assertEquals( 'pass', $auth->pass );
    }

    /**
     * @test
     */
    function shouldSetJwtAuthWithClaimAndKey() {
        $client = new PublisherClient( 'uri' );

        $claim = [ 'iss' => 'iss' ];
        $client->set_auth_jwt( $claim, 'key' );

        $this->assertInstanceOf( JwtAuth::class, $client->auth );

        /** @var JwtAuth $auth */
        $auth = $client->auth;
        $this->assertEquals( [ 'iss' => 'iss' ], $auth->claim );
        $this->assertEquals( 'key', $auth->key );
    }

    /**
     * @test
     */
    function shouldSetJwtAuthWithToken() {
        $client = new PublisherClient( 'uri' );

        $client->set_auth_jwt( 'token' );

        $this->assertInstanceOf( JwtAuth::class, $client->auth );

        /** @var JwtAuth $auth */
        $auth = $client->auth;
        $this->assertEquals( 'token', $auth->token );
    }

}
