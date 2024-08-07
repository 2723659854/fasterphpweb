<?php
return [
    'ws1'=>[
        /** 是否开启 */
        'enable'=>false,
        /** 服务类 */
        'handler'=>\Ws\TestWs::class,
        /** 监听ip */
        'host'=>'0.0.0.0',
        /** 监听端口 */
        'port'=>'9502'
    ],
    'ws2'=>[
        'enable'=>false,
        'handler'=>\Ws\TestWs2::class,
        'host'=>'0.0.0.0',
        'port'=>'9503'
    ],
    'ws3'=>[
        'enable'=>true,
        'handler'=>\Ws\Just::class,
        'host'=>'0.0.0.0',
        'port'=>'9501'
    ],
    'demo'=>[
        'enable'=>false,
        'handler'=>\Ws\Demo::class,
        'host'=>'0.0.0.0',
        'port'=>'8080'
    ]
];