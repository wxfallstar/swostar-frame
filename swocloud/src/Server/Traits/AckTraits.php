<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/7/18
 * Time: 22:24
 */

namespace SwoCloud\Server\Traits;


use co;
use Swoole\Coroutine\Http\Client;
use Swoole\Table;

trait AckTraits
{
    protected $table;

    public function createTable(){
        $this->table = new Table(1024);

        $this->table->column('ack', Table::TYPE_INT, 1);
        $this->table->column('num', Table::TYPE_INT, 1);

        $this->table->create();
    }

    public function confirmGo($unipid, $data, Client $client){
        go(function () use ($unipid, $data,$client){
            while (true){
                Co::sleep(1);
                //获取im-server回复的确认消息
                $ackData = $client->recv(0.2);
                $ackInfo = \json_decode($ackData->data, true);
                //判断类型是否为确认
                if(isset($ackInfo['method']) && $ackInfo['method'] == 'ack'){
                    //确认消息
                    $this->table->incr($ackInfo['msg_id'], 'ack');
                }
                //判断是否任务确认
                //获取任务对应的状态
                $task = $this->table->get($unipid);
                if($task['ack'] > 0 || $task['num'] >= 3){
                    dd('清空任务'.$unipid);
                    $this->table->del($unipid);
                    $client->close();
                    break;
                }else{
                    //重试发送
                    $client->push(\json_encode($data));
                }
                $this->table->incr($unipid, 'num');
                dd('任务重试+1');
            }
        });
    }

    public function confirmTick(){

    }
}