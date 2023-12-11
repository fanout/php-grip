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
     * JwtAuth constructor.
     * @param array $claim
     * @param string|null $key
     */
    public function __construct( $claim, string $key ) {
        $this->claim = $claim;
        $this->key = $key;
    }

    function build_header(): string {
        $claim = array_merge( $this->claim,
            [
                'exp' => time() + 60 * 10, // 10 minutes
            ]
        );

        $token = JWT::encode($claim, $this->key);

        return "Bearer " . $token;
    }

    static function validate_signature( $token, $key, $iss = null): bool {
        try {
            $claim = JWT::decode( $token, $key, [ 'HS256' ] );
        } catch( Throwable $ex ) {
            return false;
        }

        if( $iss !== NULL && ( !property_exists( $claim,'iss' ) || $claim->iss !== $iss ) ) {
            return false;
        }
        return $claim !== null;
    }
}
