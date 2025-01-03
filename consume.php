<?php
require_once __DIR__ . '/Queue.php';

class Consume extends RedisQueue
{
    public function execute(array $params): int
    {
        var_dump($params);
        return self::ACK;
    }
}


