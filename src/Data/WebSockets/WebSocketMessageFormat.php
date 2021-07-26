<?php


namespace Fanout\Grip\Data\WebSockets;


use Fanout\Grip\Data\FormatBase;
use InvalidArgumentException;

class WebSocketMessageFormat extends FormatBase {

    const WEBSOCKET_MESSAGE_BODY_FORMAT_STRING = 'STRING';
    const WEBSOCKET_MESSAGE_BODY_FORMAT_PACK = 'PACK';

    /**
     * @var string|null
     */
    public $content;

    /**
     * @var string
     */
    public $content_format;

    /**
     * @var bool
     */
    public $close;

    /**
     * @var int|null
     */
    public  $code;

    public function __construct( ?string $content, string $content_format = self::WEBSOCKET_MESSAGE_BODY_FORMAT_STRING, $close = false, int $code = null ) {
        if( $close ) {
            if( !is_null($content) ) {
                throw new InvalidArgumentException( 'WebSocketMessageFormat close message cannot have content.' );
            }
        } else {
            if( is_null($content) ) {
                throw new InvalidArgumentException( 'WebSocketMessageFormat requires content.' );
            }
            if( !is_null($code) ) {
                throw new InvalidArgumentException( 'WebSocketMessageFormat can have code only with close.' );
            }
        }
        $this->content = $content;
        $this->content_format = $content_format;
        $this->close = $close;
        $this->code = $code;
    }

    function name(): string {
        return 'ws-message';
    }

    function export(): array {

        $export = [];
        if( $this->close ) {
            $export[ 'action' ] = 'close';
            if( !is_null( $this->code ) ) {
                $export[ 'code' ] = $this->code;
            }
        } else {
            if( $this->content_format === self::WEBSOCKET_MESSAGE_BODY_FORMAT_STRING ) {
                $export[ 'content' ] = $this->content;
            } else {
                $export[ 'content-bin' ] = base64_encode( $this->content );
            }
        }

        return $export;
    }
}
