<?php


namespace Fanout\Grip\Data\WebSockets;


use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;

class WebSocketEvent {

    /**
     * @var string
     */
    public $type;

    /**
     * @var StreamInterface|string|null
     */
    public $content;

    public function __construct( string $type, $content = null ) {
        $this->type = $type;
        if( is_null( $content ) || gettype( $content ) === 'string' ) {
            $this->content = $content;
        } else {
            $this->content = Utils::streamFor( $content );
        }
    }

    public function get_type(): string {
        return $this->type;
    }

    public function get_content() {
        return $this->content;
    }
}
