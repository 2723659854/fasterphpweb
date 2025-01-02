<?php


/**
 * @purpose redis队列服务
 * @author yanglong
 * @time 2024年12月27日18:29:57
 */
class RedisQueue
{

    /** 队列服务器 */
    private $queue;

    /** 默认队列分组名称 */
    const QUEUE_TOPIC = 'A_SIMPLE_QUEUE_TOPIC_';

    /** 队列名称 */
    public  $queueName = self::QUEUE_TOPIC;
    /** 分组名称 */
    public  $groupName = self::QUEUE_TOPIC;

    /** 最大失败次数 */
    public $maxFailNum = 5;

    /** 消费成功 */
    const ACK = 1;

    /** 消费失败 */
    const NACK = 0;

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
            throw new \RuntimeException($e->getMessage());
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
            return $this->queue->xAdd($this->queueName, '*', $payload);
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
        $this->queue->xGroup('CREATE', $this->queueName, $this->groupName, '0', true);
        while (true) {
            $messages = $this->queue->xReadGroup($this->groupName, $this->uuid(), [$this->queueName => '>'], 1);
            if (!empty($messages)) {
                $ackMessages = [];
                foreach ($messages as $stream => $messageData) {
                    foreach ($messageData as $messageId => $message) {
                        try {
                            if ($this->execute($message)) {
                                $ackMessages[] = $messageId;
                            }else{
                                if (!isset($message['_fail'])){
                                    $message['_fail'] = 1;
                                }else{
                                    $message['_fail'] ++;
                                }
                                if ($message['_fail'] >= $this->maxFailNum) {
                                    unset($message['_fail']);
                                    /** 超过最大执行次数，抛出异常 */
                                    $this->error(new \Exception("队列：".$this->queueName." 执行失败超过最大次数：".$this->maxFailNum." 消息内容：".json_encode($message,JSON_UNESCAPED_UNICODE)));
                                }else{
                                    $this->publish($message);
                                }
                            }
                        } catch (\Exception $e) {
                            /** 理论上业务的异常应该由开发者自己处理，本程序只负责值守任务 */
                            $this->error($e);
                        }
                    }
                }
                if (!empty($ackMessages)) {
                    $this->queue->xAck($this->queueName, $this->groupName, $ackMessages);
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

    /**
     * 异常处理
     * @param \Exception $exception
     * @return void
     */
    public function error(\Exception $exception)
    {

    }

}