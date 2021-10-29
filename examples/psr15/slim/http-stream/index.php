<?php

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

$app->get( '/api/stream', function (ServerRequestInterface $request, ResponseInterface $response) {

    $grip = GripMiddleware::get_grip_context( $request );
    if( !$grip->is_proxied() ) {
        $response = $response->withHeader( 'Content-Type', 'text/plain' );
        $response->getBody()->write( '[not proxied]' . PHP_EOL );
        return $response;
    }

    $grip_instruct = $grip->start_instruct();
    $grip_instruct->add_channel( CHANNEL_NAME );
    $grip_instruct->set_hold_stream();

    $response = $response->withHeader( 'Content-Type', 'text/plain' );
    $response->getBody()->write('[stream open]' . PHP_EOL );
    return $response;
});

$app->post( '/api/publish', function (ServerRequestInterface $request, ResponseInterface $response) use ($middleware) {

    $data = strval( $request->getBody() );
    $publisher = $middleware->get_publisher();

    $publisher->publish_http_stream( CHANNEL_NAME, $data . PHP_EOL )
        ->wait();

    $response = $response->withHeader( 'Content-Type', 'text/plain' );
    $response->getBody()->write('Ok' . PHP_EOL );
    return $response;
});

$app->run();
