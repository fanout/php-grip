<?php


namespace Fanout\Grip\Engine;


use Fanout\Grip\Utils\ArrayUtil;
use Fanout\Grip\Utils\GripUriUtil;

class Publisher {

    /** @var PublisherClient[]  */
    public array $clients;

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
                'key' => $key
            ] = $config_entry;
            $client = new PublisherClient( $control_uri );
            if( !empty($control_iss) ) {
                $client->set_auth_jwt( [ 'iss' => $control_iss, ], $key );
            }

            $this->add_client( $client );
        }

    }

    public function add_client( PublisherClient $client ) {
        $this->clients[] = $client;
    }

}
