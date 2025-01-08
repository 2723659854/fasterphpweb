<?php
namespace App\Queue;
use Root\Queue\RedisQueue;

/**
 * @purpose redis消费者
 * @author administrator
 * @time 2023-10-31 03:44:50
 */
class Demo extends RedisQueue
{

    public $queueName = 'demo';

    public $groupName = 'group';

    public function execute(array $params):int
    {
        var_dump($params);
        return self::ACK;
    }

    public function error(\Exception $exception)
    {
        $this->log([$exception->getMessage()]);
    }

}