<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/6/27
 * Time: 13:08
 */

namespace App\Listener;


use Firebase\JWT\JWT;
use Swoole\Http\Request;
use Swoole\Http\Response;
use SwoStar\Event\Listener;
use SwoStar\Server\Websocket\WebSocketServer;

class HandShakeListener extends Listener
{
    protected $name = 'ws.handshake';

    public function handler(WebSocketServer $server = null, Request $request = null, Response $response = null)
    {
        //用来接收websocket传递的token
        $token = $request->header['sec-websocket-protocol'];
        //进行用户的校验
        if(empty($token) || !($this->check($server, $token, $request->fd))){
            $response->end();
            return false;
        }

        //websocket的握手过程
        $this->handshake($request, $response);
    }

    protected function check(WebSocketServer $server, $token, $fd){
        try{
            $config = $this->app->make('config');
            $key = $config->get('server.route.jwt.key');
            $jwt = JWT::decode($token, $key,
                $config->get('server.route.jwt.alg'));
            $userInfo = $jwt->data;
            //存储信息到redis
            $server->getRedis()->hSet($key, $userInfo->uid, \json_encode([
                'fd' => $fd,
                'url' => $userInfo->serverUrl
            ]));
            return true;
        } catch (\Exception $e){
            return false;
        }
    }

    protected function handshake(Request $request, Response $response){
        // websocket握手连接算法验证
        $secWebSocketKey = $request->header['sec-websocket-key'];
        $patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
        if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
            $response->end();
            return false;
        }
        //echo $request->header['sec-websocket-key'];
        $key = base64_encode(
            sha1(
                $request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
                true
            )
        );

        $headers = [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Accept' => $key,
            'Sec-WebSocket-Version' => '13',
        ];

        // WebSocket connection to 'ws://127.0.0.1:9502/'
        // failed: Error during WebSocket handshake:
        // Response must not include 'Sec-WebSocket-Protocol' header if not present in request: websocket
        if (isset($request->header['sec-websocket-protocol'])) {
            $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
        }

        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }

        $response->status(101);
        $response->end();
    }
}