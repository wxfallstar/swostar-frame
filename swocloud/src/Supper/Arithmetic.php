<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/6/26
 * Time: 20:44
 */

namespace SwoCloud\Supper;


class Arithmetic
{
    protected static $roundLastIndex = 0;

    public static function round(array $list){
        $index = self::$roundLastIndex;
        $url = $list[$index];
        if($index + 1 > count($list) - 1){
            self::$roundLastIndex = 0;
        }else{
            self::$roundLastIndex++;
        }
        return $url;
    }

    public function hash(){

    }
}