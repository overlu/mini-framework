<?php
/**
 * This file is part of Mini.
 * @auth lupeng
 */
declare(strict_types=1);

namespace Mini\Service\WsServer;

use Mini\Contracts\HttpMessage\WebsocketControllerInterface;
use Mini\Support\Store;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class DCS implements WebsocketControllerInterface
{
    public function onOpen(Server $server, \Swoole\Http\Request $request, array $routeData)
    {
        if (!$this->checkAuthCode($routeData['authcode'], $routeData['host'])) {
            ws_abort(401);
        }
    }

    public function onMessage(Server $server, Frame $frame, array $routeData)
    {
        $data = json_decode($frame->data, true);
        if (!empty($res['dcs_action'])) {
            if ($res['dcs_action'] === 'push') {
                return $server->push($data['fd'], Socket::transferToResponse($data['data']));
            }
            if ($res['dcs_action'] === 'close') {
                if ($server->exist($data['fd']) && $server->isEstablished($data['fd'])) {
                    $server->close($data['fd']);
                }
                return;
            }
        }
    }

    public function onClose(Server $server, int $fd, array $routeData, int $reactorId)
    {
//        return $reactorId;
    }

    /**
     * 校验合法性
     * @param $authcode
     * @param $host
     * @return bool
     */
    public function checkAuthCode($authcode, $host)
    {
        $host = base64_decode($host);
        return sha1($host . config('websocket.secret_key')) === $authcode && Store::has('websocket_server_hosts', $host);
    }

    /**
     * 生成authcode
     * @return string
     */
    public static function generateUrlPath()
    {
        return '/' . sha1(config('websocket.host') . ':' . config('websocket.port') . config('websocket.secret_key')) . '/' . base64_encode(config('websocket.host') . ':' . config('websocket.port'));
    }
}