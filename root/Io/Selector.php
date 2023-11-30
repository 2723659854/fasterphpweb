<?php

namespace Root\Io;

use Root\Lib\Container;
use Root\Lib\HttpClient;
use Root\Request;

/**
 * @purpose select的IO多路复用模型
 * @note 提供http服务器服务
 * @note 提供异步客户端服务
 */
class Selector
{
    /** 服务端 */
    protected $socket = NULL;

    /** 设置连接回调事件 */
    public $onConnect = NULL;

    /** 设置接收消息回调 */
    public $onMessage = NULL;

    /** 存放所有socket */
    public static $allSocket;

    /** @var string $host 监听的ip和协议 */
    public $host = '0.0.0.0';

    /** @var string $port 监听的端口 */
    public $port = '8000';

    /** @var string $protocol 通信协议 */
    public $protocol = 'tcp';
    /** 所有的客户端ip */
    private static $clientIp = [];

    /** 异步http客户端 */
    private static $success = [];
    /** 需要发送的请求 */
    private static $request = [];
    /** 异步http客户端 */
    private static $fail = [];

    /** 异步请求的 原始数据 */
    public static $asyncRequestData = [];

    /** 客户端上传数据最大请求时间 ，如果超过这个时间就断开这个连接 默认6分钟 */
    private static $maxRequestTime = 360;

