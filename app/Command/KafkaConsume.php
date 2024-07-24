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
        // Create the logger
        $logger = new Logger('my_logger');
        // Now add some handlers
        $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));

        $config = \Kafka\ConsumerConfig::getInstance();
        $config->setMetadataRefreshIntervalMs(10000);
        $config->setMetadataBrokerList('127.0.0.1:9092');
        $config->setGroupId('test');
        $config->setBrokerVersion('1.0.0');
        $config->setTopics(['test']);
        //$config->setOffsetReset('earliest');
        $consumer = new \Kafka\Consumer();
        $consumer->setLogger($logger);
        $consumer->start(function($topic, $part, $message) {
            var_dump($message);
        });
        $this->info("请在这里编写你的业务逻辑");
    }
}