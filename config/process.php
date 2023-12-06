<?php
/** 这里配置你的自定义进程 */
return [
    'abc'=>[
        'enable'=>true,
        'handler'=>\Process\Demo::class,
        'count'=>2,
        'host'=>'0.0.0.0',
        'port'=>8503
    ],
    'def'=>[
        'enable'=>true,
        'handler'=>\Process\Foo::class,
        'count'=>2,
        'host'=>'0.0.0.0',
        'port'=>8504
    ]
];