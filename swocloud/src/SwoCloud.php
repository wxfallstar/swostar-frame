<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/6/25
 * Time: 15:40
 */

namespace SwoCloud;

use SwoCloud\Server\Route;

/**
 * 启动类
 * Class SwoCloud
 * @package SwoCloud
 */
class SwoCloud
{
    public function run(){
        (new Route)->start();
    }
}