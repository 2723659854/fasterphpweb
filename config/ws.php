<?php
return [
    'ws1'=>[
        /** 是否开启 */
        'enable'=>true,
        /** 服务类 */
        'handler'=>\Ws\TestWs::class,
        /** 监听ip */
        'host'=>'0.0.0.0',
        /** 监听端口 */
        'port'=>'9502'
    ],
    'ws2'=>[
        'enable'=>true,
        'handler'=>\Ws\TestWs2::class,
        'host'=>'0.0.0.0',
        'port'=>'9504'
    ],
    'ws3'=>[
        'enable'=>true,
        'handler'=>\Ws\Just::class,
        'host'=>'0.0.0.0',
        'port'=>'9503'
    ],
];