    /** 初始化 */
    public function __construct()
    {
        global $_port;
        $this->port = $_port ?: '8000';
        /** @var string $listeningAddress 拼接监听地址 */
        $listeningAddress = $this->protocol . '://' . $this->host . ':' . $this->port;
        /** 不严重https证书 */
        $contextOptions['ssl'] = ['verify_peer' => false, 'verify_peer_name' => false];
        /** 配置socket流参数 */
        $context = stream_context_create($contextOptions);
        /** 设置端口复用 解决惊群效应  */
        stream_context_set_option($context, 'socket', 'so_reuseport', 1);
        /** 设置ip复用 */
        stream_context_set_option($context, 'socket', 'so_reuseaddr', 1);
        /** 设置服务端：监听地址+端口 */
        $this->socket = stream_socket_server($listeningAddress, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
        /** 设置非阻塞，语法是关闭阻塞 */
        stream_set_blocking($this->socket, 0);
        /** 将服务端保存 */
        Selector::$allSocket[(int)$this->socket] = $this->socket;
    }

    /** 启动服务 */
    public function start()
    {
        $this->accept();
    }

    /**
     * 发送异步请求
     * @param mixed $socket 客户端socket
     * @param string $request 需要发送的请求内容
     * @param callable|null $success 成功回调
     * @param callable|null $fail 失败回调
     * @param string $remote_address 远程服务端地址
     * @return void
     * @note http客户端发送异步请求
     * @note epoll和select 对于事件的处理是不同的，select可以循环套娃，比如当前系统添加一个方法test，里面异步请求本方法test,那么这个异步请求会一层一层的请求test方法，每请求一次就会投递一个异步请求，
     * @note 这样子就会投递无数个异步请求，所以建议不要使用异步http客户端请求包含异步请求的方法。这样子很危险的。
     */
    public static function sendRequest(mixed $socket, string $request, callable $success = null, callable $fail = null, string $remote_address = '', array $oldParams = [])
    {
        $id = (int)$socket;
        /** 直接把客户端添加到全局socket当中，然后使用stream_select检测这个客户端，检测到可读可写事件后再执行对应发送数据和接收数据操作 */
        Selector::$allSocket[$id] = $socket;
        /** 保存成功回调 */
        Selector::$success[$id] = $success;
        /** 保存失败回调 */
        Selector::$fail[$id] = $fail;
        /** 保存request */
        Selector::$request[$id] = $request;
        /** 保存对端服务器地址 */
        Selector::$clientIp[$id] = $remote_address;
        /** 保存原始数据 */
        Selector::$asyncRequestData[$id] = $oldParams;
    }

    /** 接收客户端消息 */
    private function accept()
    {
        /** 创建多个子进程阻塞接收服务端socket 这个while死循环 会导致for循环被阻塞，不往下执行，创建了子进程也没有用，直接在第一个子进程哪里阻塞了 */
        while (true) {
            /** 初始化需要监测的可写入的客户端，需要排除的客户端都为空 */
            $except = [];
            /** 需要监听socket */
            $write = $read = Selector::$allSocket;
            /** 使用stream_select函数监测可读，可写的连接，如果某一个连接接收到数据，那么数据就会改变，select使用的foreach遍历所有的连接，查看是否可读，就是有消息的时候标记为可读 */
            /** 这里设置了阻塞60秒 */
            stream_select($read, $write, $except, 60);
            /** 处理可读的连接 */
            $this->dealReadEvent($read);
            /** 处理可写的连接 */
            $this->dealWriteEvent($write);
        }
    }

    /**
     * 处理可读连接
     * @param array $read
     * @return void
     */
    private function dealReadEvent(array $read)
    {
        /** 遍历所有可读的连接 */
        foreach ($read as $val) {
            //当前发生改变的是服务端，有连接进入
            /** 如果这个可读的连接是服务端，那么说明是有新的客户端连接进来 */
            if ($val === $this->socket) {
                /** 读取服务端接收到的 消息，这个消息的内容是客户端连接 ，stream_socket_accept方法负责接收客户端连接 */
                $clientSocket = stream_socket_accept($this->socket, 0, $remote_address); //阻塞监听 设置超时0，并获取客户端地址

                //触发事件的连接的回调
                /** 如果这个客户端连接不为空，并且本服务的onConnect是回调函数 */
                if (!empty($clientSocket) && is_callable($this->onConnect)) {
                    /** 把客户端连接传递到onConnect回调函数 */
                    try {
                        call_user_func($this->onConnect, $clientSocket, $remote_address);
                    }catch (\Exception|\RuntimeException $exception){
                        self::dumpError($exception);
                    }

                }
                /** 将这个客户端连接保存，目测这里如果不保存，应该是无法发送和接收消息的，就是要把所有的连接都保存在内存中 */
                Selector::$allSocket[(int)$clientSocket] = $clientSocket;
                /** 单独用一个数组保存客户端ip地址和端口信息 */
                Selector::$clientIp[(int)$clientSocket] = $remote_address;
            } else {
                /** selector 不能使用feof判断文件是否读取完成，否则进程卡死 */
                $buffer = '';
                /** 根据连接的类型不同，读取数据的方式也不同，这里是一个坑，必须区别连接类型来读取数据，如果异步客户端也按照服务端的方式读取数据，就会出现数据不完整的情况，特别是没有告诉数据长度的情况 */
                if (empty(Selector::$asyncRequestData[(int)$val])) {

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
                        $_content = fread($val, 10240);
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
                                    fwrite($val, response('<h1>Time Out</h1>', 408));
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
                    while (!feof($val)) {
                        $buffer .= fread($val, 1024);
                    }
                }
                /** 从连接当中读取客户端的内容 */
                /** 如果数据为空，或者为false,不是资源类型 */
                if (empty($buffer)) {
                    /** 如果是客户端 */
                    if (!empty(Selector::$fail[(int)$val])) {
                        try {
                            call_user_func(Selector::$fail[(int)$val], new \RuntimeException("http连接失败，或响应内容为空，关闭连接", 500));
                        }catch (\RuntimeException|\Exception $exception){
                            /** 捕获处理错误的时候发生的异常 */
                            self::dumpError($exception);
                        }
                    }
                    /** 释放资源 */
                    Selector::unsetResource($val);
                    continue;
                }
                /** 异步客户端的响应数据 */
                if (!empty(Selector::$success[(int)$val])) {
                    /** 处理异步http客户端的响应 */
                    Selector::dealRequestResponse($val, $buffer);
                    /** 关闭客户端，释放资源 */
                    Selector::unsetResource($val);
                    /** 存在一个问题bug 只是接收了头部，没有接收body ,导致数据不完整，原因是在header和body之间有一个换行符，导致接收终止了 */
                } else {
                    /** http服务器接受到的消息，直接调用http服务的onMessage */
                    if (is_callable($this->onMessage)) {
                        try {
                            /** 传入连接，接收的值到回调函数 */
                            call_user_func($this->onMessage, $val, $buffer, Selector::$clientIp[(int)$val] ?? '');
                        }catch (\Exception|\RuntimeException $exception){
                            self::dumpError($exception);
                        }

                    }
                }

            }
        }
    }

    /**
     * 处理http异步请求的响应
     * @param mixed $val
     * @param string $buffer
     * @return void
     */
    private static function dealRequestResponse(mixed $val, string $buffer)
    {
        /** 调用用户的回调 */
        $request = Container::set(Request::class, [$buffer, Selector::$clientIp[(int)$val] ?? '']);
        if (($request->getStatusCode() > 299) && ($request->getStatusCode() < 400)) {
            /** 取出原始数据 */
            $oldParams = Selector::$asyncRequestData[(int)$val] ?? [];
            /** 获取原始参数 */
            list($host, $method, $params, $query, $header, $success, $fail) = $oldParams;
            /** 获取新的域名 */
            $host = $request->header('location');
            /** 发送新的请求 */
            HttpClient::requestAsync($host, $method, $params, $query, $header, $success, $fail);
        } else {
            try {
                /** 调用用户的回调 */
                call_user_func(Selector::$success[(int)$val], $request);
            }catch (\Exception|\RuntimeException $exception){
                /** 捕获系统运行发生的异常 */
                self::dumpError($exception);
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
     * 处理可写连接
     * @param array $write
     * @return void
     * @note 实际处理的是异步客户端发送数据
     */
    private function dealWriteEvent(array $write)
    {
        /** 检查可写连接，这里是为了处理异步客户端的请求 */
        foreach ($write as $val) {
            $id = (int)$val;
            /** 如果这个客户端有需要发送的数据 */
            if (isset(Selector::$request[$id])) {
                /** 发送数据，这里的客户端是连接的其他的服务器，不是当前的http服务  */
                $res = fwrite($val, Selector::$request[$id], strlen(Selector::$request[$id]));
                if (!$res) {
                    if (isset(Selector::$fail[$id])) {
                        try {
                            /** 通知用户发送请求失败 */
                            call_user_func(Selector::$fail[$id], new \RuntimeException("发送数据失败，请检查目标接口是否正常", 500));
                        }catch (\Exception | \RuntimeException $exception){
                            /** 用户处理错误的时候，抛出了异常 */
                            self::dumpError($exception);
                        }
                        /** 释放资源 */
                        Selector::unsetResource($val);
                    }
                }
                /** 发送完成后，删除request数据 */
                unset(Selector::$request[$id]);
            }
        }
    }

    /**
     * 释放资源
     * @param $val
     * @return void
     * @note 防止内存溢出
     */
    private static function unsetResource($val)
    {
        /** 移除请求内容 */
        unset(Selector::$request[(int)$val]);
        /** 移除这个连接 */
        unset(Selector::$allSocket[(int)$val]);
        /** 移除ip */
        unset(Selector::$clientIp[(int)$val]);
        /** 移除成功回调 */
        unset(Selector::$success[(int)$val]);
        /** 移除失败回调 */
        unset(Selector::$fail[(int)$val]);
        /** 删除原始数据 */
        unset(Selector::$asyncRequestData[(int)$val]);
        if (is_resource($val)){
            fclose($val);
        }
        unset($val);
    }

}
