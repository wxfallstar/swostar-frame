<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/4/2
 * Time: 22:05
 */
namespace SwoCloud\Server;

use SwoCloud\Server\Traits\AckTraits;
use Swoole\Server as SwooleServer;

/**
 * 所有服务的父类，主要包含公共操作
 * Class Server
 * @package SwoStar\Server
 */
abstract class Server{
    use AckTraits;
    /**
     * swostar server
     * @var Swoole/Server
     */
    protected $swooleServer;

    protected $host = '0.0.0.0';

    protected $port = 9500;

    protected $watchFile = false;

    /**
     * swoole的相关配置信息
     * @var array
     */
    protected $config = [
        'task_worker_num' => 0,
    ];

    /**
     * 注册的回调事件
     * [
     *   // 这是所有服务均会注册的时间
     *   "server" => [],
     *   // 子类的服务
     *   "sub" => [],
     *   // 额外扩展的回调函数
     *   "ext" => []
     * ]
     *
     * @var array
     */
    protected $event = [
        // 这是所有服务均会注册的时间
        "server" => [
            // 事件   =》 事件函数
            "start"        => "onStart",
            "managerStart" => "onManagerStart",
            "managerStop"  => "onManagerStop",
            "shutdown"     => "onShutdown",
            "workerStart"  => "onWorkerStart",
            "workerStop"   => "onWorkerStop",
            "workerError"  => "onWorkerError",
        ],
        // 子类的服务
        "sub" => [],
        // 额外扩展的回调函数
        // 如 ontart等
        "ext" => []
    ];

    public function __construct(){
        //1、创建swoole server服务
        $this->createServer();

        //3、设置需要注册的回调函数
        $this->initEvent();
        //4、设置swoole的回调函数
        $this->setSwooleEvent();

    }

    /**
     * 创建服务
     */
    protected abstract function createServer();
    /**
     * 初始化监听的事件
     */
    protected abstract function initEvent();

    //通用方法

    public function start(){
        $this->createTable();

        //2、设置配置信息
        $this->swooleServer->set($this->config);
        //5、启动服务
        $this->swooleServer->start();
    }

    /**
     * 设置swoole的回调事件
     */
    protected function setSwooleEvent()
    {
        foreach ($this->event as $type => $events) {
            foreach ($events as $event => $func) {
                $this->swooleServer->on($event, [$this, $func]);
            }
        }
    }

    public function onStart(SwooleServer $server){

    }

    public function onManagerStart(SwooleServer $server){

    }

    public function onManagerStop(SwooleServer $server){

    }

    public function onShutdown(SwooleServer $server){

    }

    public function onWorkerStart(SwooleServer $server, int $worker_id){

    }

    public function onWorkerStop(){

    }

    public function onWorkerError(){

    }

    /**
     * @param array
     *
     * @return static
     */
    public function setEvent($type, $event)
    {
        // 暂时不支持直接设置系统的回调事件
        if ($type == "server") {
            return $this;
        }
        $this->event[$type] = $event;
        return $this;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = array_map($this->config, $config);
        return $this;
    }

    /**
     * @param bool $watchFile
     */
    public function setWatchFile(bool $watchFile)
    {
        $this->watchFile = $watchFile;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }
}