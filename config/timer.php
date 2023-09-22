<?php
return [

    /** 定时器 */
    'one'=>[
        /** 是否开启 */
        'enable'=>false,
        /** 回调函数 */
        'function'=>[Process\CornTask::class,'handle'],
        /** 周期 */
        'time'=>3,
        /** 是否循环执行 */
        'persist'=>true,
    ],
    'two'=>[
        'enable'=>false,
        'function'=>[Process\CornTask::class,'say'],
        'time'=>5,
        'persist'=>true,
    ],
    'three'=>[
        'enable'=>false,
        'function'=>function(){echo "我是随手一写的匿名函数！\r\n";},
        'time'=>2,
        'persist'=>true,
    ]

];