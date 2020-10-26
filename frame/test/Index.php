<?php

/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/4/6
 * Time: 20:37
 */
class Index
{
    public function get(){
        echo 'index -> get()';
    }

    public function demo(){
        echo Index::get();
    }
}
(new Index)->demo();