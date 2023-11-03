<?php

namespace Root\Core\Provider;

use Root\Queue\RedisQueueConsumer;
use Root\Xiaosongshu;

/**
 * @purpose redis消费者队列
 */
class QueueProvider implements IdentifyInterface
{

    public function handle(Xiaosongshu $app,array $param){
        G(RedisQueueConsumer::class)->handle();
    }

}