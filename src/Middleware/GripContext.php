<?php

namespace Fanout\Grip\Middleware;

use Fanout\Grip\Data\GripInstruct;
use Fanout\Grip\Data\WebSockets\WebSocketContext;
use Fanout\Grip\Errors\GripInstructAlreadyStartedError;
use Fanout\Grip\Errors\GripInstructNotAvailableError;

class GripContext {
    /**
     * @var GripMiddleware
     */
    public $grip_middleware;

    /**
     * @var bool
     */
    public $handled;

    /**
     * @var bool
     */
    public $proxied;

    /**
     * @var bool
     */
    public $signed;

    /**
     * @var bool
     */
    public $grip_proxy_required;

    /**
     * @var GripInstruct|null
     */
    private $grip_instruct;

    /**
     * @var WebSocketContext|null
     */
    private $ws_context;

    public function __construct( GripMiddleware $grip_middleware ) {
        $this->grip_middleware = $grip_middleware;
        $this->handled = false;
        $this->proxied = false;
        $this->signed = false;
        $this->grip_proxy_required = false;
        $this->grip_instruct = null;
        $this->ws_context = null;
    }

    public function get_grip_middleware(): GripMiddleware {
        return $this->grip_middleware;
    }

    public function is_handled(): bool {
        return $this->handled;
    }

    public function set_is_handled( $value = true ) {
        return $this->handled = $value;
    }

    public function is_proxied(): bool {
        return $this->proxied;
    }

    public function set_is_proxied( $value = true ) {
        return $this->proxied = $value;
    }

    public function is_signed(): bool {
        return $this->signed;
    }

    public function set_is_signed( $value = true ) {
        return $this->signed = $value;
    }

    public function is_grip_proxy_required(): bool {
        return $this->grip_proxy_required;
    }

    public function set_is_grip_proxy_required( $value = true ) {
        return $this->grip_proxy_required = $value;
    }

    public function has_instruct(): bool {
        return $this->grip_instruct !== null;
    }

    public function get_instruct(): GripInstruct {
        if ($this->grip_instruct !== null) {
            return $this->grip_instruct;
        }
        return $this->start_instruct();
    }

    public function start_instruct(): GripInstruct {
        if (!$this->is_proxied()) {
            throw new GripInstructNotAvailableError();
        }
        if ($this->grip_instruct !== null) {
            throw new GripInstructAlreadyStartedError();
        }
        $this->grip_instruct = new GripInstruct();
        return $this->grip_instruct;
    }

    public function get_ws_context(): ?WebSocketContext {
        return $this->ws_context;
    }

    public function set_ws_context( WebSocketContext $ws_context ) {
        $this->ws_context = $ws_context;
    }
}
