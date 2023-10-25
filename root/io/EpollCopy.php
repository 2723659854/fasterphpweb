<?php

namespace Root\Io;

use Root\Lib\HttpClient;

class EpollCopy
{
    /** 存所有的客户端事件 */
    public static $events = [];

    /** @var \Event $serveEvent 整个服务的事件,必须单独保存在进程内，不保存进程直接退出，不单独保存，系统直接摆烂不工作 */
    public static $serveEvent ;

    /** @var \EventBase $event_base eventBase实例 使用的epoll模型 */
    public static $event_base;

    /** @var false|resource tcp 服务 */
    public static $serv;

    /** @var callable $onMessage 消息处理事件 */
    public $onMessage;

    /** @var string $host 监听的ip和协议 */
    public $host='0.0.0.0';

    /** @var string $port 监听的端口 */
    public $port='8000';

    /** @var string $protocol 通信协议 */
    public $protocol='tcp';
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

    public function __construct()
    {
        global $_port;
        $this->port=$_port?:'8000';
        /** @var string $listeningAddress 拼接监听地址 */
        $listeningAddress=$this->protocol.'://'.$this->host.':'.$this->port;
        /** 创建tcp服务器套接字 */
        Epoll::$serv = stream_socket_server($listeningAddress, $errno, $error); //
        /** 如果有错误则直接退出 */
        $errno && exit($error);
        /** 设置为异步，不然fread,stream_socket_acceptd等会堵塞 */
        stream_set_blocking(Epoll::$serv, 0);
        /** 获取eventBase实例 因为本文件使用了命名空间 所以这里必须指定eventbase 和 event 类的名命名空间是根目录\ 否则会在本类所在的命名空间查找，会报错，找不到这个类 */
        Epoll::$event_base = new \EventBase();
        /** 建立事件监听服务器socket可读事件， 获取event实例，这个是获取php的event扩展的基类 */
        /** 在react中，SyntheticEvent在调用事件回调之后该对象将被重用，并且其所有属性都将无效。如果要以异步方 式访问事件属性，则应调用event.persist()事件，这将从池中删除事件，并允许用户代码保留对该事件的引用。 */
        /** 这个读写事件必须立即 创建，否则进程会卡死摆烂 */
        $event = new \Event(Epoll::$event_base, Epoll::$serv, \Event::READ | \Event::PERSIST, function ($serv) {
            /** 获取新的连接 stream_socket_accept语法：socket连接，超时，客户端地址 */
            $cli = @stream_socket_accept($serv, 0,$remote_address);
            /** 如果有连接 */
            if ($cli) {
                /** 设置为异步 */
                stream_set_blocking($cli, 0);
                /** 将新的客户端连接投入到事件，构建客户端事件， */
                $client_event = new \Event(Epoll::$event_base, $cli, \Event::READ | \Event::PERSIST, function ($cli)use($remote_address) {
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
                    if (!$buffer  || !is_resource($cli)) {
                        /** 释放事件 */
                        unset(Epoll::$events[(int)$cli]);
                        unset($cli);
                        return;
                    }
                    /** 正常读取到数据,触发消息接收事件,响应内容，如果读取的内容不为空，并且设置了onMessage回调函数 */
                    if (!empty($buffer) && is_callable($this->onMessage)) {
                        /** 传入连接，接收的值到回调函数 */
                        call_user_func($this->onMessage, $cli, $buffer,$remote_address);
                    }
                }, $cli);
                /** 将构建的客户端事件添加到epoll当中 */
                $client_event->add();
                /** 添加事件到全局数组,不然无法持久化连接,这是个大坑，这里是把每一个连接的事件都保存,这里必须持久化，否则无法回复消息，无法读取消息 */
                Epoll::$events[(int)$cli] = $client_event;
            }
        }, Epoll::$serv);
        /** 添加事件 */
        $event->add();
        /** 这个事件必须保存，不然会退出进程 */
        Epoll::$serveEvent=$event;
    }

    /**
     * 底层逻辑是只需要使用eventBase就行了
     * @return void
     */
    public static function addWriteClient(){
        $contextOptions['ssl']=[
            'verify_peer' => false,
            'verify_peer_name' => false
        ];
        $scheme = 'tcp';
        $host = '192.168.4.128';
        $port = 8080;
        $request = HttpClient::makeRequest($host,$port);
        /** 设置参数 */
        $context = stream_context_create($contextOptions);
        /** 创建客户端 STREAM_CLIENT_CONNECT 同步请求，STREAM_CLIENT_ASYNC_CONNECT 异步请求*/
        $cli = stream_socket_client("{$scheme}://{$host}:{$port}", $errno, $errstr, 1, STREAM_CLIENT_ASYNC_CONNECT, $context);
        /** 设置为异步 */
        stream_set_blocking($cli, 0);
        //fwrite($cli,$request,strlen($request));
        /** 创建一个可读事件 */
        $client_event = new \Event(Epoll::$event_base, $cli,  \Event::WRITE|\Event::PERSIST, function ($cli)use($request) {
            fwrite($cli,$request,strlen($request));
        }, $cli);
        /** 将构建的客户端事件添加到epoll当中 */
        $client_event->add();

        $client_event1 = new \Event(Epoll::$event_base, $cli,  \Event::READ|\Event::PERSIST, function ($cli) {
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
        }, $cli);
        /** 将构建的客户端事件添加到epoll当中 */
        $client_event1->add();

        /** 添加事件到全局数组,不然无法持久化连接,这是个大坑，这里是把每一个连接的事件都保存,这里必须持久化，否则无法回复消息，无法读取消息 */
        //Epoll::$events[(int)$cli] = $client_event;
        var_dump("添加写完成");
    }

    /** 启动http服务 */
    public function run()
    {
        /** 执行事件循环 */
        Epoll::$event_base->loop();//
    }

    /** 启动服务 */
    public function start(){
        $this->fork();
    }

    /** 创建子进程 */
    public function fork(){
        global $_server_num;
        for ($i=1;$i<=$_server_num;$i++){
            /** @var int $pid 创建子进程 ,必须在loop之前创建子进程，否则loop会阻塞其他子进程 */
            $pid = \pcntl_fork();
            if ($pid){
                cli_set_process_title("xiaosongshu_http");
                writePid();
                prepareMysqlAndRedis();
                $this->run();
            }
        }
    }
}
