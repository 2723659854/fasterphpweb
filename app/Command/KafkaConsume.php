<?php
namespace App\Command;

use Monolog\Handler\StreamHandler;
use Root\Lib\BaseCommand;
use Monolog\Logger;

/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2024-07-24 01:47:56
 * @comment 通过测试发现，kafka延迟高，一般在15秒以上，并且会丢失数据，投递5条数据，会丢失一条数据。但是也可能是我的用法不对吧。
 * @note 分区操作，进入容器后执行：kafka-topics.sh --alter --topic test --partitions 6 --bootstrap-server localhost:9092
 * @note 验证分区结果：kafka-topics.sh --describe --topic test --bootstrap-server localhost:9092
 * @note 分区后，订阅同一个主题的消费者数应该等于分区数，否则某一个分区如果没有消费者，会导致消息积压在这个分区不被消费
 * 比如说，在本测试样例中，消费者订阅的主题是test，上面的命令设置了6个分区，那么同样的也要设置6个消费者。
 * 若消费者数小于分区数，某些分区的消息不回被消费，导致消息积压，给人的感觉是丢失了消息。
 * 若消费者数大于分区数，那么多出来消费者将会被限制。因为同一个分区，只能有一个消费者消费。
 * @command php start.php k:consume
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
        /**
         * 经过测试发现：同一个分组里面，即便多个消费者订阅同一个主题，消息只会推送给同一个消费者，不会平均分配。
         * 同时，消息也会推送给另外一个订阅了这个主题的分组。
         * 那么可以得出结论：kafka的消息推送机制是按主题推送，每个分组只有一个消费者获得消息
         * */
        /** 设置消费者组ID为 test */
        $config->setGroupId('test');
        /** 设置Kafka代理版本为 1.0.0 */
        $config->setBrokerVersion('1.0.0');
        /** 设置消费者订阅的主题列表，这里是订阅名为 test 的主题。 */
        $config->setTopics(['test']);
        /** 解决心跳报错 Heartbeat error, errorCode:27 The group is rebalancing, so a rejoin is needed.*/
        // 调整配置参数
        $config->set('session.timeout.ms', 30000);  // 30秒
        $config->set('heartbeat.interval.ms', 10000);  // 10秒
        $config->set('max.poll.interval.ms', 300000);  // 5分钟

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