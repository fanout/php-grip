<?php

use Fanout\Grip\Data\GripInstruct;

require_once __DIR__ . '/../../vendor/autoload.php';

$grip_instruct = new GripInstruct('test');
$grip_instruct->set_hold_stream();

$headers = $grip_instruct->build_headers();

foreach( $headers as $key => $value ) {
    header( $key . ': ' . $value );
}

echo "[open stream]\n";
