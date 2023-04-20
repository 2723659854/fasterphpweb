<?php
namespace Root;

set_time_limit(0);
require_once __DIR__ . '/function.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/Request.php';

require_once __DIR__ . '/route.php';
require_once __DIR__ . '/app.php';
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

class Fucker
{
    /** 存所有的客户端事件 */
    public $events = [];

    /** @var Event $event 整个服务的事件 */
    public $event;

    /** @var EventBase $event_base eventBase实例 使用的epoll模型 */
    public $event_base;

    /** @var false|resource tcp 服务 */
    public $serv;

    public $onMessage;

    /**
     * 定义消息处理方法
     * @param $str
     * @return void
     */
    public function message($str)
    {
        /** 直接打印 */
        echo $str . "\r\n";
    }

    public function __construct($host)
    {
        /** 创建tcp服务器套接字 */
        $this->serv = stream_socket_server($host, $errno, $error); //
        /** 如果有错误则直接退出 */
        $errno && exit($error);
        /** 设置为异步，不然fread,stream_socket_acceptd等会堵塞 */
        stream_set_blocking($this->serv, 0);
        /** 获取eventBase实例 */
        $this->event_base = new EventBase();

        /** 打印是否是使用epoll模型 */
        //echo $this->event_base->getMethod();//epoll
        //echo "\r\n";
        /** 建立事件监听服务器socket可读事件， 获取event实例，这个是获取php的event扩展的基类 */
        /** 在react中，SyntheticEvent在调用事件回调之后该对象将被重用，并且其所有属性都将无效。如果要以异步方 式访问事件属性，则应调用event.persist()事件，这将从池中删除事件，并允许用户代码保留对该事件的引用。 */
        $event = new Event($this->event_base, $this->serv, Event::READ | Event::PERSIST, function ($serv) {
            /** 获取新的连接 */
            $cli = @stream_socket_accept($serv, 0);
            /** 如果有连接 */
            if ($cli) {
                /** 设置为异步 */
                stream_set_blocking($cli, 0);
                /** 将新的客户端连接投入到事件，构建客户端事件， */
                $client_event = new Event($this->event_base, $cli, Event::READ | Event::PERSIST, function ($cli) {
                    /** 客户端连接再添加监听可读事件，读取客户端连接的数据 */
                    $buffer = '';
                    $flag    = true;
                    while ($flag) {
                        $_content = fread($cli, 1024);
                        if (strlen($_content) < 1024) {
                            $flag = false;
                        }
                        $buffer = $buffer. $_content;
                    }

                    /** 如果用户输入为空或者输入不是资源 */
                    if ($buffer == false || !is_resource($cli)) {
                        /** 释放事件 */
                        unset($this->events[(int)$cli]);
                        unset($cli);
                        return;
                    }
                    /** 正常读取到数据,触发消息接收事件,响应内容，如果读取的内容不为空，并且设置了onMessage回调函数 */
                    if (!empty($buffer) && is_callable($this->onMessage)) {
                        /** 传入连接，接收的值到回调函数 */
                        call_user_func($this->onMessage, $cli, $buffer);
                    }
                    /** 构建需要回复的内容，使用http协议 */
//                    $time    = date('Y-m-d H:i:s');
//                    $content = "<html><title>epoll模型</title><body>这里是epoll模型，当前时间:$time</body></html>";
//
//                    /** 拼装头信息 */
//                    $header = '';
//                    $header .= "HTTP/1.1 200 OK\r\n";
//                    $header .= "Date: " . gmdate('D, d M Y H:i:s T') . "\r\n";
//                    $header .= "Content-Type: text/html;charset=utf-8\r\n";
//                    $header .= "Content-Length: " . strlen($content) . "\r\n\r\n";//必须2个\r\n表示头部信息结束
//                    $header .= $content;
//                    /** 向客户端发送数据 */
//                    fwrite($cli, $header);//回写数据
//                    fclose($cli);
//                    unset($this->events[(int)$cli]);//
//                    unset($cli);

                }, $cli);
                /** 将构建的客户端事件添加到epoll当中 */
                $client_event->add();
                /** 添加事件到全局数组,不然无法持久化连接,这是个大坑，这里是把每一个连接的事件都保存,这里必须持久化，否则无法回复消息，无法读取消息 */
                $this->events[(int)$cli] = $client_event;
            }
        }, $this->serv);

        $this->event=$event;
    }

    public function run()
    {

        /** 添加事件 */
        $this->event->add();//
        /** 执行事件循环 */
        $this->event_base->loop();//
    }
}
