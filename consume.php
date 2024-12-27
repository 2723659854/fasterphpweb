<?php
require_once __DIR__ . '/Queue.php';

class Consume extends RedisQueue
{
    public function execute(array $params): int
    {
        var_dump($params);
        if ($params['num']%10 == 0){
            throw new Exception("测试抛出异常");
        }

        return 1;
    }
}


