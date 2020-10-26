<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/6/25
 * Time: 18:49
 */

namespace SwoCloud\Server;

use Firebase\JWT\JWT;
use Redis;
use SwoCloud\Supper\Arithmetic;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server as SwooleServer;
use Swoole\WebSocket\Server as WebsocketServer;

class Dispatcher
{
    public function register(Route $route, SwooleServer $server, $fd, $data){
        $redis = $route->getRedis();
        $value = \json_encode([
            'ip' => $data['ip'],
            'port' => $data['port']
        ]);
        $serverKey = $route->getServerKey();
        $redis->sadd($serverKey, $value);
        // 这里是通过触发定时判断，不用heartbeat_check_interval 的方式检测
        // 是因为我们还需要主动清空redis 数据
        $server->tick(3000, function ($timer_id, Redis $redis, SwooleServer $server, $serverKey, $fd, $value){
            //判断服务是否正常运行，如果不是就主动清空并把信息从redis中移除
            if(!$server->exist($fd)){
                $redis->sRem($serverKey, $value);
                $server->clearTimer($timer_id);
                dd('im server 宕机');
            }
        }, $redis, $server, $serverKey, $fd, $value);
    }

    public function login(Route $route, Request $request, Response $response){
        $imServer = \json_decode($this->getIMServer($route), true);
        $url = $imServer['ip'].':'.$imServer['port'];
        $uid = $request->post['id'];
        $token = $this->getToken($uid, $url);
        $response->end(\json_encode(['token'=>$token, 'url'=>$url]));
    }

    public function routeBroadcast(Route $route, WebsocketServer $server, $fd, $data){
        dd($data, '接收到im-server client的消息');
//        $ims = $route->getIMServers();
//        $token = $this->getToken(0, $route->getHost().':'.$route->getPort());
//        $header = ['sec-websocket-protocol' => $token];
//        foreach ($ims as $key=>$im){
//            $imInfo = \json_decode($im, true);
//            $route->send($imInfo['ip'], $imInfo['port'], [
//                'method' => 'routeBroadcast',
//                'msg' => $data['msg']
//            ], $header);
//        }
    }

    /**
     * 获取jwt授权token
     * @param $uid
     * @param $url
     * @return string
     */
    protected function getToken($uid, $url){
        $key = "swocloud";
        $time = \time();
        $payload = array(
            "iss" => "http://example.org",
            "aud" => "http://example.com",
            "iat" => $time,
            "nbf" => $time,
            "exp" => $time + (60*60*24),
            "data" => [
                "uid" => $uid,
                "serverUrl" => $url
            ]
        );
        return JWT::encode($payload, $key);
    }

    /**
     * 轮询获取已存在的imserver
     * @param Route $route
     * @return bool
     */
    protected function getIMServer(Route $route){
        $imServer = $route->getRedis()->sMembers($route->getServerKey());
        if(!empty($imServer)){
            return Arithmetic::{$route->getArithmetic()}($imServer);
        }
        return false;
    }

}