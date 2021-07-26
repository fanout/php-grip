<?php


namespace Fanout\Grip\Data;


use Fanout\Grip\Utils\StringUtil;
use Throwable;

class GripInstruct {

    const KEEPALIVE_DATA_FORMAT_STRING = 'STRING';
    const KEEPALIVE_DATA_FORMAT_PACK = 'PACK';

    /** @var Channel[] */
    public $channels = [];

    /**
     * @var int|null
     */
    public $status = null;

    /**
     * @var string|null
     */
    public $hold = null;

    /**
     * @var int
     */
    public $timeout = 0;

    /**
     * @var string|null
     */
    public $keep_alive_data = null;

    /**
     * @var string
     */
    public $keep_alive_data_format = self::KEEPALIVE_DATA_FORMAT_STRING;

    /**
     * @var int
     */
    public $keep_alive_timeout = 0;

    /**
     * @var string|null
     */
    public $next_link_value = null;

    /**
     * @var int
     */
    public $next_link_timeout = 0;

    /**
     * @var array
     */
    public $meta = [];

    public function __construct( $channels = null ) {

        if (!is_array($channels)) {
            $channels = [ $channels ];
        }

        foreach( $channels as $channel ) {
            if (is_null($channel)) {
                continue;
            }
            $this->add_channel( $channel );
        }

    }

    public function add_channel( $channel ) {
        if ($channel instanceof Channel) {
            $this->channels[] = $channel;
        } else {
            $this->channels[] = new Channel($channel);
        }
    }

    public function set_status( int $status ) {
        $this->status = $status;
    }

    public function set_hold_long_poll( $timeout_secs = null ) {
        $this->hold = 'response';
        if (!is_null($timeout_secs)) {
            $this->timeout = $timeout_secs;
        }
    }

    public function set_hold_stream() {
        $this->hold = 'stream';
    }

    public function set_keep_alive( string $data, string $data_format, int $timeout_secs ) {
        $this->keep_alive_data = $data;
        $this->keep_alive_data_format = $data_format;
        $this->keep_alive_timeout = $timeout_secs;
    }

    public function set_next_link( ?string $value, int $timeout_secs = 0 ) {
        $this->next_link_value = $value;
        $this->next_link_timeout = $timeout_secs;
    }

    private function build_grip_channel_header_value(): string {
        $segments = [];

        foreach( $this->channels as $channel ) {
            $export = $channel->export();
            $segment = $export[ 'name' ];
            if (array_key_exists('prev_id', $export)) {
                $segment .= '; prev-id=' . $export[ 'prev_id' ];
            }
            $segments[] = $segment;
        }

        return join(', ', $segments );
    }

    private function build_keep_alive_header(): string {

        $output = null;
        if( $this->keep_alive_data_format === self::KEEPALIVE_DATA_FORMAT_STRING ) {
            try {
                $output = StringUtil::encode_cstring( $this->keep_alive_data );
                $output .= '; format=cstring';
            } catch( Throwable $ex ) {
                $output = null;
            }
        }

        if( is_null( $output ) ) {
            $output = base64_encode( $this->keep_alive_data );
            $output .= '; format=base64';
        }

        $output .= '; timeout=' . strval( $this->keep_alive_timeout );

        return $output;
    }

    private function build_meta_header(): string {

        return join(
            ', ',
            array_map(
                function( $key, $value ) {
                    return $key . '="' . StringUtil::escape_quotes($value) . '"';
                },
                array_keys( $this->meta ),
                array_values( $this->meta )
            )
        );

    }

    private function build_link_header(): string {
        $output = "<{$this->next_link_value}>; rel=next";
        if( $this->next_link_timeout > 0 ) {
            $output .= '; timeout=' . strval( $this->next_link_timeout );
        }
        return $output;
    }

    public function build_headers(): array {
        $headers = [];
        $headers[ 'Grip-Channel' ] = $this->build_grip_channel_header_value();
        if( !is_null( $this->status ) ) {
            $headers[ 'Grip-Status' ] = strval( $this->status ); // Convert to string
        }
        if( !is_null( $this->hold ) ) {
            $headers[ 'Grip-Hold' ] = $this->hold;
            if( $this->timeout > 0 ) {
                $headers[ 'Grip-Timeout' ] = strval( $this->timeout ); // Convert to string
            }
            if( !is_null( $this->keep_alive_data ) ) {
                $headers[ 'Grip-Keep-Alive' ] = $this->build_keep_alive_header();
            }
            if( !empty( $this->meta ) ) {
                $headers[ 'Grip-Set-Meta' ] = $this->build_meta_header();
            }
        }
        if( !is_null( $this->next_link_value ) ) {
            $headers[ 'Grip-Link' ] = $this->build_link_header();
        }
        return $headers;
    }

}
