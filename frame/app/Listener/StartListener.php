<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/6/25
 * Time: 17:39
 */

namespace App\Listener;


use SwoStar\Event\Listener;
use SwoStar\Server\Server;

/**
 * 事件监听
 * Class StartListener
 * @package App\Listener
 */
class StartListener extends Listener
{
    protected $name = 'start';

    public function handler(Server $server = null){
        $config = $this->app->make('config');
        go(function() use ($server, $config){
            $cli = new \Swoole\Coroutine\Http\Client($config->get('server.route.server.host'), $config->get('server.route.server.port'));
            if($cli->upgrade("/")){
                $data = [
                    'ip' => $server->getHost(),
                    'port' => $server->getPort(),
                    'ServerName' => 'swostar_im1',
                    'method' => 'register'
                ];
                $cli->push(json_encode($data));
                //定时发送数据保持连接
                \swoole_timer_tick(3000, function () use ($cli){
                    $cli->push('', WEBSOCKET_OPCODE_PING);
                });
                //$cli->close();
            }
        });
    }
}