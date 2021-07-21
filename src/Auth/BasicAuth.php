<?php


namespace Fanout\Grip\Auth;


class BasicAuth implements IAuth {

    public string $user;
    public string $pass;

    public function __construct( string $user, string $pass ) {
        $this->user = $user;
        $this->pass = $pass;
    }

    function build_header(): string {
        $data = "{$this->user}:{$this->pass}";
        return "Basic " . base64_encode($data);
    }
}
