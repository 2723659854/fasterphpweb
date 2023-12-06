<?php

return [
    /** 队列名称 */
    'demoForOne'=>[
        /** 消费者名称 */
        'handler'=>App\Rabbitmq\Demo::class,
        /** 进程数 */
        'count'=>2,
        /** 是否开启消费者 */
        'enable'=>false,
    ],
    /** 队列名称 */
    'demoForTwo'=>[
        /** 消费者名称 */
        'handler'=>App\Rabbitmq\Demo2::class,
        /** 进程数 */
        'count'=>2,
        /** 是否开启消费者 */
        'enable'=>false,
    ],
    'DemoConsume'=>[
        /** 消费者名称 */
        'handler'=>App\Rabbitmq\DemoConsume::class,
        /** 进程数 */
        'count'=>1,
        /** 是否开启消费者 */
        'enable'=>false,
    ]
];