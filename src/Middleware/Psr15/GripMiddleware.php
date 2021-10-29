<?php

namespace Fanout\Grip\Middleware\Psr15;

use Fanout\Grip\Auth\JwtAuth;
use Fanout\Grip\Data\WebSockets\WebSocketContext;
use Fanout\Grip\Data\WebSockets\WebSocketEvent;
use Fanout\Grip\Engine\PrefixedPublisher;
use Fanout\Grip\Engine\Publisher;
use Fanout\Grip\Errors\ConnectionIdMissingError;
use Fanout\Grip\Errors\WebSocketDecodeEventError;
use Fanout\Grip\Middleware\GripContext;
use Fanout\Grip\Utils\HttpUtil;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GripMiddleware implements MiddlewareInterface {

    const SERVER_REQUEST_ATTRIBUTE_NAME = 'fanout/grip_context';

    public static function add_grip_context( ServerRequestInterface $request, GripContext $grip ): ServerRequestInterface {
        return $request->withAttribute( self::SERVER_REQUEST_ATTRIBUTE_NAME, $grip );
    }

    public static function get_grip_context( ServerRequestInterface $request ): ?GripContext {
        return $request->getAttribute( self::SERVER_REQUEST_ATTRIBUTE_NAME );
    }

    /**
     * @var array
     */
    private $config;

    /**
     * @var Publisher|false|null
     */
    private $publisher;

    public function __construct( array $config = null ) {
        $this->config = $config ?? [];
        $this->publisher = null;
    }

    public function process( ServerRequestInterface $request, RequestHandlerInterface $handler ): ResponseInterface {

        $grip = self::get_grip_context( $request );
        if( $grip === null ) {
            $grip_proxy_required = $this->config[ 'grip_proxy_required' ] ?? false;
            $grip = new GripContext( $this );
            $grip->set_is_grip_proxy_required( $grip_proxy_required );
            $request = self::add_grip_context( $request, $grip );
        }

        if( $grip->is_handled() ) {
            // Already ran for this request, returning true
            return $handler->handle( $request );
        }

        $grip->set_is_handled();

        $grip_config = $this->config[ 'grip' ] ?? null;
        if( $grip_config === null ) {
            return new Response(500, [], 'No GRIP configuration provided.' . PHP_EOL );
        }

        $this->setup_grip( $request );

        if( $grip->is_grip_proxy_required() && !$grip->is_proxied() ) {
            // If we require a GRIP proxy but we detect there is
            // not one, we needs to fail now
            // nERROR - grip_proxy_required is true, but request not proxied.
            return new Response(501, [], 'Not Implemented.' . PHP_EOL );
        }

        $ws_context = $this->setup_ws_context( $request );
        if( $ws_context === 'connection-id-missing' ) {
            return new Response( 400, [], 'WebSocket event missing connection-id header.' . PHP_EOL );
        }
        if( $ws_context === 'websocket-decode-error' ) {
            return new Response( 400, [], 'Error parsing WebSocket events.' . PHP_EOL );
        }

        $response = $handler->handle( $request );

        if( $ws_context !== null ) {
            $response = $this->apply_ws_context( $request, $response );
        } else {
            $response = $this->apply_grip_instruct( $request, $response );
        }

        return $response;

    }

    private function setup_grip( ServerRequestInterface $request ) {

        $grip_sig = HttpUtil::flatten_headers( $request->getHeader( 'grip-sig' ) );
        if( empty($grip_sig) ) {
            // Not grip proxied
            return;
        }

        $publisher = $this->get_publisher();
        if( empty( $publisher ) ) {
            // No publisher
            return;
        }

        if( empty($publisher->clients) ) {
            // no publisher clients configured.
            return;
        }

        // If every client needs signing, then we mark as requires_signed
        $requires_signed = true;
        foreach( $publisher->clients as $client ) {
            if( !($client->auth instanceof JwtAuth && !empty($client->auth->key)) ) {
                $requires_signed = false;
                break;
            }
        }

        // If all publishers have keys, then only consider this signed if
        // the grip sig has been signed by one of them
        $is_signed = false;
        if( $requires_signed ) {
            // requires validating grip signature
            foreach( $publisher->clients as $client ) {
                // At this point, all clients are JwtAuth
                /** @var JwtAuth $auth */
                $auth = $client->auth;
                // validating: $grip_sig with $auth->key
                if( JwtAuth::validate_signature( $grip_sig, $auth->key ) ) {
                    // validated
                    $is_signed = true;
                    break;
                }
                // not validated
            }
            if (!$is_signed) {
                // Could not validate grip signature
                // If we need to be signed but we got here without a signature,
                // we don't even consider this proxied.
                return;
            }
        }

        $grip = self::get_grip_context( $request );

        $grip->set_is_signed( $is_signed );
        $grip->set_is_proxied();
    }

    private function setup_ws_context( ServerRequestInterface $request ) {

        if( !WebSocketContext::is_ws_over_http( $request ) ) {
            // is_ws_over_http false
            return null;
        }
        // is_ws_over_http true

        try {
            $ws_context = WebSocketContext::from_req( $request );
        } catch( ConnectionIdMissingError $ex ) {
            // ERROR - connection-id header needed
            return 'connection-id-missing';
        } catch( WebSocketDecodeEventError $ex ) {
            // ERROR - error parsing websocket events
            return 'websocket-decode-error';
        }

        $grip = self::get_grip_context( $request );

        $grip->set_ws_context( $ws_context );

        return $ws_context;
    }

    public function get_publisher() {
        if( $this->publisher === false ) {
            return null;
        }

        if( $this->publisher !== null ) {
            return $this->publisher;
        }

        $grip_config = $this->config[ 'grip' ] ?? null;
        $grip_prefix = $this->config[ 'prefix' ] ?? null;

        if( $grip_config === null ) {
            $this->publisher = false;
            return null;
        }

        $this->publisher = new PrefixedPublisher( $grip_config, $grip_prefix );

        return $this->publisher;
    }

    function apply_grip_instruct( ServerRequestInterface $request, ResponseInterface $response ): ResponseInterface {
        $grip = self::get_grip_context( $request );
        if( !$grip->has_instruct() ) {
            return $response;
        }

        $grip_instruct = $grip->get_instruct();

        if( $response->getStatusCode() === 304 ) {
            // Code 304 only allows certain headers.
            // Some web servers strictly enforce this.
            // In that case we won't be able to use
            // Grip- headers to talk to the proxy.
            // Switch to code 200 and use Grip-Status
            // to specify intended status.
            // Using gripInstruct setStatus header to handle 304
            $response = $response->withStatus( 200 );
            $grip_instruct->set_status( 304 );
        }

        foreach( $grip_instruct->build_headers() as $header_name => $header_value ) {
            $response = $response->withAddedHeader( $header_name, $header_value );
        }

        return $response;
    }

    function apply_ws_context( ServerRequestInterface $request, ResponseInterface $response ): ResponseInterface {
        $grip = self::get_grip_context( $request );
        $ws_context = $grip->get_ws_context();

        if( $response->getStatusCode() === 200 || $response->getStatusCode() === 204 ) {
            // We can safely use Response#header() as the header values are always strings.
            foreach( $ws_context->to_headers() as $header_name => $header_value ) {
                $response = $response->withAddedHeader( $header_name, $header_value );
            }

            // Add outgoing events to response
            $out_events = $ws_context->get_outgoing_events();
            $out_events_encoded = strval( WebSocketEvent::encode_events( $out_events ) );
            if( !empty( $out_events_encoded ) ) {
                $response_content = Utils::streamFor( strval( $response->getBody() ) );
                $response_content->write($out_events_encoded);
                $response = $response->withStatus( 200 )
                    ->withBody( $response_content );
            }
        }

        return $response;
    }

}
