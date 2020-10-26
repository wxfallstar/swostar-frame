<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/4/19
 * Time: 16:21
 */

namespace App\WebSocket\Controller;


class IndexController
{
    public function open($server, $request) {
        dd('IndexController open');
    }

    public function message($server, $frame) {
        //$server->push($frame->fd, "this is server");
    }

    public function close($ser, $fd) {

    }
}