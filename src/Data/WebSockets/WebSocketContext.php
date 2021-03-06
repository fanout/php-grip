<?php


namespace Fanout\Grip\Data\WebSockets;


use Error;
use Fanout\Grip\Errors\ConnectionIdMissingError;
use Fanout\Grip\Errors\WebSocketDecodeEventError;
use GuzzleHttp\Psr7\AppendStream;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use Throwable;

class WebSocketContext {

    const CONTENT_TYPE_WEBSOCKET_EVENTS = 'application/websocket-events';

    public $id;
    public $prefix;

    public $meta;
    private $meta_prev;

    private $accepted;
    private $closed;
    private $out_close_code;
    private $close_code;

    private $in_events;
    private $read_index;
    private $out_events;

    public function __construct( $id, $meta, $in_events, $prefix = ''  ) {

        $this->id = $id;
        $this->prefix = $prefix;
        $this->meta = $meta;
        $this->meta_prev = $meta;

        $this->accepted = false;
        $this->closed = false;
        $this->out_close_code = 0;
        $this->close_code = 0;

        $this->in_events = $in_events;
        $this->read_index = 0;
        $this->out_events = [];
    }

    public function is_opening(): bool {

        if( !is_array( $this->in_events ) || empty( $this->in_events ) ) {
            return false;
        }
        return $this->in_events[0]->type === 'OPEN';

    }

    public function is_accepted(): bool {
        return $this->accepted;
    }

    public function is_closed(): bool {
        return $this->closed;
    }

    public function get_close_code(): int {
        return $this->close_code;
    }

    public static function event_can_be_recv( WebSocketEvent $event ): bool {
        return in_array( $event->type, ['TEXT', 'BINARY', 'CLOSE', 'DISCONNECT'] );
    }

