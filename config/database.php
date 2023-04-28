<?php
return [

    /** 默认连接方式 */
    'default'=>'mysql',
    /** mysql配置 */
    'mysql'=>[
        /** 是否提前连接MySQL */
        'preStart'=>true,
        /** mysql基本配置 */
        'host'=>'192.168.4.80',
        'username'=>'root',
        'passwd'=>'root',
        'dbname'=>'go_gin_chat',
        'port'=>'3306'
    ],
    //todo 其他类型请自己去实现
];
