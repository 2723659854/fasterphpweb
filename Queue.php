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
    const QUEUE_TOPIC = 'A_SIMPLE_DEMO_QUEUE_TOPIC';

    /** 队列名称 */
    public $queueName = self::QUEUE_TOPIC;
    /** 分组名称 */
    public $groupName = self::QUEUE_TOPIC;

    /** 最大失败次数 */
    public $maxFailNum = 5;

    /** 消费成功 */
    const ACK = 1;

    /** 消费失败 */
    const NACK = 0;

    /**
     * 初始化
     */
    final public function __construct()
    {
        try {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            $redis->auth('X3WzfcTI3LEXPhyW');
            $redis->select(5);
            $this->queue = $redis;
        } catch (\RedisException $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * 投递消息
     * @param array $payload 消息内容
     * @return bool
     * @note 不限制队列长度，并持久化存储
     */
    final public function publish(array $payload): bool
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
    final public function consume()
    {
        /** 创建消费者组 从第一条消息开始处理 $从当前开始， 0 从0开始 */
        $this->queue->xGroup('CREATE', $this->queueName, $this->groupName, '0', true);
        while (true) {
            $messages = $this->queue->xReadGroup($this->groupName, $this->uuid(), [$this->queueName => '>'], 1);
            if (!empty($messages)) {
                foreach ($messages as $stream => $messageData) {
                    $ackMessages = [];
                    foreach ($messageData as $messageId => $message) {
                        try {
                            if ($this->execute($message)) {
                                $ackMessages[] = $messageId;
                            } else {
                                if (!isset($message['_fail'])) {
                                    $message['_fail'] = 1;
                                } else {
                                    $message['_fail']++;
                                }
                                if ($message['_fail'] >= $this->maxFailNum) {
                                    unset($message['_fail']);
                                    $this->error(new \Exception("the queue " . $this->queueName . " execution failed more than the maximum number of times：" . $this->maxFailNum . " ，the content of message ：" . json_encode($message, JSON_UNESCAPED_UNICODE)));
                                } else {
                                    $this->publish($message);
                                }
                            }
                        } catch (\Exception $e) {
                            $this->error($e);
                        }
                    }
                    if (!empty($ackMessages)) {
                        $res = $this->queue->xAck($stream, $this->groupName, $ackMessages);
                        if ($res) {
                            foreach ($ackMessages as $ackMessage) {
                                $this->queue->xDel($this->queueName, [$ackMessage]);
                            }
                        }
                    }
                }
            } else {
                usleep(1000);
            }
        }
    }

    /**
     * 具体业务逻辑
     * @param array $params 参数
     * @return int
     */
    public  function execute(array $params): int{
        return self::ACK;
    }


    /**
     * 生成消费者id
     * @return string
     */
    private function uuid()
    {
        return 'consumer_'.md5(uniqid() . time());
    }

    /**
     * 异常处理
     * @param \Exception $exception
     * @return void
     */
    public function error(\Exception $exception){}

    /**
     * 获取日志存放目录
     * @access private
     * @return string
     */
    final public function getLogPath()
    {
        return dirname(dirname(dirname(dirname(__DIR__)))) . '/Application/Runtime/Logs/Command/';
    }

    /**
     * 记录日志
     * @param array $params 参数
     * @return void
     */
    final public function log(array $params)
    {
        $file = $this->getLogPath() . date('Y_m_d') . '_' . $this->queueName . ".log";
        $content = "[info]: 时间：" . date('Y-m-d H:i:s') . " 内容：" . json_encode($params, JSON_UNESCAPED_UNICODE) . "\r\n";
        file_put_contents($file, $content, FILE_APPEND);
    }

}