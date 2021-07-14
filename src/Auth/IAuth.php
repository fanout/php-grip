<?php

namespace Fanout\Grip\Auth;

interface IAuth {
    function build_header(): string;
}
