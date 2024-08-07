<?php

namespace Root\Queue;
//use Workerman\Worker;
use Root\Lib\Worker;
/**
 * @purpose 以后台守护进程模式运行，统一管理
 */
class RtmpConsumer
{

    /**
     * 推流服务
     * @param $param
     * @return void
     */
    public function consume($param)
    {
        if ($param[0]!='stop'){
            $_rtmp_pid = pcntl_fork();
            if ($_rtmp_pid == 0) {
                $this->push($param);exit;
            }
        }else{
            $this->push($param);exit;
        }
    }

    /**
     * 业务逻辑
     * @param $param
     * @return void
     */
    public function push($param){
        global $argv;
        /** 如果是重启 则直接后台守护模式运行 */
        if ($param[0]=='restart'){
           $param = ['start','-d'];
        }
        /** 重构启动参数 */
        $argv = array_merge(['start.php'],$param);
        $safeEcho = G(\Xiaosongshu\ColorWord\Transfer::class);
        $rtmpConfig = config('rtmp')??[];
        $rtmpPort = $rtmpConfig['rtmp']??1935;
        $flvPort = $rtmpConfig['flv']??18080;
        /** 开启一个tcp服务，监听1935端口 */
        $rtmpServer = new  Worker("tcp://0.0.0.0:{$rtmpPort}");
        /** 当客户端连接服务端的时候触发 */
        $rtmpServer->onConnect = function (\Workerman\Connection\TcpConnection $connection)use($safeEcho) {
            echo $safeEcho->info("connection" . $connection->getRemoteAddress() . " connected . \r\n");
            new \MediaServer\Rtmp\RtmpStream(
                new \MediaServer\Utils\WMBufferStream($connection)
            );
        };
        /** 下面是提供flv播放资源的接口 */
        $rtmpServer->onWorkerStart = function ($worker)use($safeEcho,$rtmpPort,$flvPort) {
            echo $safeEcho->info("rtmp server " . $worker->getSocketName() . " start . \r\n");
            \MediaServer\Http\HttpWMServer::$publicPath = __DIR__.'/public';
            $httpServer = new \MediaServer\Http\HttpWMServer("\\MediaServer\\Http\\ExtHttpProtocol://0.0.0.0:{$flvPort}\r\n");
            $httpServer->listen();
            echo $safeEcho->info("rtmp推流地址：rtmp://0.0.0.0:{$rtmpPort}/{your_app_name}/{your_live_room_name}\r\n");
            echo $safeEcho->info("rtmp拉流地址：rtmp://0.0.0.0/{your_app_name}/{your_live_room_name}\r\n");
            echo $safeEcho->info("http-flv地址：http://0.0.0.0:{$flvPort}/{your_app_name}/{your_live_room_name}.flv\r\n");
            echo $safeEcho->info("ws-flv地址：ws://0.0.0.0:{$flvPort}/{your_app_name}/{your_live_room_name}.flv\r\n");
        };
        Worker::runAll();
    }
}