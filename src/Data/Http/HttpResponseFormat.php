<?php


namespace Fanout\Grip\Data\Http;


use Fanout\Grip\Data\FormatBase;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;

class HttpResponseFormat extends FormatBase {

    /**
     * @var string|null
     */
    public $code = null;

    /**
     * @var string|null
     */
    public $reason = null;

    /**
     * @var array|null
     */
    public $headers = null;

    /**
     * @var StreamInterface|string|null
     */
    public $body = null;

    /**
     * HttpResponseFormat constructor.
     * @param array|null $params {
     *   @type string|null $code
     *   @type string|null $reason
     *   @type array|null $headers
     *   @type StreamInterface|string|null $body
     * }
     */
    public function __construct( array $params = null ) {

        $this->code = $params['code'] ?? null;
        $this->reason = $params['reason'] ?? null;
        $this->headers = $params['headers'] ?? null;

        $body = $params['body'] ?? null;
        if( is_null($body) || gettype( $body ) === 'string' ) {
            $this->body = $body;
        } else {
            $this->body = Utils::streamFor( $body );
        }

    }


    function name(): string {
        return 'http-response';
    }

    function export(): array {
        $obj = [];
        if( !is_null( $this->code ) ) {
            $obj[ 'code' ] = $this->code;
        }
        if( !is_null( $this->reason ) ) {
            $obj[ 'reason' ] = $this->reason;
        }
        if( !is_null( $this->headers ) ) {
            $obj[ 'headers' ] = $this->headers;
        }
        if( !is_null( $this->body ) ) {
            if( !($this->body instanceof StreamInterface ) ) {
                $obj[ 'body' ] = $this->body;
            } else {
                $obj[ 'body-bin' ] = base64_encode( $this->body );
            }
        }
        return $obj;
    }
}
