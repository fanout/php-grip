<?php

use Fanout\Grip\Data\WebSockets\WebSocketMessageFormat;
use Fanout\Grip\Middleware\Psr15\GripMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;

require __DIR__ . '/../../../../vendor/autoload.php';

const CHANNEL_NAME = 'test';

$app = AppFactory::create();

$middleware = new GripMiddleware([
    'grip' => 'http://localhost:5561/'
]);
$app->add( $middleware );

// Websocket-over-HTTP is translated to HTTP POST
$app->post( '/api/websocket', function (ServerRequestInterface $request, ResponseInterface $response) {

    $grip = GripMiddleware::get_grip_context( $request );
    $ws_context = $grip->get_ws_context();

    if( is_null( $ws_context ) ) {
        $response = $response->withStatus( 400 );
        $response->getBody()->write( '[not a websocket request]' . PHP_EOL );
        return $response;
    }

    // If this is a new connection, accept it and subscribe it to a channel
    if( $ws_context->is_opening() ) {
        $ws_context->accept();
        $ws_context->subscribe( CHANNEL_NAME );
    }

    while( $ws_context->can_recv() ) {
        $message = $ws_context->recv();

        if( is_null( $message ) ) {
            // If return value is undefined then connection is closed
            $ws_context->close();
            break;
        }

        // Echo the message
        $ws_context->send( $message );
    }

    return $response;
});

$app->post( '/api/broadcast', function (ServerRequestInterface $request, ResponseInterface $response) use ($middleware) {

    $response = $response->withHeader( 'Content-Type', 'text/plain' );

    $data = strval( $request->getBody() );
    $publisher = $middleware->get_publisher();

    $publisher->publish_formats( CHANNEL_NAME, new WebSocketMessageFormat( $data ) )
        ->wait();

    $response->getBody()->write('Ok' . PHP_EOL );
    return $response;
});

$app->run();
