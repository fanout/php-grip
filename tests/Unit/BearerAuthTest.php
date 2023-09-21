<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Auth\BearerAuth;
use PHPUnit\Framework\TestCase;

class BearerAuthTest extends TestCase {
    /**
     * @test
     */
    public function shouldConstruct() {

        $bearer_auth = new BearerAuth( 'token' );
        $this->assertSame( 'token', $bearer_auth->token );

    }

    /**
     * @test
     */
    public function shouldBuildHeader() {

        $bearer_auth = new BearerAuth( 'token' );

        $this->assertSame( 'Bearer ' . 'token', $bearer_auth->build_header() );

    }
}
