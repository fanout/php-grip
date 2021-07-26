<?php


namespace Fanout\Grip\Data\Http;


use Fanout\Grip\Data\FormatBase;

class HttpStreamFormat extends FormatBase {

    const HTTP_RESPONSE_BODY_FORMAT_STRING = 'STRING';
    const HTTP_RESPONSE_BODY_FORMAT_PACK = 'PACK';

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

    public function __construct( ?string $content, ?string $content_format = self::HTTP_RESPONSE_BODY_FORMAT_STRING, $close = false ) {
        $this->content = $content;
        $this->content_format = $content_format;
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
            if( $this->content_format === self::HTTP_RESPONSE_BODY_FORMAT_STRING ) {
                $obj[ 'content' ] = $this->content;
            } else {
                $obj[ 'content-bin' ] = base64_encode( $this->content );
            }
        }

        return $obj;
    }
}
