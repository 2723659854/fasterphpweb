<?php

namespace Root\Io;

use Root\Lib\Container;
use Root\Lib\HttpClient;
use Root\Request;

/**
 * @purpose epoll的IO多路复用模型
 * @note 提供http服务器服务
 * @note 提供异步客户端服务
 * @note io多路复用模型中，不可抛出任何异常，否则epoll无法工作
 */
class Epoll
{
    /** 存所有的客户端事件 */
    public static $events = [];

    /** @var \Event $serveEvent 整个服务的事件,必须单独保存在进程内，不保存进程直接退出，不单独保存，系统直接摆烂不工作 */
    private static $serveEvent;

    /** @var \EventBase $event_base eventBase实例 使用的epoll模型 */
    public static $event_base;

    /** @var false|resource tcp 服务 */
    private static $serv;

    /** @var callable $onMessage 消息处理事件 */
    public $onMessage;

    /** @var string $host 监听的ip和协议 */
    private $host = '0.0.0.0';

    /** @var string $port 监听的端口 */
    private $port = '8000';

    /** @var string $protocol 通信协议 */
    private $protocol = 'tcp';

    /** 标记异步客户端已发送请求 */
    private static $write = [];
    /** 异步请求的 原始数据 */
    public static $asyncRequestData = [];
    /** 客户端上传数据最大请求时间 ，如果超过这个时间就断开这个连接 默认6分钟 */
    private static $maxRequestTime = 360;

