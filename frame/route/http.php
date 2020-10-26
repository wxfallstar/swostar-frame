<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/4/5
 * Time: 20:07
 */
use SwoStar\Routes\Route;

Route::get('index', function(){
    return 'test index route';
});

Route::get('/index/dd', 'IndexController@dd');