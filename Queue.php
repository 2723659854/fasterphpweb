<?php


class Queue
{
    private $queue;

    const QUEUE_TOPIC = 'A_SIMPLE_QUEUE_TOPIC_';

    /**
     * 初始化
     */
    public function __construct()
    {
        try {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            $redis->auth('xT9=123456');
            $redis->select(5);
            $this->queue = $redis;
        } catch (\RedisException $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * 投递消息
     * @param array $payload
     * @return bool
     */
    public function publish(array $payload): bool
    {
        try {
            return $this->queue->xAdd(self::QUEUE_TOPIC, '*', $payload);
        } catch (\RedisException $e) {
            return false;
        }

    }

    /**
     * 开启消费者
     * @return void
     * @throws \RedisException
     */
    public function consume()
    {
        /** 创建消费者组 从第一条消息开始处理 $从当前开始， 0 从0开始 */
        $this->queue->xGroup('CREATE', self::QUEUE_TOPIC, self::QUEUE_TOPIC, '0', true);
        while (true) {
            $messages = $this->queue->xReadGroup(self::QUEUE_TOPIC, $this->uuid(), [self::QUEUE_TOPIC => '>'], 1);
            if (!empty($messages)) {
                $ackMessages = [];
                foreach ($messages as $stream => $messageData) {
                    foreach ($messageData as $messageId => $message) {
                        if (method_exists($this, 'execute')) {
                            try {
                                if ($this->execute($message)) {
                                    $ackMessages[] = $messageId;
                                }
                            } catch (\Exception $e) {
                                /** 理论上业务的异常应该由开发者自己处理，本程序只负责值守任务 */
                            }
                        }
                    }
                }
                if (!empty($ackMessages)) {
                    $this->queue->xAck(self::QUEUE_TOPIC, self::QUEUE_TOPIC, $ackMessages);
                }
            } else {
                usleep(1000);
            }
        }
    }

    /**
     * 具体逻辑
     * @param array $params
     * @return int
     */
    public function execute(array $params): int
    {
    }

    /**
     * 生成消费者id
     * @return string
     */
    private function uuid()
    {
        return md5(uniqid() . time());
    }

}