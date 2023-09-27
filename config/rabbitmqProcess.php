<?php
use App\Rabbitmq\Demo;
use App\Rabbitmq\Demo2;
return [
    /** 队列名称 */
    'demoForOne'=>[
        /** 消费者名称 */
        'handler'=>Demo::class,
        'count'=>2
    ],
    /** 队列名称 */
    'demoForTwo'=>[
        /** 消费者名称 */
        'handler'=>Demo2::class,
        'count'=>1
    ]
];