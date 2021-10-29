<?php

namespace Fanout\Grip\Errors;

use Error;

class GripInstructNotAvailableError extends Error {
    public function __construct() {
        parent::__construct('GripInstruct Not Available');
    }
}
