<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/4/2
 * Time: 21:59
 */
require __DIR__.'/../vendor/autoload.php';

use App\App;
use SwoStar\Foundation\Application;
use SwoStar\Index;

//echo (new Index())->index();
//echo (new App())->index();


echo app('index')->index();