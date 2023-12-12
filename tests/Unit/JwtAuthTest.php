<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Auth\JwtAuth;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;

class JwtAuthTest extends TestCase {
    /**
     * @test
     */
    public function shouldConstructWithClaimAndKey() {

        $jwt_auth = new JwtAuth( [ 'iss' => 'claim' ], 'key' );
        $this->assertSame( [ 'iss' => 'claim' ], $jwt_auth->claim );
        $this->assertSame( 'key', $jwt_auth->key );

    }


    /**
     * @test
     */
    public function shouldBuildHeaderFromClaimAndKey() {

        $jwt_auth = new JwtAuth( [ 'iss' => 'hello' ], "key==" );

        $header = $jwt_auth->build_header();
        $token_part = substr( $header, 7 );

        $decoded = (array) JWT::decode( $token_part, 'key==', [ 'HS256' ] );
        $this->assertArrayHasKey( 'exp', $decoded );
        $this->assertSame( 'hello', $decoded[ 'iss' ] );

    }


    /**
     * @test
     */
    public function shouldVerifyJwtToken() {
        $token = JWT::encode([
            'claim' => 'hello',
            'exp' => time() + 60 * 60 // 1 hour
        ], 'key==' );
        $this->assertTrue( JwtAuth::validate_signature($token, 'key==') );
    }

    /**
     * @test
     */
    public function shouldFailVerifyExpiredJwtToken() {
        $token = JWT::encode([
            'claim' => 'hello',
            'exp' => time() - 60 * 60 // 1 hour
        ], 'key==' );
        $this->assertFalse( JwtAuth::validate_signature($token, 'key==') );
    }

    /**
     * @test
     */
    public function shouldFailVerifyJwtTokenKeyMismatch() {
        $token = JWT::encode([
            'claim' => 'hello',
            'exp' => time() + 60 * 60 // 1 hour
        ], 'key==' );
        $this->assertFalse( JwtAuth::validate_signature($token, 'key===') );
    }
}
