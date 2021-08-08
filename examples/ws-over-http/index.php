<?php

const CHANNEL_NAME = 'test';

use Fanout\Grip\Data\WebSockets\WebSocketContext;
use Fanout\Grip\Data\WebSockets\WebSocketEvent;
use Fanout\Grip\Errors\ConnectionIdMissingError;

require_once __DIR__ . '/../../vendor/autoload.php';

if( !WebSocketContext::is_ws_over_http() ) {
    http_response_code( 400 );
    echo "[not a websocket request]\n";
    die();
}

try {
    $ws_context = WebSocketContext::from_req();
} catch( ConnectionIdMissingError $ex ) {
    http_response_code( 401 );
    echo "connection-id required\n";
    die();
}

if( $ws_context->is_opening() ) {
    // Open the WebSocket and subscribe to a channel:
    $ws_context->accept();
    $ws_context->subscribe(CHANNEL_NAME);
}

while( $ws_context->can_recv() ) {
    $message = $ws_context->recv();

    if ($message === null) {
        // If return value is undefined then connection is closed
        $ws_context->close();
        break;
    }

    // Echo the message
    $ws_context->send( $message );
}

$headers = $ws_context->to_headers();
foreach( $headers as $key => $value ) {
    header( $key . ': ' . $value );
}

$out_events = $ws_context->get_outgoing_events();
echo WebSocketEvent::encode_events( $out_events );
