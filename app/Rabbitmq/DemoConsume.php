<?php
namespace App\Rabbitmq;
use Root\Queue\RabbitMQBase;

/**
 * @purpose rabbitMq消费者
 * @author administrator
 * @time 2023-10-31 05:27:48
 */
class DemoConsume extends RabbitMQBase
{

    /**
     * 自定义队列名称
     * @var string
     */
    public $queueName ="DemoConsume";

    /** @var int $timeOut 普通队列 */
    public $timeOut=0;

   /**
     * 逻辑处理
     * @param array $param
     * @return void
     */
    public function handle(array $param)
    {
        var_dump($param);
        // TODO: Implement handle() method.
    }

    /**
     * 异常处理
     * @param \Exception|\RuntimeException $exception
     * @return mixed|void
     */
    public function error(\Exception|\RuntimeException $exception)
    {
        // TODO: Implement error() method.
    }
}