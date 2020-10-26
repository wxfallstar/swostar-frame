<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/6/25
 * Time: 15:29
 */

namespace SwoCloud\Server;
use Swoole\Coroutine\Http\Client;
use Swoole\Server as SwooleServer;
use Swoole\WebSocket\Server as SwooleWebSocketServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use SwoStar\Console\Input;

/**
 * 1. 检测IM-server的存活状态
 * 2. 支持权限认证
 * 3. 根据服务器的状态，按照一定的算法，计算出该客户端连接到哪台IM-server，返回给客户端，客户端再去连接到对应的服务端,保存客户端与IM-server的路由关系
 * 4. 如果 IM-server宕机，会自动从Redis中当中剔除
 * 5. IM-server上线后连接到Route，自动加 入Redis(im-server ip:port)
 * 6. 可以接受来自PHP代码、C++程序、Java程序的消息请求，转发给用户所在的IM-server
 * 7. 缓存服务器地址，多次查询redis
 *
 * 是一个websocket
 */
class Route extends Server
{
    //存储连接的redis集合key
    protected $server_key = 'im_server';

    protected $arithmetic = 'round';

    //保存redis客户端
    protected $redis ;

    protected $dispatcher = null;

    public function createServer(){
        $this->swooleServer = new SwooleWebSocketServer($this->host, $this->port);
        Input::info('websocket server 访问：ws://192.168.174.169:'.$this->port);
    }

    public function initEvent(){
        $this->setEvent('sub', [
            'request'=>'onRequest',
            'open' => 'onOpen',
            'message' => 'onMessage',
            'close' => 'onClose'
        ]);
    }

    public function onOpen(SwooleServer $server, $request) {
        dd("onOpen");
    }

    public function onWorkerStart(SwooleServer $server, int $worker_id){
        $this->redis = new \Redis();
        $this->redis->pconnect('127.0.0.1', 6379);
    }

    public function onMessage(SwooleServer $server, $frame) {
        $data = \json_decode($frame->data, true);
        $fd = $frame->fd;
        $this->getDispatcher()->{$data['method']}($this, $server, ...[$fd, $data]);
    }

    public function onRequest(SwooleRequest $request, SwooleResponse $response){
        $uri = $request->server['request_uri'];
        if ($uri == '/favicon.ico') {
            $response->status(404);
            $response->end();
            return null;
        }

        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Methods', 'GET,POST');

        $this->getDispatcher()->{$request->post['method']}($this, $request, $response);
    }

    public function onClose($server, $fd) {
        dd("onClose");
    }

    /**
     * 获取所有连接服务器的信息
     * @return array
     */
    public function getIMServers(){
        return $this->getRedis()->sMembers($this->getServerKey());
    }

    /**
     * 通过http协程客户端发送websocket消息
     * @param $ip
     * @param $port
     * @param $data
     * @param null $header
     */
    public function send($ip, $port, $data, $header = null){
        $unipid = session_create_id();
        $data['msg_id'] = $unipid;
        $cli = new Client($ip, $port);
        empty($header) ?: $cli->setHeaders($header);
        if($cli->upgrade('/')){
            $cli->push(\json_encode($data));
        }
        //发送成功之后调用  是否确认接收
        $this->confirmGo($unipid, $data, $cli);
    }

    /**
     * @return mixed
     */
    public function getDispatcher()
    {
        if(empty($this->dispatcher)){
            $this->dispatcher = new Dispatcher();
        }
        return $this->dispatcher;
    }

    /**
     * @return \Redis
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * @return string
     */
    public function getServerKey(): string
    {
        return $this->server_key;
    }

    /**
     * @return string
     */
    public function getArithmetic(): string
    {
        return $this->arithmetic;
    }

    /**
     * @param string $arithmetic
     */
    public function setArithmetic(string $arithmetic)
    {
        $this->arithmetic = $arithmetic;
    }
}