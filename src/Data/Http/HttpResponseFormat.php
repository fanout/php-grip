<?php


namespace Fanout\Grip\Data\Http;


use Fanout\Grip\Data\FormatBase;

class HttpResponseFormat extends FormatBase {

    const HTTP_RESPONSE_BODY_FORMAT_STRING = 'STRING';
    const HTTP_RESPONSE_BODY_FORMAT_PACK = 'PACK';

    public ?string $code = null;
    public ?string $reason = null;
    public ?array $headers = null;

    public ?string $body = null;
    public string $body_format = self::HTTP_RESPONSE_BODY_FORMAT_STRING;

    public function __construct( $params = null ) {

        $this->code = $params['code'] ?? null;
        $this->reason = $params['reason'] ?? null;
        $this->headers = $params['headers'] ?? null;
        $this->body = $params['body'] ?? null;
        $this->body_format = $params['body_format'] ?? self::HTTP_RESPONSE_BODY_FORMAT_STRING;

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
            if ($this->body_format === self::HTTP_RESPONSE_BODY_FORMAT_STRING) {
                $obj[ 'body' ] = $this->body;
            } else {
                $obj[ 'body-bin' ] = base64_encode( $this->body );
            }
        }
        return $obj;
    }
}