    /** 初始化 */
    public function __construct()
    {
        global $_port;
        /** http 服务的端口 */
        $this->port = $_port ?: '8000';
        /** @var string $listeningAddress 拼接监听地址 */
        $listeningAddress = $this->protocol . '://' . $this->host . ':' . $this->port;
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
            $cli = @stream_socket_accept($serv, 0, $remote_address);
            /** 如果有连接 */
            if ($cli) {
                /** 设置为异步 */
                stream_set_blocking($cli, 0);
                /** 创建read处理事件 */
                Epoll::dealReadEvent($cli, $this->onMessage, $remote_address);
            }
        }, Epoll::$serv);
        /** 添加事件 */
        $event->add();
        /** 这个事件必须保存，不然会退出进程 */
        Epoll::$serveEvent = $event;
    }

    /**
     * epoll的异步客户端处理程序
     * @param mixed $cli 客户端
     * @param string $request 请求体
     * @param callable|null $success 成功回调
     * @param callable|null $fail 失败回调
     * @param string $remoteAddress 服务器地址
     * @param array $oldParams 原始数据
     * @return void
     * @note 要把write和read事件分成两个事件添加，分别处理对应的逻辑。分别添加到Event事件里面，最后还要添加到EventBase事件里面
     * @note epoll和select 对于事件的处理是不同的，select可以循环套娃，比如当前系统添加一个方法test，里面异步请求本方法test,那么这个异步请求会一层一层的请求test方法，每请求一次就会投递一个异步请求，
     * @note 这样子就会投递无数个异步请求，所以建议不要使用异步http客户端请求包含异步请求的方法。这样子很危险的。
     */
    public static function sendRequest(mixed $cli, string $request, callable $success = null, callable $fail = null, string $remoteAddress = '', array $oldParams = [])
    {
        /** 首先保存原始数据 */
        Epoll::$asyncRequestData[(int)$cli] = $oldParams;
        /** 创建一个可写事件 */
        Epoll::dealWriteEvent($cli, $request, $fail);
        /** 添加一个可读事件 */
        Epoll::dealReadEvent($cli, $success, $remoteAddress, $fail);
        /** 最后需要把事件添加到服务的event事件 */
        Epoll::$serveEvent->add();
    }

    /**
     * 创建可写事件
     * @param mixed $cli
     * @param string $request
     * @param callable|null $fail
     * @return void
     */
    private static function dealWriteEvent(mixed $cli, string $request, callable $fail = null)
    {
        /** 语法 ：EventBase cli flag(write,read,persist) 回调，param */
        $client_event_write = new \Event(Epoll::$event_base, $cli, \Event::WRITE | \Event::PERSIST, function ($cli) use ($request, $fail) {
            /** 如果没有发送数据则发送请求给对面服务端 */
            if (empty(Epoll::$write[(int)$cli])) {
                $res = fwrite($cli, $request, strlen($request));
                /** 发送失败 */
                if (!$res) {
                    /** 如果用户定义了失败请求回调 */
                    if ($fail) {
                        try {
                            call_user_func($fail, new \RuntimeException("发送数据失败，请检查目标接口是否正常", 500));
                        }catch (\RuntimeException|\Exception $exception){
                            /** 用户处理异常的时候可能会抛出异常 */
                            self::dumpError($exception);
                        }

                        /** 释放资源 */
                        self::unsetResource($cli);
                    }
                } else {
                    /** 标记已发送过数据 */
                    Epoll::$write[(int)$cli] = 1;
                }
            }
        }, $cli);
        /** 将构建的客户端事件添加到Event当中 */
        $client_event_write->add();
        /** 所有的事件都必须保存到进程中，否则无效 ，导致服务无法运行或者直接卡死 */
        Epoll::$events[-(int)$cli] = $client_event_write;
    }


    /**
     * 创建可读事件
     * @param mixed $cli
     * @param callable|null $success
     * @param string $remoteAddress
     * @return void
     * @note 这里有一个问题，feof方法是判断资源是否读取结束。只能用来客户端读取服务端资源，当因为服务端发送完数据后，就会关闭，客户端使用这个函数没有问题。
     * @note 但是服务端不能用来判断读取客户端是否结束，因为客户端要一直保持连接并等待服务端返回数据
     */
    private static function dealReadEvent(mixed $cli, callable $success = null, string $remoteAddress = '127.0.0.1:8000', callable $fail = null)
    {
        /** 创建一个可读事件 */
        $client_event_read = new \Event(Epoll::$event_base, $cli, \Event::READ | \Event::PERSIST, function ($cli) use ($success, $remoteAddress, $fail) {
            /** 客户端连接再添加监听可读事件，读取客户端连接的数据，这里客户端可以用feof判断是否读取完成，这个feof是作为客户端读取文件， */
            /** selector 不能使用feof判断文件是否读取完成，否则进程卡死 */
            $buffer = '';
            /** 根据连接的类型不同，读取数据的方式也不同，这里是一个坑，必须区别连接类型来读取数据，如果异步客户端也按照服务端的方式读取数据，就会出现数据不完整的情况，特别是没有告诉数据长度的情况 */
            if (empty(Epoll::$asyncRequestData[(int)$cli])) {
                /** 1，作为服务端的时候没有保存原始数据 使用长度判断是否接收完所有数据 */
                $flag = true;
                /** 这个方法读物的数据长度不对 */
                $length = 10240;
                /** 是否上传文件 */
                $post = false;
                /** 初始化头部的长度 */
                $headerLength = 0;
                /** 标记接收数据的开始时间 */
                $startTime = time();
                while ($flag) {
                    $_content = fread($cli, 10240);
                    /** 这里涉及到tcp通信的问题，当数据包很大的时候，tcp会自动分包，那么一个文件会被分隔成多个数据包传输，所以这里需要验证数据包的大小 */
                    if (stripos($_content,'multipart/form-data; boundary=')){
                        /** 说明是传输文件过来 */
                        $post = true;
                        preg_match("/Content-Length: (?<content_length>\d+)/", $_content, $matches);
                        $length = $matches["content_length"];
                        /** 处理数据获取头部的长度 */
                        $small_request = explode("\r\n\r\n",$_content);
                        $headerRaw = $small_request[0];
                        $headerLength = strlen($headerRaw);

                        /** 以下这一段代码是查询boundary的，不过这里不需要了 */
//                        $array = explode("\r\n",$small_request[0]);
//                        foreach ($array as  $header_content){
//                            $pattern = '/Content-Type: (.*)$/';
//                            preg_match($pattern, $header_content, $matches);
//                            if (isset($matches[1])){
//                                $contentTypeArray = explode('boundary=',$matches[1]);
//                                $boundary = end($contentTypeArray);
//                            }
//                        }
                    }

                    $buffer = $buffer . $_content;

                    /** 如果是传输文件过来 */
                    if ($post){
                        /** 这里是验证body的长度 */
                        if ((strlen($buffer)-$headerLength)>=$length){
                            /** 如果body的长度达到了header中的content-length 则说明已经接收完毕了 */
                            $flag = false;
                        }else{
                            if ((time()-$startTime)>self::$maxRequestTime){
                                /** 如果超过最大等待时间，还没有发送完数据，那么直接通知客户端请求超时，并清空已接收到的数据 */
                                fwrite($cli, response('<h1>Time Out</h1>', 408));
                                $flag = false;
                                $buffer = '';
                            }
                        }
                    }elseif (strlen($_content) < 10240) {
                        /** 如果不是传输文件，那么只要接收的数据长度小于规定长度，则说明数据接受完成了 */
                        $flag = false;
                    }
                }
            } else {
                /** 2，作为客户端的时候 ，直接把服务端当成资源读取，使用feof判断是否接收完所有数据 */
                while (!feof($cli)) {
                    $buffer .= fread($cli, 1024);
                }
            }
            /** 如果用户输入为空或者输入不是资源 */
            if (!$buffer) {
                //var_dump($buffer);
                /** 如果是异步客户端，还需要通知用户 */
                if (isset(Epoll::$asyncRequestData[(int)$cli])) {
                    if ($fail) {
                        try {
                            call_user_func($fail, new \RuntimeException("http连接失败，或响应内容为空，关闭连接", 500));
                        }catch (\Exception|\RuntimeException $exception){
                            /** 用户处理失败回调的时候，可能抛出异常 */
                            self::dumpError($exception);
                        }
                    }
                }
                /** 释放资源 */
                Epoll::unsetResource($cli);

            } else {
                //var_dump($buffer);
                /** 处理http响应 */
                Epoll::dealRequestResponse($cli, $buffer, $success, $remoteAddress,$fail);
            }

        }, $cli);
        /** 将事件添加到event */
        $client_event_read->add();
        /** 将所有的事件都保存到进程中 */
        Epoll::$events[((int)$cli)] = $client_event_read;
    }

    /**
     * 处理http异步请求的响应
     * @param mixed $val
     * @param string $buffer
     * @return void
     */
    private static function dealRequestResponse(mixed $val, string $buffer, callable $success = null, string $remoteAddress = '127.0.0.1:8000',callable $fail=null)
    {
        /** 异步客户端 */
        if (!empty(Epoll::$asyncRequestData[(int)$val])) {
            /** 调用用户的回调 */
            $request = Container::set(Request::class, [$buffer, $remoteAddress]);
            if (($request->getStatusCode() > 299) && ($request->getStatusCode() < 400)) {
                /** 取出原始数据 */
                $oldParams = Epoll::$asyncRequestData[(int)$val] ?? [];
                /** 获取原始参数 */
                list($host, $method, $params, $query, $header, $success, $fail) = $oldParams;
                /** 获取新的域名 */
                $host = $request->header('location');
                /** 释放资源 */
                Epoll::unsetResource($val);
                /** 发送新的请求 */
                HttpClient::requestAsync($host, $method, $params, $query, $header, $success, $fail);
            } else {
                try {
                    /** 调用用户的回调 */
                    call_user_func($success, $request);
                }catch (\Exception|\RuntimeException $exception){
                    /** 捕获系统异常 */
                    Epoll::dumpError($exception);
                }

                /** 释放资源 */
                Epoll::unsetResource($val);
            }
        } else {
            /** 作为http服务器的时候，走这一条路处理 */
            /** 正常读取到数据,触发消息接收事件,响应内容，如果读取的内容不为空，并且设置了onMessage回调函数 */
            if (!empty($buffer) && is_callable($success)) {
                try {
                    /** 传入连接，接收的值到回调函数 */
                    call_user_func($success, $val, $buffer, $remoteAddress);
                }catch (\Exception|\RuntimeException $exception){
                    self::dumpError($exception);
                }
            }
        }
    }

    /**
     * 打印系统异常信息
     * @param $exception
     * @return void
     * @note 应该记录到日志的
     */
    private static function dumpError($exception){
        //var_dump("发生错误",$exception->getCode(),$exception->getFile(),$exception->getLine(),$exception->getMessage());
        dump_error($exception);
    }

    /**
     * 释放资源
     * @param $cli
     * @return void
     * @note 防止内存溢出
     */
    private static function unsetResource($cli)
    {
        /** 清理写事件 */
        if (!empty(Epoll::$events[(int)$cli])) {
            /** 暂停事件，仅仅只是暂停，但是依然保存在内存中 */
            Epoll::$events[(int)$cli]->del();
            /** 释放资源，从内存中清除 */
            Epoll::$events[(int)$cli]->free();
        }
        /** 清理写事件 */
        if (!empty(Epoll::$events[-(int)$cli])) {
            /** 暂停事件 */
            Epoll::$events[-(int)$cli]->del();
            /** 释放资源 */
            Epoll::$events[-(int)$cli]->free();
        }

        /** 清理读事件 */
        if (!empty(Epoll::$events[(int)$cli])) {
            /** 暂停事件 */
            Epoll::$events[(int)$cli]->del();
            /** 释放资源 */
            Epoll::$events[(int)$cli]->free();
        }
        /** 释放写事件 */
        unset(Epoll::$events[(int)$cli]);
        /** 释放读事件 */
        unset(Epoll::$events[(-1) * ((int)$cli)]);
        /** 释放读标记 */
        unset(Epoll::$write[(int)$cli]);
        /** 释放原始数据 */
        unset(Epoll::$asyncRequestData[(int)$cli]);
        /** 关闭连接 */
        fclose($cli);
    }

    /** 启动http服务 */
    private function run()
    {
        /** 执行事件循环 */
        Epoll::$event_base->loop();//
    }

    /** 启动服务 */
    public function start()
    {
        $this->fork();
    }

    /** 创建子进程 */
    private function fork()
    {
        /** 配置和运行模式  */
        global $_server_num,$_daemonize;
        /** 后台守护进程模式 */
        if ($_daemonize){
            for ($i = 1; $i <= $_server_num; $i++) {
                /** @var int $pid 创建子进程 ,必须在loop之前创建子进程，否则loop会阻塞其他子进程 */
                $pid = \pcntl_fork();
                if ($pid==0) {
                    cli_set_process_title("xiaosongshu_http");
                    $this->run();exit;
                }
            }
        }else{
            /** 调试模式 */
            $this->run();
        }

    }
}
