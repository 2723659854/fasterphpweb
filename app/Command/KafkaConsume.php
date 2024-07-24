<?php
namespace App\Command;

use Monolog\Handler\StreamHandler;
use Root\Lib\BaseCommand;
use Monolog\Logger;
use Monolog\Handler\StdoutHandler;
/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2024-07-24 01:47:56
 */
class KafkaConsume extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'k:consume';
    
     /**
     * 配置参数
     * @return void
     */
    public function configure(){
        /** 必选参数 */
        $this->addArgument('argument','这个是参数argument的描述信息');
        /** 可传参数 */
        $this->addOption('option','这个是option参数的描述信息');
    }
    
    /**
     * 清在这里编写你的业务逻辑
     * @return void
     */
    public function handle()
    {
        /** 丢弃日志 */
        // Create the logger
        $logger = new Logger('my_logger');
        // Now add some handlers
        $logger->pushHandler(new StreamHandler('php://stdout', Logger::WARNING));

        /** 获取消费者配置实例 */
        $config = \Kafka\ConsumerConfig::getInstance();

        /** 设置元数据刷新间隔时间为10000毫秒（10秒）*/
        $config->setMetadataRefreshIntervalMs(1000);
        /** 设置Kafka集群的代理（broker）列表，指向本地的Kafka实例，端口号为9092。*/
        $config->setMetadataBrokerList('127.0.0.1:9092');
        /** 设置消费者组ID为 test */
        $config->setGroupId('test');
        /** 设置Kafka代理版本为 1.0.0 */
        $config->setBrokerVersion('1.0.0');
        /** 设置消费者订阅的主题列表，这里是订阅名为 test 的主题。 */
        $config->setTopics(['test']);
        /** 设置消费者在找不到之前的偏移量或偏移量超出范围时，应该从主题的最早（earliest）位置开始消费。 默认为 latest，即从最新的位置开始消费 */
        //$config->setOffsetReset('earliest');
        /** 创建一个新的 \Kafka\Consumer 实例并赋值给 $consumer 变量 */
        $consumer = new \Kafka\Consumer();
        /** 设置日志记录器 */
        $consumer->setLogger($logger);
        /** 启动消费者，并传递一个回调函数，该函数将在每次接收到消息时被调用。 */
        /** 回调函数接收三个参数：主题名 $topic，分区号 $part，以及消息内容 $message。 */
        $consumer->start(function($topic, $part, $message) {
            $this->info("接收到消息了".date('Y-m-d H:i:s'));
            var_dump($message['message']['value']);
        });

    }
}