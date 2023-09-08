<?php
namespace Root\Queue;
//require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
class RabbitMQBase
{
    /**
     * https://www.jianshu.com/p/a6f21317722a
     * 首先要安装php的rabbitmq插件composer require php-amqplib/php-amqplib=^3.2
     * rabbitmq的服务参照旁边的docker配置，插件的话，在这里下载：https://github.com/rabbitmq/rabbitmq-delayed-message-exchange/releases/
     * 如果打不开，可以使用QQ群里面存的3.8的延迟插件
     * 将延迟插件放到/d/rabbitmq/ 下面，
     * 然后进入到rabbitmq容器，进入/var/log/rabbitmq/目录
     * 将插件复制到 /plugins目录下 命令：mv rabbitmq_delayed_message_exchange-3.8.0.ez /plugins
     * 开启插件  rabbitmq-plugins enable rabbitmq_delayed_message_exchange
     * 然后重启rabbitmq容器就可以了
     *
     * 延迟消息的关键是声明一个延迟模式的交换机，然后消息体里面要设置为延迟模式，并且设置延迟时间
     *
     * 然后呢，本服务类又加入了最大尝试次数，原理就是增加了一个参数 _max_attempt_num ，消费者不管业务是否成功，都做消费成功处理 ，返回ack，
     * 但是，如果处理业务的时候发生了异常，则消费没有成功，这个时候捕获异常，然后重新投递，且执行次数自动+1，当下一次的消费判断超过最大执行次数
     * 之后，则不在投递，而是将数据写入数据库，方便手动操作。这样子避免了因为逻辑错误，导致队列反反复复的被消费，最终服务器内存被沾满。
     */

    /** @var string 服务器地址 */
    private $host = "127.0.0.1";
    /** @var int 服务器端口 */
    private $port = 5672;
    /** @var string 服务器登陆用户 */
    private $user = "guest";
    /** @var string 服务器登陆密码 */
    private $pass = "guest";
    /** @var \PhpAmqpLib\Channel\AbstractChannel|\PhpAmqpLib\Channel\AMQPChannel 渠道通道 */
    private $channel;
    /** @var AMQPStreamConnection rabbitmq连接 */
    private $connection;


    /** @var int 过期时间 */
    public $timeOut = 0;
    /** @var string 交换机名称 */
    private $exchangeDelayed = "delayed";
    /** @var string 队列名称 */
    public $queueName = "delayedQueue";

    /** @var string 交换机类型 转发给所有绑定到本交换机的通道，不匹配路由 */
    const EXCHANGETYPE_FANOUT = "fanout";
    /** @var string 交换机类型 只转发给绑定到本交换机，并且路由完全匹配的通道 */
    const EXCHANGETYPE_DIRECT = "direct";
    /** @var string 交换机类型  延迟信息 */
    const EXCHANGETYPE_DELAYED = "x-delayed-message";

    /** @var int 最大尝试次数 */
    protected $_max_attempt_num=3;

