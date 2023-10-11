<?php
namespace App\Command;
use Workerman\Worker;
use Root\Lib\BaseCommand;
/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2023-10-10 09:11:34
 */
class Demo extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'check:rtmp';
    
     /**
     * 配置参数
     * @return void
     */
    public function configure(){
    }
    
    /**
     * 清在这里编写你的业务逻辑
     * @return void
     */
    public function handle()
    {

        $this->info("请在这里编写你的业务逻辑");
        /** 从下面的代码可以可以得出结论，开两个进程 */

        /** 开启一个tcp服务，监听1935端口 */
        $rtmpServer = new  Worker('tcp://0.0.0.0:1935');
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
            $httpServer = new \MediaServer\Http\HttpWMServer("\\MediaServer\\Http\\ExtHttpProtocol://127.0.0.1:18080");
            $httpServer->listen();
            logger()->info("http server " . $httpServer->getSocketName() . " start . ");
        };
        echo "rtmp-sever已开启\r\n";

        \Workerman\Worker::runAll();

    }
}