<?php


namespace Fanout\Grip\Data\WebSockets;


use Error;
use GuzzleHttp\Psr7\AppendStream;
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

    // Encode the specified array of WebSocketEvent instances. The returned string
    // value should then be passed to a GRIP proxy in the body of an HTTP response
    // when using the WebSocket-over-HTTP protocol.
    /**
     * @param WebSocketEvent[] $events
     * @return StreamInterface
     */
    public static function encode_events( array $events ): StreamInterface {
        $stream = new AppendStream();
        foreach( $events as $event ) {

            $content = $event->get_content();
            if( $content !== null ) {
                if( $content instanceof StreamInterface ) {
                    $content = $content->getContents();
                }

                $stream->addStream( Utils::streamFor( $event->get_type() ) );
                $stream->addStream( Utils::streamFor( " " . strtoupper(dechex( strlen( $content ) )) . "\r\n" ) );
                $stream->addStream( Utils::streamFor( $content ) );
                $stream->addStream( Utils::streamFor( "\r\n" ) );

            } else {
                $stream->addStream( Utils::streamFor( $event->get_type() ) );
                $stream->addStream( Utils::streamFor( "\r\n" ) );
            }

        }

        return $stream;

    }

    // Decode the specified HTTP request body into an array of WebSocketEvent
    // instances when using the WebSocket-over-HTTP protocol. A RuntimeError
    // is raised if the format is invalid.
    /**
     * @param StreamInterface|string $body
     * @return WebSocketEvent[]
     */
    public static function decode_events( $body ): array {
        $events = [];

        $body_is_string = !($body instanceof StreamInterface);
        $body = Utils::streamFor( $body );

        while(true) {
            $type_line = self::read_string_until( $body, "\r\n" );
            if( $type_line === '' ) {
                break;
            }
            if( substr( $type_line, -2 ) !== "\r\n" ) {
                throw new Error('bad format');
            }
            $type_line = substr( $type_line, 0, -2 );

            $pos_space = strpos( $type_line, ' ' );
            if( $pos_space !== false ) {
                $type_string = substr( $type_line, 0, $pos_space );
                $type_length = substr( $type_line, $pos_space + 1 );
                $type_length = hexdec( $type_length );
                $content = $body->read( $type_length );
                if( $body->read(2) !== "\r\n" ) {
                    throw new Error('bad format');
                }
                $e = new WebSocketEvent( $type_string, $body_is_string ? $content : Utils::streamFor( $content ) );

            } else {
                $e = new WebSocketEvent( $type_line );
            }
            $events[] = $e;
        }

        return $events;
    }

    public static function read_string_until( StreamInterface $stream, $needle ): string {

        $pieces = str_split( $needle );
        $pieces_len = count( $pieces );
        $index = 0;

        $buffer = '';

        while( true ) {
            $read_data = $stream->read(1);
            if( $read_data === '' ) {
                return $buffer;
            }

            $buffer .= $read_data;
            if( $index > 0 ) {
                if( $read_data === $pieces[$index] ) {
                    $index = $index + 1;
                    if( $index >= $pieces_len ) {
                        return $buffer;
                    }
                } else {
                    $index = 0;
                }
            }
            if( $read_data === $pieces[$index] ) {
                $index = $index + 1;
                if( $index >= $pieces_len ) {
                    return $buffer;
                }
            }
        }
    }
}
