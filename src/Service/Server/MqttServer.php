<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\Server;

use Mini\Service\Server\Protocol\MQTT;
use Swoole\Server;

/**
 * Class MqttServer
 * @package Mini\Service\Server
 */
class MqttServer extends AbstractServer
{
    protected string $type = 'Mqtt';

    public function initialize(): void
    {
        $this->server = new Server($this->config['ip'], $this->config['port'], $this->config['mode']);
    }

    public function onReceive(Server $server, $fd, $fromId, $data): void
    {
        parent::onReceive($server, $fd, $fromId, $data);
        try {
            $data = MQTT::decode($data);
            if (is_array($data) && isset($data['cmd'])) {
                switch ($data['cmd']) {
                    case MQTT::PINGREQ: // 心跳请求
                        [$class, $func] = $this->config['receiveCallbacks'][MQTT::PINGREQ];
                        $obj = new $class();
                        if ($obj->{$func}($server, $fd, $fromId, $data)) {
                            // 返回心跳响应
                            $server->send($fd, MQTT::getAck(['cmd' => 13]));
                        }
                        break;
                    case MQTT::DISCONNECT: // 客户端断开连接
                        [$class, $func] = $this->config['receiveCallbacks'][MQTT::DISCONNECT];
                        $obj = new $class();
                        if ($obj->{$func}($server, $fd, $fromId, $data)) {
                            if ($server->exist($fd)) {
                                $server->close($fd);
                            }
                        }
                        break;
                    case MQTT::CONNECT: // 连接
                    case MQTT::PUBLISH: // 发布消息
                    case MQTT::SUBSCRIBE: // 订阅
                    case MQTT::UNSUBSCRIBE: // 取消订阅
                        [$class, $func] = $this->config['receiveCallbacks'][$data['cmd']];
                        $obj = new $class();
                        $obj->{$func}($server, $fd, $fromId, $data);
                        break;
                }
            } else {
                $server->close($fd);
            }
        } catch (\Exception $e) {
            $server->close($fd);
        }
    }
}