    /** 交换机类型有：direct，fanout，topic，header，x-delayed-message */
    /**
     * 初始化rabbitmq连接
     * @param $type
     */
    public function __construct(string $type='')
    {
        $config=config('rabbitmq');
        $this->host=$config['host'];
        $this->port=$config['port'];
        $this->user=$config['user'];
        $this->pass=$config['pass'];
        /** @var  connection 创建一个rabbitmq连接*/
        $this->connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->pass);
        /** @var  channel 创建一个通道*/
        $this->channel = $this->connection->channel();
        /** exchange: 交换机的名字，为空则自动创建一个名字
        exchange_type:  默认交换机类型为direct
        passive: 检查交换机是否存在，存在返回状态信息，不存在返回404错误
        durable: 设置是否持久化
        auto_delete: 最后一个队列解绑则删除
        internal: 是否设置为值接收从其他交换机发送过来的消息，不接收生产者的消息
        nowaite: 如果设置，服务器将不会对方法作出回应
        arguments: 一个字典，用于传递额外的参数
        原文链接：https://blog.csdn.net/heroiclee/article/details/122196087
         */
        /** 声明Exchange 这里比较关键的是要设置名称和类型，并且第四个参数设置为true，即持久化保存交换机，最后一个参数用来设置延迟队列的消息转发模式为路由完全匹配（就是延迟消息要求必须匹配路由才可以转发给消费者）*/
        $this->channel->exchange_declare($this->exchangeDelayed, $type?:self::EXCHANGETYPE_DIRECT, false, true, false, false, false, new AMQPTable(["x-delayed-type" => self::EXCHANGETYPE_DIRECT]));
        /** 声明队列 也是需要持久化保存队列 */
        $this->channel->queue_declare($this->queueName, false, true, false, false);
        /** 将队列绑定到交换机 同时设置路由，这里的队列名称和路由是相同的字符串，这里应该是路由名称，作者直接把队列名称和队列名称公用 */
        $this->channel->queue_bind($this->queueName, $this->exchangeDelayed, $this->queueName);
    }

    /**
     * 创建延迟信息
     * @param string $msg 消息内容
     * @param int $time 延迟时间
     * @return AMQPMessage 包装时候的消息
     */
    private function createMessageDelay(string $msg, int $time):object
    {
        /** @var  $delayConfig [] 初始化消息配置*/
        $delayConfig = [
            /** 传递模式   消息持久化 ，这一个配置是消费确认发送ack的根本原因*/
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            /** 消息表头 设置延迟时间  延迟可以精确到毫秒 */
            //'application_headers' => new AMQPTable(['x-delay' => $time * 1000])
        ];
        if ($time){
            /** 消息表头 设置延迟时间  延迟可以精确到毫秒 */
            $delayConfig['application_headers']=new AMQPTable(['x-delay' => $time * 1000]);
        }
        /** @var  $msg AMQPMessage 生成消息对象 */
        $msg = new AMQPMessage($msg, $delayConfig);
        return $msg;
    }

    /**
     * 发送延迟消息
     * @param string $msg 消息内容
     * @return void
     * @throws Exception
     */
    private function sendDelay(string $msg)
    {
        /** @var AMQPMessage $_msg 创建rabbitmq的延迟消息*/
        $_msg = $this->createMessageDelay($msg, $this->timeOut);
        /** 发布消息 语法：消息体，交换机，路由（这里作者简化了用的队列名称代理路由名称）*/
        $this->channel->basic_publish($_msg, $this->exchangeDelayed, $this->queueName);
    }

    /**
     * 关闭服务
     * @return void
     * @throws Exception
     */
    public function close(){
        //echo "关闭连接\r\n";
        /** 发布完成后关闭通道 */
        $this->channel->close();
        /** 发布完成后关闭连接 */
        $this->connection->close();
    }

    /**
     * 消费延迟队列
     * @return void
     * @throws Exception
     */
    private function consumDelay()
    {
        /**
         * 创建一个回调函数，用来处理接收到的消息
         * @param $msg
         * @return void
         */
        $callback = function ($msg) {
            //echo "接收时间：".date('Y-m-d H:i:s',time())."\r\n";
            //echo ' [x] ', $msg->body, "\n";
            $params=json_decode($msg->body,true);
            /** 这里手动实现了队列的执行次数统计，因为是重试队列，所以不能让队列反反复复的一直重试 */
            if (!isset($params['_max_attempt_num'])){
                $params['_max_attempt_num']=1;
            }else{
                $params['_max_attempt_num']++;
            }
            try {
                /** 业务逻辑，这里手动抛出一个异常 */
//                if ($params['number']==6){
//                    throw new Exception("发生了异常");
//                }
                /** 调用用户的逻辑 */
                $this->handle($params);
                /** 确认接收到消息 */
                $this->channel->basic_ack($msg->delivery_info['delivery_tag'], false);
            }catch (Exception|RuntimeException $exception){
                var_dump($exception->getMessage());
                /** 如果当前任务重试次数小于最大尝试次数，那么就继续重试， */
                if ($params['_max_attempt_num']<=$this->_max_attempt_num){
                    //var_dump("重新投递");
                    $this ->sendDelay(json_encode($params),5);
                }else{
                    //todo 数据写入数据库，方便后续操作，比如退钱什么的
                    echo "超过了最大执行次数，数据写入数据库\r\n";
                }
                /** 确认接收到消息 */
                $this->channel->basic_ack($msg->delivery_info['delivery_tag'], false);
            }


            /** 关闭消费者信息 */
            if ($msg->body=='quit'){
                /** 这里使用的是通过消费者标签关闭通道 */
                $msg->getChannel()->basic_cancel($msg->getConsumerTag());
            }
        };
        /** 设置消费者智能分配模式：就是当前消费者消费完了才接收新的消息，交换机分配的时候优先分配给空闲的消费者 */
        $this->channel->basic_qos(null, 1, null);
        /** 开始消费队里里面的消息 这里要注意一下，第二个参数添加了标签，主要是用来后面关闭通道使用，并且不会接收本消费者发送的消息*/
        $this->channel->basic_consume($this->queueName, 'lantai', false, false, false, false, $callback);

        /** 如果有配置了回调方法，则等待接收消息。这里不建议休眠，因为设置了消息确认，会导致rabbitmq疯狂发送消息，如果取消了消息确认，休眠会导致消息丢失 */
        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        $this->close();
    }

    /**
     * 发送消息
     * @param array $msg
     * @param int $time
     * @return void
     * @throws Exception
     */
    public function send(array $msg){
        $this ->sendDelay(json_encode($msg));
    }

    /**
     * 消费队列
     * @return void
     * @throws Exception
     */
    public function consume(){
        $this->consumDelay();
    }

    /**
     * 业务逻辑
     * @param $param
     * @return void
     */
    public function handle($param){
        //var_dump($param);
    }
}
