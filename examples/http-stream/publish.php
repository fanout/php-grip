<?php

use Fanout\Grip\Engine\Publisher;

require_once __DIR__ . '/../../vendor/autoload.php';

$uri = 'http://localhost:5561/';

@[ , $channel, $message ] = $argv;

echo "Publish URI: " . $uri . "\n";
echo "Channel: " . $channel . "\n";
echo "Message: " . $message . "\n";

$publisher = new Publisher([ 'control_uri' => $uri ]);

$publisher->publish_http_stream( $channel, $message . "\n" )
    ->then(function() {
        echo "Publish Successful!\n";
    })
    ->otherwise(function($e) {
        echo "Publish Fail!\n";
        echo json_encode( $e ) . "\n";
    })
    ->wait();
