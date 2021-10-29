<?php

namespace Fanout\Grip\Engine;

use Fanout\Grip\Data\Item;
use GuzzleHttp\Promise\PromiseInterface;

class PrefixedPublisher extends Publisher {
    /**
     * @var string
     */
    private $prefix;

    public function __construct( $config = [], string $prefix = null ) {
        parent::__construct( $config );
        $this->prefix = $prefix ?? '';
    }

    public function publish( string $channel, Item $item ): PromiseInterface {
        return parent::publish( $this->prefix . $channel, $item );
    }
}
