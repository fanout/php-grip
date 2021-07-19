<?php


namespace Fanout\Grip\Data;


use Fanout\Grip\Errors\DuplicateFormatNameError;

class Item {

    /**
     * @var FormatBase[]
     */
    public array $formats;

    public ?string $id;
    public ?string $prev_id;

    /**
     * Item constructor.
     * @param FormatBase|FormatBase[] $formats
     * @param string|null $id
     * @param string|null $prev_id
     */
    public function __construct(
        $formats,
        string $id = null,
        string $prev_id = null
    ) {
        $this->formats = is_array( $formats ) ? $formats : [ $formats ];
        $this->id = $id;
        $this->prev_id = $prev_id;
    }

    function export() {
        $obj = [
            'formats' => [],
        ];

        if( !is_null( $this->id) ) {
            $obj[ 'id' ] = $this->id;
        }
        if( !is_null( $this->prev_id) ) {
            $obj[ 'prev-id' ] = $this->prev_id;
        }

        $known_format_keys = [];
        foreach( $this->formats as $format ) {
            $name = $format->name();
            if( array_key_exists($name, $known_format_keys ) ) {
                throw new DuplicateFormatNameError();
            }
            $known_format_keys[$name] = true;
            $obj[ 'formats' ][ $name ] = $format->export();
        }

        return $obj;
    }

}
