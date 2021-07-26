<?php


namespace Fanout\Grip\Data\WebSockets;


use Fanout\Grip\Data\FormatBase;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use Iterator;
use Psr\Http\Message\StreamInterface;

class WebSocketMessageFormat extends FormatBase {

    /**
     * @var StreamInterface|string|null
     */
    public $content;

    /**
     * @var bool
     */
    public $close;

    /**
     * @var int|null
     */
    public  $code;

    /**
     * WebSocketMessageFormat constructor.
     * @param resource|string|int|float|bool|StreamInterface|callable|Iterator|null $content
     * @param bool $close
     * @param int|null $code
     */
    public function __construct( $content, bool $close = false, int $code = null ) {
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
        if( is_null( $content ) || gettype( $content ) === 'string' ) {
            $this->content = $content;
        } else {
            $this->content = Utils::streamFor( $content );
        }
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
            if( !( $this->content instanceof StreamInterface ) ) {
                $export[ 'content' ] = $this->content;
            } else {
                $export[ 'content-bin' ] = base64_encode( $this->content );
            }
        }

        return $export;
    }
}
