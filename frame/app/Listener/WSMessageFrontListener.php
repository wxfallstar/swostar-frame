<?php
/**
 * Created by PhpStorm.
 * User: deepcam
 * Date: 2020/6/30
 * Time: 15:27
 */

namespace App\Listener;


use Swoole\Coroutine\Http\Client;
use SwoStar\Event\Listener;
use SwoStar\Server\Websocket\Connections;
use SwoStar\Server\Websocket\WebSocketServer;
use Swoole\WebSocket\Server as SwooleServer;

class WSMessageFrontListener extends Listener
{
    protected $name = 'ws.message.front';

    public function handler(WebSocketServer $swoStartServer = null, SwooleServer $server = null, $frame = null)
    {
//        {
//            'method' => // 操作类型
//            'msg' => // 消息内容
//            'target' => // 保留，可能有指定的目标点
//        }
        $data = \json_decode($frame->data, true);
        $this->{$data['method']}($swoStartServer, $server, $data, $frame->fd);
    }

    /**
     * 对所有服务器进行信息发送;
     * 通过Route服务器对所有服务器进行广播（不选择当前服务器广播是因为，服务器自己需要执行相应的业务压力会大性能可能影响）
     *
     * @param WebSocketServer|null $swoStartServer
     * @param SwooleServer|null $server
     * @param null $frame
     */
    protected function serverBroadcast(WebSocketServer $swoStartServer, SwooleServer $server, $data, $fd){
        $config = $this->app->make('config');
        //用协程通过route服务器进行广播
        $cli = new Client($config->get('server.route.server.host'), $config->get('server.route.server.port'));
        if($cli->upgrade("/")){
            $cli->push(json_encode([
                'method' => 'routeBroadcast',
                'msg' => $data['msg']
            ]));
        }
    }

    /**
     * 接收Route服务广播的消息
     * @param WebSocketServer $swoStartServer
     * @param SwooleServer $server
     * @param $data
     * @param $fd
     */
    protected function routeBroadcast(WebSocketServer $swoStartServer, SwooleServer $server, $data, $fd){
        $ackData = [
            'method' => 'ack',
            'msg_id' => $data['msg_id']
        ];
        $server->push($fd, \json_encode($ackData));
        $swoStartServer->sendAll(\json_encode($data));
    }

    public function ack(){

    }

    /**
     * 向指定的某一个用户发送消息
     * @param WebSocketServer $swoStartServer
     * @param SwooleServer $server
     * @param $data
     * @param $fd
     */
    protected function privateChat(WebSocketServer $swoStartServer, SwooleServer $server, $data, $fd){
        //获取私聊用户的clientId
        $clientId = $data['clientId'];
        //根据用户的clientId获取对应的服务器信息
        $clientIMServerInfoJson = $swoStartServer->getRedis()->hGet(
            $this->app->make('config')->get('server.route.jwt.key'), $clientId);
        $clientIMServerInfo = \json_decode($clientIMServerInfoJson, true);
        // 获取当前客户端的token
        $request = Connections::get($fd)['request'];
        $token = $request->header['sec-websocket-protocol'];
        $clientIMServerUrl = explode(":", $clientIMServerInfo['url']);
        $swoStartServer->send($clientIMServerUrl[0], $clientIMServerUrl[1], [
            'method' => 'forwarding',
            'msg' => $data['msg'],
            'fd' => $clientIMServerInfo['fd']
        ], [
            'sec-websocket-protocol' => $token
        ]);
    }

    /**
     * 转发私聊信息
     * @param WebSocketServer $swoStarServer
     * @param $swooleServer
     * @param $data
     * @param $fd
     */
    protected function forwarding(WebSocketServer $swoStarServer, $swooleServer ,$data, $fd)
    {
        $swooleServer->push($data['fd'], json_encode(['msg' => $data['msg']]));
    }
}