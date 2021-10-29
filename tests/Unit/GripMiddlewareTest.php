<?php

namespace Fanout\Grip\Tests\Unit;

use Fanout\Grip\Errors\GripInstructAlreadyStartedError;
use Fanout\Grip\Errors\GripInstructNotAvailableError;
use Fanout\Grip\Middleware\GripContext;
use Fanout\Grip\Middleware\GripMiddleware;
use Fanout\Grip\Utils\HttpUtil;
use Firebase\JWT\JWT;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class GripMiddlewareTest extends TestCase {

    const SAMPLE_KEY = 'sample_key';

    function config_grip( $params = null ): array {
        $clients = $params['clients'] ?? null;
        if( $clients === null ) {
            $grip = 'https://api.fanout.io/realm/realm?iss=realm';

            $use_sample_key = $params['use_sample_key'] ?? false;
            if($use_sample_key) {
                $key = self::SAMPLE_KEY;
            } else {
                $key = $params['use_key'] ?? false;
            }
            if( !empty( $key ) ) {
                $grip .= '&key=base64:' . base64_encode( $key );
            }
            $clients = [ $grip ];
        }

        $grip_proxy_required = $params['grip_proxy_required'] ?? false;

        return [
            'grip' => $clients,
            'prefix' => '',
            'grip_proxy_required' => $grip_proxy_required,
        ];
    }

    function create_request( array $params = null ): ServerRequestInterface {
        $grip_sig = $params['grip_sig'] ?? false;
        $is_websocket = $params['is_websocket'] ?? false;
        $has_connection_id = $params['has_connection_id'] ?? false;
        $body = $params['body'] ?? null;

        $http_method = 'GET';
        $headers = [];

        if( $grip_sig ) {
            $exp = time() + 60 * 60; // 1 hour ago from now
            if ($grip_sig === 'expired') {
                $exp = time() - 60 * 60; // 1 hour ago
            }
            $sig = JWT::encode([
                'iss' => 'realm',
                'exp' => $exp,
            ], self::SAMPLE_KEY );
            $headers['Grip-Sig'] = $sig;
        }

        if( $is_websocket ) {
            $headers[ 'Content-Type' ] = 'application/websocket-events';
            $http_method = 'POST';
        }

        if( $has_connection_id ) {
            $uuid = Uuid::uuid4();
            $headers[ 'Connection-Id' ] = $uuid->toString();
        }

        return new ServerRequest( $http_method, new Uri( 'https://www.example.com/' ), $headers, $body );
    }

    function create_response(): ResponseInterface {
        return new Response();
    }

    /** @test */
    function can_add_context() {
        $req = new ServerRequest( 'GET', new Uri( 'https://www.example.com/' ) );

        $grip_middleware = new GripMiddleware();
        $grip = new GripContext( $grip_middleware );
        $req = GripMiddleware::add_grip_context( $req, $grip );

        $grip = GripMiddleware::get_grip_context( $req );
        $this->assertInstanceOf( GripContext::class, $grip );

        $this->assertSame( $grip_middleware, $grip->get_grip_middleware() );
    }

    /** @test */
    function grip_middleware_throws_500_if_not_configured() {
        $req = $this->create_request();
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware();

        $called = 0;
        $res = $grip_middleware( $req, $res, function( $req, $res ) use (&$called) {
            $called = 1;
            return $res;
        } );

        $this->assertEquals( 0, $called );
        $this->assertEquals( 500, $res->getStatusCode() );
        $this->assertEquals( "No GRIP configuration provided.\n", $res->getBody() );
    }

    /** @test */
    function grip_middleware_is_installed() {
        $config = $this->config_grip();

        $req = $this->create_request();
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );
        $called = 0;
        $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            $grip = GripMiddleware::get_grip_context( $req );
            $this->assertTrue( $grip->is_handled() );
            return $res;
        });

        $this->assertEquals( 1, $called );
    }

    /** @test */
    function grip_middleware_detects_when_not_proxied() {
        $config = $this->config_grip();

        $req = $this->create_request();
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );

        $called = 0;
        $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            $grip = GripMiddleware::get_grip_context( $req );
            $this->assertFalse( $grip->is_proxied() );
            return $res;
        });

        $this->assertEquals( 1, $called );
    }

    /** @test */
    function grip_middleware_assumes_no_proxy_when_no_clients() {
        $config = $this->config_grip(['clients' => []]);

        $req = $this->create_request(['grip_sig' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );

        $called = 0;
        $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            $grip = GripMiddleware::get_grip_context( $req );
            $this->assertFalse( $grip->is_proxied() );
            return $res;
        });

        $this->assertEquals( 1, $called );
    }

    /** @test */
    function grip_middleware_detects_proxy_requires_no_sig() {
        $config = $this->config_grip();
        $req = $this->create_request(['grip_sig' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );

        $called = 0;
        $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            $grip = GripMiddleware::get_grip_context( $req );
            $this->assertTrue( $grip->is_proxied() );
            return $res;
        });

        $this->assertEquals( 1, $called );
    }

    /** @test */
    function grip_middleware_detects_proxy_requires_and_has_sig() {
        $config = $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);

        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );

        $called = 0;
        $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            $grip = GripMiddleware::get_grip_context( $req );
            $this->assertTrue( $grip->is_proxied() );
            return $res;
        });

        $this->assertEquals( 1, $called );
    }

    /** @test */
    function grip_middleware_detects_no_proxy_when_requires_and_has_expired_sig() {
        $config = $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => 'expired']);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );

        $called = 0;
        $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            $grip = GripMiddleware::get_grip_context( $req );
            $this->assertFalse( $grip->is_proxied() );
            return $res;
        });

        $this->assertEquals( 1, $called );
    }

    /** @test */
    function grip_middleware_detects_no_proxy_when_requires_and_has_invalid_sig() {
        $config = $this->config_grip(['use_key' => 'foo']);
        $req = $this->create_request(['grip_sig' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );

        $called = 0;
        $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            $grip = GripMiddleware::get_grip_context( $req );
            $this->assertFalse( $grip->is_proxied() );
            return $res;
        });

        $this->assertEquals( 1, $called );
    }

    /** @test */
    function grip_middleware_detects_not_signed_when_requires_no_sig() {
        $config = $this->config_grip();
        $req = $this->create_request(['grip_sig' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );

        $called = 0;
        $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            $grip = $grip_middleware->get_grip_context( $req );
            $this->assertFalse( $grip->is_signed() );
            return $res;
        });

        $this->assertEquals( 1, $called );
    }

    /** @test */
    function grip_middleware_detects_signed_when_requires_and_has_sig() {
        $config = $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );

        $called = 0;
        $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            $grip = $grip_middleware->get_grip_context( $req );
            $this->assertTrue( $grip->is_signed() );
            return $res;
        });

        $this->assertEquals( 1, $called );
    }


    /** @test */
    function grip_middleware_throws_501_when_requires_proxy_but_not_proxied() {
        $config = $this->config_grip(['grip_proxy_required' => true]);
        $req = $this->create_request();
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );

        $called = 0;
        $res = $grip_middleware( $req, $res, function( $req, $res ) use (&$called) {
            $called = 1;
            return $res;
        });

        $this->assertEquals( 0, $called );
        $this->assertEquals( 501, $res->getStatusCode() );
        $this->assertEquals( "Not Implemented.\n", $res->getBody() );
    }


    /** @test */
    function grip_middleware_allows_start_grip_instruct_when_proxied() {
        $config = $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );

        $called = 0;
        $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called, &$is_signed) {
            $called = 1;
            $grip = $grip_middleware->get_grip_context( $req );
            $grip_instruct = $grip->start_instruct();
            $this->assertNotNull( $grip_instruct );
            return $res;
        });

        $this->assertEquals( 1, $called );
    }


    /** @test */
    function grip_middleware_does_not_allow_start_grip_instruct_when_not_proxied() {
        $config = $this->config_grip();
        $req = $this->create_request();
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );

        $this->expectException( GripInstructNotAvailableError::class );
        $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware) {
            $grip = $grip_middleware->get_grip_context( $req );
            $grip->start_instruct();
            return $res;
        });
    }


    /** @test */
    function grip_middleware_does_not_allow_start_grip_instruct_multiple_times() {
        $config = $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );

        $this->expectException( GripInstructAlreadyStartedError::class );
        $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware) {
            $grip = $grip_middleware->get_grip_context( $req );
            $grip->start_instruct();
            $grip->start_instruct();
            return $res;
        });
    }


    /** @test */
    function grip_middleware_adds_headers_simple() {
        $config = $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );
        $res = $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware) {
            $grip = $grip_middleware->get_grip_context( $req );
            $grip_instruct = $grip->start_instruct();
            $grip_instruct->add_channel( 'foo' );
            return $res;
        });

        $this->assertEquals( 'foo', HttpUtil::flatten_headers( $res->getHeader( 'Grip-Channel' ) ) );
    }


    /** @test */
    function grip_middleware_doesnt_add_headers_unless_instruct_used() {
        $config = $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );
        $called = 0;
        $res = $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            return $res;
        });

        $this->assertEquals( 1, $called );
        $this->assertEmpty( $res->getHeader( 'Grip-Channel' ) );
    }


    /** @test */
    function grip_middleware_handle_304_status() {
        $config = $this->config_grip(['use_sample_key' => true]);
        $req = $this->create_request(['grip_sig' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );
        $res = $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware) {
            $grip = $grip_middleware->get_grip_context( $req );
            $grip_instruct = $grip->start_instruct();
            $grip_instruct->add_channel( 'foo' );
            return new Response( 304 );
        });
        $this->assertEquals( 200, $res->getStatusCode() );
        $this->assertEquals( '304', HttpUtil::flatten_headers( $res->getHeader( 'Grip-Status' ) ) );
    }

    /** @test */
    function grip_middleware_throws_400_when_is_websocket_but_no_connection_id() {
        $config = $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );
        $called = 0;
        $res = $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            return $res;
        });

        $this->assertEquals( 0, $called );
        $this->assertEquals( 400, $res->getStatusCode() );
        $this->assertEquals( 'WebSocket event missing connection-id header.' . PHP_EOL, $res->getBody() );
    }

    /** @test */
    function grip_middleware_makes_ws_context_when_is_websocket_and_connection_id_present() {
        $config = $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );
        $called = 0;
        $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            $grip = GripMiddleware::get_grip_context( $req );
            $this->assertNotNull( $grip->get_ws_context() );
            return $res;
        });

        $this->assertEquals( 1, $called );
    }

    /** @test */
    function grip_middleware_makes_ws_context_and_decodes_it_when_is_websocket_and_connection_id_present_with_valid_event() {
        $config = $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => 'true', 'body' => "TEXT 5\r\nHello\r\n"]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );
        $called = 0;
        $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            $grip = GripMiddleware::get_grip_context( $req );
            $ws_context = $grip->get_ws_context();
            $data = $ws_context->recv();
            $this->assertEquals( 'Hello', $data );
            return $res;
        });

        $this->assertEquals( 1, $called );
    }

    /** @test */
    function grip_middleware_throws_400_when_is_websocket_and_connection_id_but_event_is_malformed() {
        $config = $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => 'true', 'body' => "TEXT 5\r\n"]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );
        $called = 0;
        $res = $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            return $res;
        });

        $this->assertEquals( 0, $called );
        $this->assertEquals( 400, $res->getStatusCode() );
        $this->assertEquals( 'Error parsing WebSocket events.' . PHP_EOL, $res->getBody() );
    }

    /** @test */
    function grip_middleware_makes_no_ws_context_when_not_websocket() {
        $config = $this->config_grip();
        $req = $this->create_request(['grip_sig' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );
        $called = 0;
        $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            $grip = GripMiddleware::get_grip_context( $req );
            $this->assertNull( $grip->get_ws_context() );
            return $res;
        });

        $this->assertEquals( 1, $called );
    }

    /** @test */
    function grip_middleware_outputs_no_ws_headers_when_not_websocket() {
        $config = $this->config_grip();
        $req = $this->create_request(['grip_sig' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );
        $called = 0;
        $res = $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            return $res;
        });

        $this->assertEquals( 1, $called );
        $this->assertEmpty( $res->getHeader( 'Content-Type' ) );
    }

    /** @test */
    function grip_middleware_outputs_ws_headers_when_is_websocket_and_connection_id_present() {
        $config = $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );
        $res = $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware) {
            $grip = GripMiddleware::get_grip_context( $req );
            $ws_context = $grip->get_ws_context();
            $ws_context->accept();
            return $res;
        });

        $this->assertEquals( 'application/websocket-events', HttpUtil::flatten_headers( $res->getHeader( 'Content-Type' ) ) );
        $this->assertEquals( 'grip', HttpUtil::flatten_headers( $res->getHeader( 'Sec-WebSocket-Extensions' ) ) );
    }


    /** @test */
    function grip_middleware_does_not_output_ws_headers_when_is_websocket_and_connection_id_present_but_code_not_200() {
        $config = $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );
        $res = $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware) {
            return new Response( 500 );
        });

        $this->assertEmpty( $res->getHeader( 'Content-Type' ) );
    }

    /** @test */
    function grip_middleware_outputs_ws_events_when_is_websocket_and_connection_id_present_and_events_are_sent() {
        $config = $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );
        $res = $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware) {
            $grip = GripMiddleware::get_grip_context( $req );
            $ws_context = $grip->get_ws_context();
            $ws_context->send( 'foo' );
            return $res;
        });

        $this->assertEquals( "TEXT 5\r\nm:foo\r\n", $res->getBody() );
    }

    /** @test */
    function grip_middleware_outputs_changes_204_to_200_when_is_websocket_and_connection_id_present_and_events_are_sent() {
        $config = $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );
        $res = $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware) {
            $grip = GripMiddleware::get_grip_context( $req );
            $ws_context = $grip->get_ws_context();
            $ws_context->send( 'foo' );
            return new Response( 204 );
        });

        $this->assertEquals( 200, $res->getStatusCode() );
    }

    /** @test */
    function grip_middleware_outputs_no_ws_events_when_is_websocket_and_connection_id_present_and_events_are_not_sent() {
        $config = $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );
        $called = 0;
        $res = $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware, &$called) {
            $called = 1;
            return $res;
        });

        $this->assertEquals( 1, $called );
        $this->assertEmpty( strval( $res->getBody() ) );
    }

    /** @test */
    function grip_middleware_keeps_204_when_is_websocket_and_connection_id_present_and_events_are_not_sent_and_code_is_204() {
        $config = $this->config_grip();
        $req = $this->create_request(['grip_sig' => true, 'is_websocket' => true, 'has_connection_id' => true]);
        $res = $this->create_response();

        $grip_middleware = new GripMiddleware( $config );
        $res = $grip_middleware( $req, $res, function( $req, $res ) use ($grip_middleware) {
            return new Response( 204 );
        });

        $this->assertEquals( 204, $res->getStatusCode() );
    }
}
