<?php
use App\Rabbitmq\Demo;
use App\Rabbitmq\Demo2;
return [
    'demoForOne'=>[
        'handler'=>Demo::class
    ],
    'fuck'=>[
        'handler'=>Demo2::class,
    ]
];