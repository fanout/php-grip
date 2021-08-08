<?php

const CHANNEL_NAME = 'test';

use Fanout\Grip\Engine\Publisher;

require_once __DIR__ . '/../../vendor/autoload.php';

$uri = 'http://localhost:5561/';

@[ , $message ] = $argv;

echo "Publish URI: " . $uri . "\n";
echo "Channel: " . CHANNEL_NAME . "\n";
echo "Message: " . $message . "\n";

$publisher = new Publisher([ 'control_uri' => $uri ]);

$publisher->publish_websocket_message( CHANNEL_NAME, $message )
    ->then(function() {
        echo "Publish Successful!\n";
    })
    ->otherwise(function($e) {
        echo "Publish Fail!\n";
        echo json_encode( $e ) . "\n";
    })
    ->wait();
