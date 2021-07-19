<?php


namespace Fanout\Grip\Errors;


use Throwable;

class CStringEncodeInvalidInputError extends \Error {
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
