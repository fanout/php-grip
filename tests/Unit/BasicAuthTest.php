<?php


namespace Fanout\Grip\Tests\Unit\Auth;


use Fanout\Grip\Auth\BasicAuth;
use PHPUnit\Framework\TestCase;

class BasicAuthTest extends TestCase {
    /**
     * @test
     */
    public function shouldConstruct() {

        $basic_auth = new BasicAuth( 'username', 'password' );
        $this->assertSame( 'username', $basic_auth->user );
        $this->assertSame( 'password', $basic_auth->pass );

    }

    /**
     * @test
     */
    public function shouldBuildHeader() {

        $basic_auth = new BasicAuth( 'username', 'password' );

        $this->assertSame( 'Basic ' . base64_encode('username:password'), $basic_auth->build_header() );

    }
}
