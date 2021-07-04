<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\HttpMessage;

use Swoole\Http\Request;
use Swoole\WebSocket\Server;

interface WebsocketResponseInterface
{
    /**
     * @param $data
     * @param null $fd
     * @return $this
     */
    public function push($data, $fd = null): WebsocketResponseInterface;

    /**
     * @param null $fd
     */
    public function close($fd = null): void;

    /**
     * @return Server
     */
    public function getServer(): Server;

    /**
     * @return Request
     */
    public function getSwooleRequest(): Request;
}
