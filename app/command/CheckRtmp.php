<?php
namespace App\Command;
use Root\Lib\BaseCommand;
/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2023-10-10 09:11:34
 */
class CheckRtmp extends BaseCommand
{

    /** @var string $command 测试直播推流 */
    public $command = 'check:rtmp';

    /** 监听ip */
    public string $host = '0.0.0.0';
    /** rtmp服务监听端口 */
    public int $rtmp_port = 1935;
    /** flv服务监听端口 */
    public int $flv_port = 18080;
    
     /**
     * 配置参数
     * @return void
     */
    public function configure(){
        $this->addArgument('cmd','命令类型：start,stop,reload,restart,status');
        $this->addArgument('damon','是否开启后台运行 -d：后台运行,否则为调试模式');
    }
    
    /**
     * 清在这里编写你的业务逻辑
     * @return void
     * @note 比如 应用名称为 a,直播间名称为 b
     * @note 推流地址：rtmp://127.0.0.1:1935/a/b
     * @note rtmp拉流地址：rtmp://127.0.0.1/a/b
     * @note http-flv播放地址: http://127.0.0.1:18080/a/b.flv
     * @note ws-flv播放地址: ws://127.0.0.1:18080/a/b.flv
     * @note 推流工具 可以使用obs，也可以使用FFmpeg
     * @note
     */
    public function handle()
    {
        /** 开启一个tcp服务，监听1935端口 */
        $rtmpServer = new  \Workerman\Worker('tcp://'.$this->host.':'.$this->rtmp_port);
        /** 当客户端连接服务端的时候触发 */
        $rtmpServer->onConnect = function (\Workerman\Connection\TcpConnection $connection) {
            logger()->info("connection" . $connection->getRemoteAddress() . " connected . ");
            new \MediaServer\Rtmp\RtmpStream(
                new \MediaServer\Utils\WMBufferStream($connection)
            );
        };
        /** 下面是提供flv播放资源的接口 */
        $rtmpServer->onWorkerStart = function ($worker) {
            $this->info("rtmp server " . $worker->getSocketName() . " start . ");
            \MediaServer\Http\HttpWMServer::$publicPath = __DIR__.'/public';
            $httpServer = new \MediaServer\Http\HttpWMServer("\\MediaServer\\Http\\ExtHttpProtocol://".$this->host.":".$this->flv_port);
            $httpServer->listen();
            $this->info("rtmp推流地址：rtmp://{$this->host}:{$this->rtmp_port}/{your_app_name}/{your_live_room_name}");
            $this->info("rtmp拉流地址：rtmp://{$this->host}/{your_app_name}/{your_live_room_name}");
            $this->info("http-flv地址：http://{$this->host}:{$this->flv_port}/{your_app_name}/{your_live_room_name}.flv");
            $this->info("ws-flv地址：ws://{$this->host}:{$this->flv_port}/{your_app_name}/{your_live_room_name}.flv");
        };
        \Workerman\Worker::runAll();
    }
}