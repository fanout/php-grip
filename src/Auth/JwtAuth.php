<?php


namespace Fanout\Grip\Auth;


use Firebase\JWT\JWT;
use Throwable;

class JwtAuth implements IAuth {

    /**
     * @var array|null
     */
    public $claim;

    /**
     * @var string|null
     */
    public $key;

    /**
     * @var string|null
     */
    public $token;

    /**
     * JwtAuth constructor.
     * @param array|string $claim_or_token
     * @param string|null $key
     */
    public function __construct( $claim_or_token, string $key = null ) {
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

    static function validate_signature( $token, $key ): bool {
        try {
            $claim = JWT::decode( $token, $key, [ 'HS256' ] );
        } catch( Throwable $ex ) {
            return false;
        }

        return $claim !== null;
    }
}
