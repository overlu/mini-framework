<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use JsonException;
use Mini\Contracts\HttpMessage\WebsocketControllerInterface;
use Mini\Support\Store;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

/**
 * Class DCS
 * @package Mini\Service\WsServer
 */
class DCS implements WebsocketControllerInterface
{
    /**
     * @param Server $server
     * @param \Swoole\Http\Request $request
     * @param array $routeData
     * @return void
     */
    public function onOpen(Server $server, \Swoole\Http\Request $request, array $routeData): void
    {
        if (!$this->checkAuthCode($routeData['authcode'], $routeData['host'])) {
            ws_abort(401);
        }
    }

    /**
     * @param Server $server
     * @param Frame $frame
     * @param array $routeData
     * @return mixed|void
     * @throws JsonException
     */
    public function onMessage(Server $server, Frame $frame, array $routeData)
    {
        $data = json_decode($frame->data, true, 512, JSON_THROW_ON_ERROR);
        if (!empty($data['dcs_action'])) {
            if ($data['dcs_action'] === 'push') {
                if ($server->exist($data['fd']) && $server->isEstablished($data['fd'])) {
                    $server->push($data['fd'], Socket::transferToResponse($data['data']));
                } else {
                    User::unbind($data['uid'], (int)$data['fd']);
                }
                return;
            }
            if ($data['dcs_action'] === 'close') {
                User::unbind($data['uid'], (int)$data['fd']);
                $server->close($data['fd']);
            }
        }
    }

    /**
     * @param Server $server
     * @param int $fd
     * @param array $routeData
     * @param int $reactorId
     * @return void
     * @throws JsonException
     */
    public function onClose(Server $server, int $fd, array $routeData, int $reactorId):void
    {
        $uids = User::getUserByFd($fd);
        foreach ($uids as $uid) {
            User::unbind($uid, $fd);
        }
    }

    /**
     * 校验合法性
     * @param string $authcode
     * @param string $host
     * @return bool
     */
    public function checkAuthCode(string $authcode, string $host): bool
    {
        $host = base64_decode($host);
        return sha1($host . config('websocket.secret_key')) === $authcode && Store::has(Socket::$host, $host);
    }

    /**
     * 生成url
     * @return string
     */
    public static function generateUrlPath(): string
    {
        return '/' . sha1(config('websocket.host') . ':' . config('websocket.port') . config('websocket.secret_key')) . '/' . base64_encode(config('websocket.host') . ':' . config('websocket.port'));
    }
}