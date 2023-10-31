<?php
namespace App\Rabbitmq;
use Root\Queue\RabbitMQBase;

class Demo extends RabbitMQBase
{

    /** @var int $timeOut 延迟时间 秒 0 则不延时，延时需要安装官方插件 */
    public $timeOut = 0;

    /**
     * 业务逻辑
     * @param $param
     * @return void
     */
    public function handle($param)
    {
        var_dump($param);
        var_dump($this->queueName);
    }

    /**
     * 异常处理
     * @param \Exception|\RuntimeException \$exception
     * @return mixed|void
     */
    public function error(\Exception|\RuntimeException $exception)
    {
        // TODO: Implement error() method.
    }
}