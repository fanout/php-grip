<?php


namespace Fanout\Grip\Tests\Unit;


use Fanout\Grip\Auth\BasicAuth;
use Fanout\Grip\Auth\BearerAuth;
use Fanout\Grip\Auth\JwtAuth;
use Fanout\Grip\Data\FormatBase;
use Fanout\Grip\Data\Item;
use Fanout\Grip\Engine\PublisherClient;
use Fanout\Grip\Errors\PublishError;
use Fanout\Grip\Tests\Utils\GuzzleMock;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

class TestFormat extends FormatBase {
    /**
     * @var string
     */
    public $content;

    public function __construct( string $content ) {
        $this->content = $content;
    }

    function name(): string {
        return 'test-format';
    }

    function export(): array {
        return [ 'content' => $this->content ];
    }
}

class StreamThatFails extends Stream {
    public function __construct() {
        $stream = Utils::tryFopen('php://temp', 'r+');
        fwrite($stream, 'foo');
        fseek($stream, 0);
        parent::__construct($stream);
    }

    function getContents(): string {
        throw new RuntimeException('Fail');
    }
}

class PublisherClientTest extends TestCase {

    /**
     * @test
     */
    function shouldConstructWithUriOnly() {
        $client = new PublisherClient( 'http://uri' );

        $this->assertEquals( 'http://uri', $client->uri );
        $this->assertNull( $client->auth );
    }

    /**
     * @test
     */
    function shouldConstructWithUriWithTrailingSlash() {
        $client = new PublisherClient( 'http://uri/' );

        $this->assertEquals( 'http://uri', $client->uri );
        $this->assertNull( $client->auth );
    }

    /**
     * @test
     */
    function shouldSetBasicAuth() {
        $client = new PublisherClient( 'http://uri' );
        $client->set_auth_basic( 'user', 'pass' );

        $this->assertInstanceOf( BasicAuth::class, $client->auth );

        /** @var BasicAuth $auth */
        $auth = $client->auth;
        $this->assertEquals( 'user', $auth->user );
        $this->assertEquals( 'pass', $auth->pass );
    }

    /**
     * @test
     */
    function shouldSetJwtAuthWithClaimAndKey() {
        $client = new PublisherClient( 'http://uri' );

        $claim = [ 'iss' => 'iss' ];
        $client->set_auth_jwt( $claim, 'key' );

        $this->assertInstanceOf( JwtAuth::class, $client->auth );

        /** @var JwtAuth $auth */
        $auth = $client->auth;
        $this->assertEquals( [ 'iss' => 'iss' ], $auth->claim );
        $this->assertEquals( 'key', $auth->key );
    }

    /**
     * @test
     */
    function shouldSetBearerAuth() {
        $client = new PublisherClient( 'http://uri' );

        $client->set_auth_bearer( 'token' );

        $this->assertInstanceOf( BearerAuth::class, $client->auth );

        /** @var BearerAuth $auth */
        $auth = $client->auth;
        $this->assertEquals( 'token', $auth->token );
    }


    /**
     * @test
     */
    function shouldNotSetVerifyComponentsIfNotSet() {
        $client = new PublisherClient( 'http://uri' );
        $client->set_auth_bearer( 'token' );

        $this->assertEmpty($client->verify_iss);
        $this->assertEmpty($client->verify_key);
    }

    /**
     * @test
     */
    function shouldSetVerifyIss() {
        $client = new PublisherClient( 'http://uri' );
        $client->set_auth_bearer( 'token' );
        $client->set_verify_iss( 'v_iss' );

        $this->assertEquals( 'v_iss', $client->get_verify_iss() );
    }

    /**
     * @test
     */
    function shouldSetVerifyKey() {
        $client = new PublisherClient( 'http://uri' );
        $client->set_auth_bearer( 'token' );
        $client->set_verify_key( 'v_key' );

        $this->assertEquals( 'v_key', $client->get_verify_key() );
    }

    function shouldHaveVerifyKeyIfJwtAuthSet() {
        $client = new PublisherClient( 'http://uri' );
        $claim = [ 'iss' => 'iss' ];
        $client->set_auth_jwt( $claim, 'key' );

        $this->assertEquals( 'key', $client->get_verify_key() );
    }

