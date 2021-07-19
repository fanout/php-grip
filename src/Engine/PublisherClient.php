<?php


namespace Fanout\Grip\Engine;

use Fanout\Grip\Auth\BasicAuth;
use Fanout\Grip\Auth\IAuth;
use Fanout\Grip\Auth\JwtAuth;
use Fanout\Grip\Data\Item;
use Fanout\Grip\Utils\StringUtil;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;

// The PublisherClient class allows consumers to publish to an endpoint of
// their choice. The consumer wraps a Format class instance in an Item class
// instance and passes that to the publish method.
class PublisherClient {

    public string $uri;
    public ?IAuth $auth;

    public function __construct( string $uri ) {
        if (StringUtil::string_ends_with( $uri, '/' )) {
            $uri = substr( $uri, 0, strlen( $uri ) - 1 );
        }
        $this->uri = $uri;
        $this->auth = null;
    }

    public function set_auth_basic( string $username, string $password ) {

        $this->auth = new BasicAuth( $username, $password );

    }

    public function set_auth_jwt( ...$params ) {

        $this->auth = new JwtAuth( ...$params );

    }

    public function publish( string $channel, Item $item ): PromiseInterface {
        return new FulfilledPromise( true );
    }

}
