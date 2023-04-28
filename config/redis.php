<?php

return [
    /** 是否提前启动缓存连接 */
    'preStart'=>false,
    /** redis队列开关 */
    'enable'=>false,
    /** redis连接基本配置 */
    'host'     => 'redis',
    //'host'     => '192.168.4.105',
    'password' => '',
    'port'     => '6379',
    'database' => 0,
];