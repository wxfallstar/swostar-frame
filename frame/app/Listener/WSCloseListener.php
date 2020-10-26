<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/6/25
 * Time: 17:39
 */

namespace App\Listener;


use Firebase\JWT\JWT;
use SwoStar\Event\Listener;
use SwoStar\Server\Server;
use Swoole\WebSocket\Server as SwooleServer;
use SwoStar\Server\Websocket\Connections;
use SwoStar\Server\Websocket\WebSocketServer;

/**
 * 事件监听
 * Class StartListener
 * @package App\Listener
 */
class WSCloseListener extends Listener
{
    protected $name = 'ws.close';

    public function handler(WebSocketServer $swoStartServer = null, SwooleServer $server = null, $fd = null){
        $request = Connections::get($fd)['request'];
        $token = $request->header['sec-websocket-protocol'];
        $config = $this->app->make('config');
        $key = $config->get('server.route.jwt.key');
        $jwt = JWT::decode($token, $key,
            $config->get('server.route.jwt.alg'));
        $swoStartServer->getRedis()->hDel($key, $jwt->data->uid);
    }
}