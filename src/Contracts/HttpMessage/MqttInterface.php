<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Contracts\HttpMessage;

use Swoole\Server;

interface MqttInterface
{
    // 1
    public function onMqConnect(Server $server, int $fd, $fromId, $data);

    // 12
    public function onMqPingreq(Server $server, int $fd, $fromId, $data): bool;

    // 14
    public function onMqDisconnect(Server $server, int $fd, $fromId, $data): bool;

    // 3
    public function onMqPublish(Server $server, int $fd, $fromId, $data);

    // 8
    public function onMqSubscribe(Server $server, int $fd, $fromId, $data);

    // 10
    public function onMqUnsubscribe(Server $server, int $fd, $fromId, $data);
}
