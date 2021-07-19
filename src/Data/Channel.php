<?php


namespace Fanout\Grip\Data;


class Channel {

    public string $name;
    public ?string $prev_id;

    public function __construct( string $name, string $prev_id = null ) {
        $this->name = $name;
        $this->prev_id = $prev_id;
    }

    public function export() {
        $export = [
            'name' => $this->name,
        ];
        if (!is_null($this->prev_id)) {
            $export[ 'prev_id' ] = $this->prev_id;
        }

        return $export;
    }

}
