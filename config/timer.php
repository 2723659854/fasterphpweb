<?php
return [
    /** 定时器 */
    'one'=>[
        /** 是否开启 */
        'enable'=>false,
        /** 回调函数，调用静态方法 */
        'function'=>[Process\CornTask::class,'handle'],
        /** 周期 */
        'time'=>3,
        /** 是否循环执行 */
        'persist'=>true,
    ],
    'two'=>[
        'enable'=>false,
        /** 调用动态方法 */
        'function'=>[Process\CornTask::class,'say'],
        'time'=>5,
        'persist'=>true,
    ],
    'three'=>[
        'enable'=>false,
        /** 调用匿名函数 */
        'function'=>function(){$time=date('y-m-d H:i:s'); echo "\r\n {$time} 我是随手一敲的匿名函数！\r\n";},
        'time'=>5,
        'persist'=>true,
    ],
];