    public function can_recv(): bool {
        if (!empty($this->in_events)) {
            for ($index = $this->read_index; $index < count($this->in_events); $index++) {
                $event = $this->in_events[$index];
                if( self::event_can_be_recv( $event ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    public function accept() {
        $this->accepted = true;
    }

    public function disconnect() {
        $this->out_events[] = new WebSocketEvent('DISCONNECT');
    }

    public function close( $close_code = 0 ) {
        $this->closed = true;
        $this->out_close_code = $close_code;
    }

    /**
     * @param $data StreamInterface|string
     */
    public function send( $data ) {

        $this->out_events[] = new WebSocketEvent(
            'TEXT',
            new AppendStream([
                Utils::streamFor('m:'),
                $data instanceof StreamInterface ? $data : Utils::streamFor($data),
            ])
        );

    }

    /**
     * @param $data StreamInterface
     */
    public function send_binary( StreamInterface $data ) {

        $this->out_events[] = new WebSocketEvent(
            'BINARY',
            new AppendStream([
                Utils::streamFor('m:'),
                $data,
            ])
        );

    }

    /**
     * @param $data StreamInterface|string
     */
    public function send_control( $data ) {

        $this->out_events[] = new WebSocketEvent(
            'TEXT',
            new AppendStream([
                Utils::streamFor('c:'),
                $data instanceof StreamInterface ? $data : Utils::streamFor($data),
            ])
        );

    }

    public function subscribe( $channel ) {

        $this->send_control(
            self::create_web_socket_control_message( 'subscribe', [ 'channel' => $this->prefix . $channel ] )
        );

    }

    public function unsubscribe( $channel ) {

        $this->send_control(
            self::create_web_socket_control_message( 'unsubscribe', [ 'channel' => $this->prefix . $channel ] )
        );

    }

    public function detach() {

        $this->send_control(
            self::create_web_socket_control_message( 'detach' )
        );

    }

    /**
     * @return string|null
     */
    public function recv(): ?string {
        $content = $this->recv_raw();

        if ($content === null) {
            return null;
        }

        return strval( $content );
    }

    public function recv_raw() {
        $event = $this->get_next_in_event();
        if( $event === null ) {
            throw new Error('Read from empty buffer.');
        }

        if ($event->type === 'TEXT') {
            return $event->get_content() !== null ? strval( $event->get_content() ) : '';
        }

        if ($event->type === 'BINARY') {
            return $event->get_content() !== null ? $event->get_content() : Utils::streamFor( null );
        }

        if ($event->type === 'CLOSE') {
            $content = $event->get_content();
            if( $content instanceof StreamInterface ) {
                if ( $content->getSize() === 2 ) {
                    $this->close_code = unpack( 'n', $content->getContents() )[1];
                }
            }
            return null;
        }

        throw new Error('Client disconnected unexpectedly.');
    }

    /**
     * @return WebSocketEvent[]
     */
    public function get_outgoing_events(): array {
        $events = [];
        if( $this->accepted ) {
            $events[] = new WebSocketEvent('OPEN');
        }
        foreach( $this->out_events as $event ) {
            $events[] = $event;
        }
        if( $this->closed ) {
            $events[] = new WebSocketEvent('CLOSE', Utils::streamFor( pack('n', $this->out_close_code ) ));
        }
        return $events;
    }

    public function to_headers() {

        $headers = [
            'Content-Type' => 'application/websocket-events',
        ];

        if($this->accepted) {
            $headers['Sec-WebSocket-Extensions'] = 'grip';
        }

        $meta_lower_keys = [];
        foreach( $this->meta as $key => $value ) {
            $meta_lower_keys[ strtolower( $key ) ] = $value;
        }

        $meta_prev_lower_keys = [];
        foreach( $this->meta_prev as $key => $value ) {
            $meta_prev_lower_keys[ strtolower( $key ) ] = $value;
        }

        $all_meta_keys = array_merge(array_keys($meta_lower_keys), array_keys($meta_prev_lower_keys));
        $all_meta_keys = array_unique( $all_meta_keys );

        foreach( $all_meta_keys as $key ) {
            $new_value = ($meta_lower_keys[ $key ] ?? '');
            $prev_value = ($meta_prev_lower_keys[ $key ] ?? '');
            if( $new_value !== $prev_value ) {
                $headers[ 'Set-Meta-' . $key ] = strval( $new_value );
            }
        }

        return $headers;

    }

    /**
     * @return WebSocketEvent|null
     */
    private function get_next_in_event(): ?WebSocketEvent {

        while( true ) {
            if ($this->read_index > count( $this->in_events )) {
                return null;
            }
            $event = $this->in_events[ $this->read_index ];
            $this->read_index++;
            if( self::event_can_be_recv( $event ) ) {
                return $event;
            }
        }

    }

    // Generate a WebSocket control message with the specified type and optional
    // arguments. WebSocket control messages are passed to GRIP proxies and
    // example usage includes subscribing/unsubscribing a WebSocket connection
    // to/from a channel.
    static function create_web_socket_control_message( $type, $args = [] ) {
        $out = array_merge([], $args, [ 'type' => $type ]);
        return json_encode( $out );
    }

    public static function is_ws_over_http(): bool {
        if( ($_SERVER[ 'REQUEST_METHOD' ] ?? null) !== 'POST' ) {
            return false;
        }

        $content_type = $_SERVER[ 'HTTP_CONTENT_TYPE' ] ?? null;
        if( $content_type !== null ) {
            $semi_pos = strpos( $content_type, ';' );
            if( $semi_pos !== false ) {
                $content_type = substr($content_type, 0, $semi_pos);
            }
            if( $content_type === self::CONTENT_TYPE_WEBSOCKET_EVENTS ) {
                return true;
            }
        }

        $accept = $_SERVER[ 'HTTP_ACCEPT' ] ?? null;
        if( $accept !== null ) {
            $accepts = explode( ',', $accept );
            $accepts = array_map( 'trim', $accepts );
            $accepts = array_filter( $accepts );
            if( in_array( self::CONTENT_TYPE_WEBSOCKET_EVENTS, $accepts ) ) {
                return true;
            }
        }

        return false;
    }

    public static $req_input = null;
    public static function set_input( $value ) {
        self::$req_input = $value;
    }

    public static function from_req( $prefix = '' ): WebSocketContext {
        $connection_id = $_SERVER['HTTP_CONNECTION_ID'] ?? null;
        if( $connection_id === null ) {
            throw new ConnectionIdMissingError();
        }

        $input = self::$req_input ?? fopen('php://input', 'r');
        try {
            $events = WebSocketEvent::decode_events( Utils::streamFor( $input ) );
        } catch( Throwable $ex ) {
            throw new WebSocketDecodeEventError();
        }

        $meta = [];
        foreach( $_SERVER as $key => $value ) {
            $key = strtolower( $key );
            if( substr( $key, 0, 10 ) !== 'http_meta_' ) {
                continue;
            }
            $key = substr( $key, 10 );
            $key = str_replace( '_', '-', $key );
            $meta[ $key ] = $value;
        }

        return new WebSocketContext( $connection_id, $meta, $events, $prefix );
    }
}
