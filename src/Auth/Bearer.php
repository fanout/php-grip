<?php 

namespace Fanout\Grip\Auth;

class Bearer implements IAuth {

    /** @var string */
    public $token;

    public function __construct( string $token ) {
        $this->token = $token;
    }

    function build_header(): string {
        return "Bearer " . $this->token;
    }
}
