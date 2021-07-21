<?php


namespace Fanout\Grip\Errors;


use Error;

class PublishError extends Error {

    public ?array $data;

    public function __construct($message = "", $data = null) {
        parent::__construct($message);
        $this->data = $data;
    }

}
