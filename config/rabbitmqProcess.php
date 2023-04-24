<?php
use App\Rabbitmq\Demo;
use App\Rabbitmq\Demo2;
return [
    /** 队列名称 */
    'demoForOne'=>[
        /** 消费者名称 */
        'handler'=>Demo::class
    ],
    /** 队列名称 */
    'otherConsumer'=>[
        /** 消费者名称 */
        'handler'=>Demo2::class,
    ]
];