<?php
namespace App\Rabbitmq;
use Root\Queue\RabbitMQBase;

class Demo2 extends RabbitMQBase
{

    /**
     * 自定义队列名称
     * @var string
     */
    public $queueName ="just";

    /** @var int $timeOut 普通队列 */
    public $timeOut=0;

    /**
     * 逻辑处理
     * @param $param
     * @return void
     */
    public function handle($param)
    {
        var_dump($param);
        var_dump($this->queueName);
    }
}