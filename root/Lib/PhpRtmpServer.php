<?php

namespace Root\Lib;

class PhpRtmpServer
{

    public function consume(){
        /** 开启一个tcp服务，监听1935端口 */
        $rtmpServer = new  \Workerman\Worker('tcp://0.0.0.0:1935');
        /** 当客户端连接服务端的时候触发 */
        $rtmpServer->onConnect = function (\Workerman\Connection\TcpConnection $connection) {
            logger()->info("connection" . $connection->getRemoteAddress() . " connected . ");
            new \MediaServer\Rtmp\RtmpStream(
                new \MediaServer\Utils\WMBufferStream($connection)
            );
        };
        /** 下面是提供flv播放资源的接口 */
        $rtmpServer->onWorkerStart = function ($worker) {
            logger()->info("rtmp server " . $worker->getSocketName() . " start . ");
            \MediaServer\Http\HttpWMServer::$publicPath = __DIR__.'/public';
            $httpServer = new \MediaServer\Http\HttpWMServer("\\MediaServer\\Http\\ExtHttpProtocol://0.0.0.0:18080");
            $httpServer->listen();
            logger()->info("http server " . $httpServer->getSocketName() . " start . ");
        };
        echo "rtmp-sever已开启\r\n";

        \Workerman\Worker::runAll();
    }
}