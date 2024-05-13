<?php

namespace Root\Queue;

class RedisQueueConsumer
{

    /** 执行队列 */
    public function consume()
    {
        $enable = config('redis')['enable'];
        if ($enable) {
            $id = pcntl_fork();
            if ($id>0){
                \cli_set_process_title("xiaosongshu_redis_queue");
                $this->_queue_xiaosongshu();
            }
        }
    }

    /** 执行队列 */
    public function handle()
    {
        $enable = config('redis')['enable'];
        if ($enable) {
            echo "redis队列消费者已开启\r\n";
            $this->_queue_xiaosongshu();
        }
    }
    /**
     * redis队列
     * @return void
     */
    public function _queue_xiaosongshu()
    {
        try {
            $config = config('redis');
            $host   = $config['host'] ?? '127.0.0.1';
            $port   = $config['port'] ?? '6379';
            $client = new \Redis();
            $client->connect($host, $port);
            $client->auth($config['password'] ?? '');
            while (true) {
                $job = json_decode($client->RPOP('xiaosongshu_queue'), true);
                $this->deal_job($job);
                $res = $client->zRangeByScore('xiaosongshu_delay_queue', 0, time(), ['limit' => 1]);
                if ($res) {
                    $value = $res[0];
                    $res1  = $client->zRem('xiaosongshu_delay_queue', $value);
                    if ($res1) {
                        $job = json_decode($value, true);
                        $this->deal_job($job);
                    }
                }
                if (empty($job) && empty($res)) {
                    sleep(1);
                }
            }
        } catch (\Exception $exception) {
            global $_color_class;
            echo $_color_class->error("\nredis连接失败,详情：{$exception->getMessage()}\n");
            echo "\r\n";
            echo $_color_class->info("系统将在3秒后重试\n");
            sleep(3);
            $this->_queue_xiaosongshu();
        }
    }


    /**
     * 队列逻辑处理
     * @param $job
     * @return void
     */
    public function deal_job($job = [])
    {
        if (!empty($job)) {
            if (class_exists($job['class'])) {
                $class = new $job['class']($job['param']);
                $class->handle();
            } else {
                echo $job['class'] . '不存在，队列任务执行失败！';
                echo "\r\n";
            }
        }
    }

}