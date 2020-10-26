<?php
/**
 * Created by PhpStorm.
 * User: fallstar
 * Date: 2020/4/12
 * Time: 12:34
 */
return [
    'http'=>[
        'host' => '192.168.174.169',
        'port' => 9000,
        'swoole' => [
            'task_worker_num' => 0
        ]
    ],
    'ws' => [
        'host' => '192.168.174.169', //服务监听ip
        'port' => 9800, //监听端口
        'tcpable'=>1, //是否开启tcp监听
        'enable_http' => true, //是否开启http服务
        'is_handshake' => true,
        'swoole' => [
            'task_worker_num' => 0
        ]
    ],
    "rpc" => [
        'tcpable' => 0, // 1为开启， 0 为关闭
        "host" => "127.0.0.1",
        "port" => 9502,
        "swoole" => [
            "worker_num" => 2
        ]
    ],
    'route' => [
        'server' => [
            'host' => '192.168.174.169',
            'port' => 9500
        ],
        'jwt' => [
            'key' => 'swocloud',
            'alg' => [
                'HS256'
            ]
        ]
    ]
];