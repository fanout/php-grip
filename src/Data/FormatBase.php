<?php


namespace Fanout\Grip\Data;


abstract class FormatBase {
    abstract function name(): string;
    abstract function export(): array;
}
