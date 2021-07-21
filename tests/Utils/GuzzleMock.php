<?php


namespace Fanout\Grip\Tests\Utils;


use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;

class GuzzleMock {

    public array $transactions;
    public Client $client;

    public function __construct( $queue ) {

        $mock = new MockHandler($queue);
        $handler_stack = HandlerStack::create( $mock );

        $remove_user_agent = Middleware::mapRequest(function (RequestInterface $request) {
            return $request->withoutHeader('User-Agent');
        });
        $handler_stack->push( $remove_user_agent );

        $this->transactions = [];
        $history = Middleware::history( $this->transactions );
        $handler_stack->push( $history );

        $this->client = new Client([
            'handler' => $handler_stack,
        ]);

    }

}
