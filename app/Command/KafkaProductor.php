<?php

namespace App\Command;

use Root\Lib\BaseCommand;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * @purpose 用户自定义命令
 * @author administrator
 * @time 2024-07-24 01:48:06
 */
class KafkaProductor extends BaseCommand
{

    /** @var string $command 命令触发字段，请替换为你自己的命令，执行：php start.php your:command */
    public $command = 'k:publish';

    /**
     * 配置参数
     * @return void
     */
    public function configure()
    {
        /** 必选参数 */
        $this->addArgument('argument', '这个是参数argument的描述信息');
        /** 可传参数 */
        $this->addOption('option', '这个是option参数的描述信息');
    }

    /**
     * 清在这里编写你的业务逻辑
     * @return void
     */
    public function handle()
    {
        //$this->sendASync();
        $this->sendSync();

    }

    /**
     * 发送异步消息
     * @return void
     */
    public function sendASync()
    {

        // Create the logger
        $logger = new Logger('my_logger');
        /** 设置日志为标准输入输出 */
        $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
        /** 创建或获取一个 \Kafka\ProducerConfig 实例，并将其分配给 $config 变量。 */
        $config = \Kafka\ProducerConfig::getInstance();
        /** 设置元数据刷新间隔为 10000 毫秒（10 秒） */
        $config->setMetadataRefreshIntervalMs(10000);
        /** 设置 Kafka 代理（Broker）的地址为 127.0.0.1:9092。 */
        $config->setMetadataBrokerList('127.0.0.1:9092');
        /** 设置 Kafka 代理的版本为 1.0.0 */
        $config->setBrokerVersion('1.0.0');
        /** 设置消息的确认（acknowledgment）级别为 1  表示至少有一个副本的 Kafka Broker 收到消息后才确认成功*/
        $config->setRequiredAck(1);
        /** false 表示同步模式，在调用 send 方法时会等待消息发送结果再继续执行。 */
        $config->setIsAsyn(false);
        /** 设置生产消息的时间间隔为 500 毫秒。 */
        $config->setProduceInterval(500);
        /** 生产者实例需要一个回调函数，该函数返回要发送的消息。 */
        $producer = new \Kafka\Producer(
            function () {
                return [
                    [
                        'topic' => 'test',
                        'value' => '一步消息',
                        'key' => 'test',
                    ],
                ];
            }
        );
        /**  为生产者设置一个日志记录器 $logger */
        $producer->setLogger($logger);
        /** 设置生产者的成功回调函数 */
        $producer->success(function ($result) {
            $this->info("投递成功");
            //var_dump($result);
        });
        /** 设置生产者的错误回调函数 */
        $producer->error(function ($errorCode) {
            var_dump($errorCode);
        });
        /** 调用 send 方法开始发送消息 */
        $producer->send(true);

    }

    /**
     * 发送同步消息
     * @return void
     */
    public function sendSync()
    {
        $logger = new Logger('my_logger');
        /** 设置日志为标准输入输出 */
        $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

        $config = \Kafka\ProducerConfig::getInstance();
        $config->setMetadataRefreshIntervalMs(1000);
        /** 设置代理服务器 */
        $config->setMetadataBrokerList('127.0.0.1:9092');
        /** 消费者和生产者的协议版本必须一致 */
        $config->setBrokerVersion('1.0.0');
        //$config->setRequiredAck(1);
        /** 所有消息都需要反馈ack确认 */
        $config->setRequiredAck(-1);
        /** 经过测试发现，这里设置成异步，才不会丢失消息 */
        $config->setIsAsyn(true);
        $config->setProduceInterval(500);
        $producer = new \Kafka\Producer();
        $producer->setLogger($logger);

        for ($i = 0; $i < 10; $i++) {
            $producer->send(array(
                array(
                    'topic' => 'test',
                    'value' => $i,
                    'key' => 'test',
                ),
            ));
        }
        $this->info("同步消息推送完毕");
    }
}