<?php
//date_default_timezone_set('Asia/Taipei');
//echo date('Y-m-d H:i:s',1714728665);

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/root/function.php';
$server = \Root\Io\RtmpDemo::instance();
$server->port = 1935 ;
$server->onConnect = function (\Root\rtmp\TcpConnection $connection){
    /** 将传递进来的数据解码 */
    //$buffer = \MediaServer\Utils\WMBufferStream::input($buffer,$socket);
    new \MediaServer\Rtmp\RtmpStream(
        new \MediaServer\Utils\WMBufferStream($connection)
    );
    //fwrite($socket, response('<h1>OK</h1>', 200));
};
$server->start();
