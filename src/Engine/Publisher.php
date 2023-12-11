<?php


namespace Fanout\Grip\Engine;


use Fanout\Grip\Data\FormatBase;
use Fanout\Grip\Data\Http\HttpResponseFormat;
use Fanout\Grip\Data\Http\HttpStreamFormat;
use Fanout\Grip\Data\Item;
use Fanout\Grip\Data\WebSockets\WebSocketMessageFormat;
use Fanout\Grip\Utils\ArrayUtil;
use Fanout\Grip\Utils\GripUriUtil;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;

class Publisher {

    /** @var PublisherClient[]  */
    public $clients;

    /**
     * Publisher constructor.
     * @param string|array $config
     */
    public function __construct( $config = [] ) {
        $this->clients = [];
        $this->apply_config( $config );
    }

    public function apply_config( $config ) {

        $configs = ArrayUtil::is_numeric_array( $config ) ? $config : [ $config ];
        foreach( $configs as $config_entry ) {
            if( is_string( $config_entry ) ) {
                $config_entry = GripUriUtil::parse( $config_entry );
            }
            @[
                'control_uri' => $control_uri,
                'control_iss' => $control_iss,
                'key' => $key,
                'verify_key' => $verify_key,
                'verify_iss' => $verify_iss
            ] = $config_entry;
            $client = new PublisherClient( $control_uri );
            if( !empty($control_iss) ) {
                if( empty( $key ) ) {
                    $key = '';
                }
                $client->set_auth_jwt( [ 'iss' => $control_iss, ], $key );
            } else if ( !empty( $key ) ) {
                $client->set_auth_bearer( $key );
            }

            if( !empty($verify_iss) ) {
                $client->verify_iss = $verify_iss;
            }
            if( !empty($verify_key) ) {
                $client->verify_key = $verify_key;
            }

            $this->add_client( $client );
        }

    }

    public function add_client( PublisherClient $client ) {
        $this->clients[] = $client;
    }

    public function publish( string $channel, Item $item ): PromiseInterface {
        $promises = array_map( function(PublisherClient $client) use ($channel, $item) {
            return $client->publish( $channel, $item );
        }, $this->clients );
        return Utils::all( $promises );
    }

    /**
     * @param string $channel
     * @param FormatBase|FormatBase[] $formats
     * @param string|null $id
     * @param string|null $prev_id
     * @return PromiseInterface
     */
    public function publish_formats( string $channel, $formats, ?string $id = null, ?string $prev_id = null ): PromiseInterface {
        return $this->publish( $channel, new Item( $formats, $id, $prev_id ) );
    }

    /**
     * @param string $channel
     * @param HttpResponseFormat|string $data
     * @param string|null $id
     * @param string|null $prev_id
     * @return PromiseInterface
     */
    public function publish_http_response(string $channel, $data, ?string $id = null, ?string $prev_id = null ): PromiseInterface {
        $data = $data instanceof HttpResponseFormat ? $data : new HttpResponseFormat([ 'body' => $data ]);
        return $this->publish_formats( $channel, $data, $id, $prev_id );
    }

    /**
     * @param string $channel
     * @param HttpStreamFormat|string $data
     * @param string|null $id
     * @param string|null $prev_id
     * @return PromiseInterface
     */
    public function publish_http_stream(string $channel, $data, ?string $id = null, ?string $prev_id = null ): PromiseInterface {
        $data = $data instanceof HttpStreamFormat ? $data : new HttpStreamFormat($data);
        return $this->publish_formats( $channel, $data, $id, $prev_id );
    }

    /**
     * @param string $channel
     * @param WebSocketMessageFormat|string $data
     * @param string|null $id
     * @param string|null $prev_id
     * @return PromiseInterface
     */
    public function publish_websocket_message(string $channel, $data, ?string $id = null, ?string $prev_id = null ):  PromiseInterface {
        $data = $data instanceof WebSocketMessageFormat ? $data : new WebSocketMessageFormat( $data );
        return $this->publish_formats( $channel, $data, $id, $prev_id );
    }
}
