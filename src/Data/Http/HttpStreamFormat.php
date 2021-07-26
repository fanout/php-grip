<?php


namespace Fanout\Grip\Data\Http;


use Fanout\Grip\Data\FormatBase;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;

class HttpStreamFormat extends FormatBase {

    /**
     * @var string|null
     */
    public $content;

    /**
     * @var string|null
     */
    public $content_format;

    /**
     * @var bool
     */
    public $close;

    /**
     * HttpStreamFormat constructor.
     * @param StreamInterface|string|null $content
     * @param bool $close
     */
    public function __construct( $content, bool $close = false ) {

        if( is_null( $content ) || gettype( $content ) === 'string' ) {
            $this->content = $content;
        } else {
            $this->content = Utils::streamFor( $content );
        }

        $this->close = $close;
    }

    function name(): string {
        return 'http-stream';
    }

    function export(): array {
        $obj = [];

        if( $this->close ) {
            $obj[ 'action' ] = 'close';
            $obj[ 'content' ] = '';
        } else if( !is_null( $this->content ) ) {
            if( !( $this->content instanceof StreamInterface ) ) {
                $obj[ 'content' ] = $this->content;
            } else {
                $obj[ 'content-bin' ] = base64_encode( $this->content );
            }
        }

        return $obj;
    }
}
