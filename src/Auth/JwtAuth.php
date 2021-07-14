<?php


namespace Fanout\Grip\Auth;


use Firebase\JWT\JWT;

class JwtAuth implements IAuth {

    public ?array $claim;
    public ?string $key;
    public ?string $token;

    /**
     * JwtAuth constructor.
     * @param array|string $claim_or_token
     * @param string|null $key
     */
    public function __construct( mixed $claim_or_token, string $key = null ) {
        if (is_null($key)) {
            $this->claim = null;
            $this->key = null;
            $this->token = $claim_or_token;
        } else {
            $this->claim = $claim_or_token;
            $this->key = $key;
            $this->token = null;
        }
    }

    function build_header(): string {

        if (!is_null($this->token)) {
            $token = $this->token;
        } else {
            $claim = array_merge( $this->claim,
                [
                    'exp' => time() + 60 * 10, // 10 minutes
                ]
            );

            $token = JWT::encode($claim, $this->key);
        }

        return "Bearer " . $token;

    }
}