    /**
     * @test
     */
    function shouldPublishUseAuthHeader() {
        $guzzle_mock = new GuzzleMock([
            new Response(200, [], 'result'),
        ]);
        PublisherClient::$guzzle_client = $guzzle_mock->client;

        $client = new PublisherClient( 'http://uri' );
        $client->set_auth_basic( 'user', 'pass' );

        $item = new Item( new TestFormat( 'body' ) );

        $client->publish( 'channel', $item )
            ->then(function() use ($guzzle_mock, $item) {

                $export = $item->export();
                $export[ 'channel' ] = 'channel';
                $content = [
                    'items' => [ $export ],
                ];
                $content_json = json_encode( $content );

                $this->assertCount( 1, $guzzle_mock->transactions );

                $transaction = $guzzle_mock->transactions[0];

                /**
                 * @var $request RequestInterface
                 */
                $request = $transaction[ 'request' ];

                $this->assertEquals( 'POST' , $request->getMethod() );
                $this->assertEquals( 'http://uri/publish/', $request->getUri() );
                $this->assertEquals( [
                    'Content-Type' => [ 'application/json' ],
                    'Content-Length' => [ strval( strlen( $content_json ) ) ],
                    'Authorization' => [ 'Basic ' . base64_encode( 'user:pass' ) ],
                    'Host' => [ 'uri' ],
                ], $request->getHeaders() );
                $this->assertEquals( $content_json, (string) $request->getBody() );

            })
            ->wait();

    }

    /**
     * @test
     */
    function shouldPublishWithoutAuthHeader() {

        $guzzle_mock = new GuzzleMock([
            new Response(200, [], 'result'),
        ]);
        PublisherClient::$guzzle_client = $guzzle_mock->client;

        $client = new PublisherClient( 'http://uri' );

        $item = new Item( new TestFormat( 'body' ) );

        $client->publish( 'channel', $item )
            ->then(function() use ($guzzle_mock, $item) {

                $export = $item->export();
                $export[ 'channel' ] = 'channel';
                $content = [
                    'items' => [ $export ],
                ];
                $content_json = json_encode( $content );

                $this->assertCount( 1, $guzzle_mock->transactions );

                $transaction = $guzzle_mock->transactions[0];

                /**
                 * @var $request RequestInterface
                 */
                $request = $transaction[ 'request' ];

                $this->assertEquals( 'POST' , $request->getMethod() );
                $this->assertEquals( 'http://uri/publish/', $request->getUri() );
                $this->assertEquals( [
                    'Content-Type' => [ 'application/json' ],
                    'Content-Length' => [ strval( strlen( $content_json ) ) ],
                    'Host' => [ 'uri' ],
                ], $request->getHeaders() );
                $this->assertEquals( $content_json, (string) $request->getBody() );

            })
            ->wait();

    }

    /**
     * @test
     */
    function shouldPublishWithErrorCode() {
        $guzzle_mock = new GuzzleMock([
            new Response(500, [], 'fail'),
        ]);
        PublisherClient::$guzzle_client = $guzzle_mock->client;

        $client = new PublisherClient( 'http://uri' );

        $item = new Item( new TestFormat( 'body' ) );

        $client->publish( 'channel', $item )
            ->otherwise(function( $error ) use ($guzzle_mock, $item) {

                $this->assertInstanceOf( PublishError::class, $error );
                /** @var PublishError $error */
                $this->assertEquals( 'fail', $error->getMessage() );

            })
            ->wait();

    }

    /**
     * @test
     */
    function shouldPublishWithConnectionError() {
        $guzzle_mock = new GuzzleMock([
            new RequestException( 'Connection Error', new Request( 'GET', '/' )),
        ]);
        PublisherClient::$guzzle_client = $guzzle_mock->client;

        $client = new PublisherClient( 'http://uri' );

        $item = new Item( new TestFormat( 'body' ) );

        $client->publish( 'channel', $item )
            ->otherwise(function( $error ) use ($guzzle_mock, $item) {

                $this->assertInstanceOf( PublishError::class, $error );
                /** @var PublishError $error */
                $this->assertEquals( 'Connection Error', $error->getMessage() );
                $this->assertEquals( [ 'status_code' => -1 ], $error->data );

            })
            ->wait();

    }

    /**
     * @test
     */
    function shouldPublishWithStreamThatFails() {
        $guzzle_mock = new GuzzleMock([
            new Response(200, [], new StreamThatFails()),
        ]);
        PublisherClient::$guzzle_client = $guzzle_mock->client;

        $client = new PublisherClient( 'http://uri' );

        $item = new Item( new TestFormat( 'body' ) );

        $client->publish( 'channel', $item )
            ->otherwise(function( $error ) use ($guzzle_mock, $item) {

                $this->assertInstanceOf( PublishError::class, $error );
                /** @var PublishError $error */
                $this->assertEquals( 'Connection Closed Unexpectedly', $error->getMessage() );
                $this->assertEquals( 200, $error->data[ 'status_code' ] );
                $this->assertInstanceOf( RuntimeException::class, $error->data[ 'http_body' ] );

            })
            ->wait();

    }

    /**
     * @test
     */
    function shouldPublisherClientRejectInvalidURI() {
        $this->expectException(InvalidArgumentException::class);
        new PublisherClient( 'uri' );
    }

}
