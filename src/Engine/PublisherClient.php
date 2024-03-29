<?php


namespace Fanout\Grip\Engine;

use Fanout\Grip\Auth\BasicAuth;
use Fanout\Grip\Auth\BearerAuth;
use Fanout\Grip\Auth\IAuth;
use Fanout\Grip\Auth\JwtAuth;
use Fanout\Grip\Data\Item;
use Fanout\Grip\Errors\PublishError;
use Fanout\Grip\Utils\StringUtil;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use Throwable;

// The PublisherClient class allows consumers to publish to an endpoint of
// their choice. The consumer wraps a Format class instance in an Item class
// instance and passes that to the publish method.
class PublisherClient {

    /**
     * @var string
     */
    public $uri;

    /**
     * @var IAuth|null
     */
    public $auth;

    /**
     * @var Client|null
     */
    public static $guzzle_client;

    /**
     * @var string
     */
    public $verify_key;

    public $verify_iss;

    public function __construct( string $uri ) {
        if (StringUtil::string_ends_with( $uri, '/' )) {
            $uri = substr( $uri, 0, strlen( $uri ) - 1 );
        }

        if (!filter_var( $uri, FILTER_VALIDATE_URL ) || (
            !StringUtil::string_starts_with($uri,'http://') &&
            !StringUtil::string_starts_with($uri,'https://')
        )) {
            throw new InvalidArgumentException('uri');
        }

        $this->uri = $uri;
        $this->auth = null;
    }

    public function set_auth_basic( string $username, string $password ) {

        $this->auth = new BasicAuth( $username, $password );

    }

    public function set_auth_jwt( $claim, string $key ) {

        $this->auth = new JwtAuth( $claim, $key );

    }

    public function set_auth_bearer( string $token ) {

        $this->auth = new BearerAuth( $token );

    }

    public function set_verify_iss( $verify_iss ) {

        $this->verify_iss = $verify_iss;

    }

    public function get_verify_iss() {

        return $this->verify_iss;

    }

    public function set_verify_key( $verify_key ) {

        $this->verify_key = $verify_key;

    }

    public function get_verify_key() {

        if( !empty($this->verify_key) ) {
            return $this->verify_key;
        } else if( $this->auth instanceof JwtAuth ) {
            return $this->auth->key;
        } else {
            return null;
        }

    }

    public function publish( string $channel, Item $item ): PromiseInterface {

        // prepare request body
        $export = $item->export();
        $export[ 'channel' ] = $channel;
        $content = [
            'items' => [ $export ],
        ];
        $content_json = json_encode( $content );

        $headers = [
            'Content-Type' => 'application/json',
            'Content-Length' => strval( strlen( $content_json ) ),
        ];
        if( !empty( $this->auth ) ) {
            $headers[ 'Authorization' ] = $this->auth->build_header();
        }

        $url = $this->uri . '/publish/';

        $request = new Request(
            'POST',
            $url,
            $headers,
            $content_json
        );

        return static::$guzzle_client
            ->sendAsync($request, ['http_errors' => false])
            ->otherwise(function($error) {
                throw new PublishError( $error->getMessage(), [ 'status_code' => -1 ]);
            })
            ->then(function($response) {

                /** @var Response $response */
                $status_code = $response->getStatusCode();

                $context = [
                    'status_code' => $status_code,
                    'headers' => $response->getHeaders(),
                ];

                $body = $response->getBody();
                try {
                    $mode = 'end';
                    $data = $body->getContents();
                } catch(Throwable $exception) {
                    $mode = 'close';
                    $data = $exception;
                }

                $context[ 'http_body' ] = $data;
                if ($mode === 'end') {
                    if ($status_code < 200 || $status_code >= 300) {
                        throw new PublishError( is_string($context['http_body']) ? $context['http_body'] : json_encode($context['http_body']), $context );
                    }
                } else {
                    throw new PublishError( 'Connection Closed Unexpectedly', $context );
                }

                return $response;

            });

    }

}
PublisherClient::$guzzle_client = new Client();
