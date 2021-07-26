<?php


namespace Fanout\Grip\Tests\Utils;


use GuzzleHttp\Psr7\Utils;

class TestStreamData {

    /**
     * @var string
     */
    public static $sample_stream;

    /**
     * @var string
     */
    public static $sample_stream_value_to_base64;

    /**
     * @var string
     */
    public static $sample_iterator_values_to_string;

    /**
     * @var string
     */
    public static $sample_iterator_values_to_base64;

    private const ITERATOR_VALUES = [ 100, 200, 150, 50, 250 ];

    static function __initialize_statics() {
        self::$sample_stream = Utils::streamFor( 'ABC' );
        self::$sample_stream_value_to_base64 = 'QUJD';

        self::$sample_iterator_values_to_string = join( '', array_map( 'chr', self::ITERATOR_VALUES ) );
        self::$sample_iterator_values_to_base64 = 'ZMiWMvo=';
    }

    private static function sample_generator() {
        foreach( self::ITERATOR_VALUES as $value ) {
            yield chr($value);
        }
        return false;
    }

    public static function get_sample_iterator() {
        return self::sample_generator();
    }
}
TestStreamData::__initialize_statics();
