<?php
return [

    /** 默认连接方式 */
    'default'=>'mysql',
    /** mysql配置 */
    'mysql'=>[
        /** 是否提前连接MySQL */
        'preStart'=>false,
        /** mysql基本配置 */
        'host'=>'192.168.110.72',
        'username'=>'root',
        'passwd'=>'root',
        'dbname'=>'demo',
        'port'=>'3306'
    ],
    'mysql2'=>[
        'host'=>yaml('mysql.host'),
        'port'=>yaml('mysql.port'),
        'username'=>yaml('mysql.username'),
        'passwd'=>yaml('mysql.password'),
        'dbname'=>yaml('mysql.dbname','go_gin_chat'),
    ]
    //todo 其他类型请自己去实现
];
