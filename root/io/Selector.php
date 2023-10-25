<?php

namespace Root\Io;

use Root\Lib\Container;
use Root\Request;

/**
 * @purpose select的IO多路复用模型
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
    /** 处理读取的缓存 */
    private static $buffer = [];
    /** 初始化 */
    public function __construct()
    {
        global $_port;
        $this->port = $_port ?: '8000';
        /** @var string $listeningAddress 拼接监听地址 */
        $listeningAddress = $this->protocol . '://' . $this->host . ':' . $this->port;
        $contextOptions['ssl'] = [
            'verify_peer' => false,
            'verify_peer_name' => false
        ];
        /** 配置socket流参数 */
        $context = stream_context_create($contextOptions);
        /** 设置端口复用 */
        stream_context_set_option($context, 'socket', 'so_reuseport', 1);
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
     */
    public static function sendRequest(mixed $socket, string $request, callable $success = null, callable $fail = null,string $remote_address='')
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
    }

    /** 接收客户端消息 */
    public function accept()
    {
        /** 创建多个子进程阻塞接收服务端socket 这个while死循环 会导致for循环被阻塞，不往下执行，创建了子进程也没有用，直接在第一个子进程哪里阻塞了 */
        while (true) {
            /** 初始化需要监测的可写入的客户端，需要排除的客户端都为空 */
            $except = [];
            /** 需要监听socket */
            $write = $read = Selector::$allSocket;
            //状态谁改变
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
    public  function dealReadEvent(array $read){
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
                    call_user_func($this->onConnect, $clientSocket, $remote_address);
                }
                /** 将这个客户端连接保存，目测这里如果不保存，应该是无法发送和接收消息的，就是要把所有的连接都保存在内存中 */
                Selector::$allSocket[(int)$clientSocket] = $clientSocket;
                /** 单独用一个数组保存客户端ip地址和端口信息 */
                Selector::$clientIp[(int)$clientSocket] = $remote_address;
            } else {
                /** selector 不能使用feof判断文件是否读取完成，否则进程卡死 */
                $buffer = '';
                $flag = true;
                while ($flag) {
                    $_content = fread($val, 1024);
                    if (strlen($_content) < 1024) {
                        $flag = false;
                    }
                    $buffer = $buffer . $_content;
                }
                /** 从连接当中读取客户端的内容 */
                /** 如果数据为空，或者为false,不是资源类型 */
                if (empty($buffer)) {
                    /** feof：如果检测已经到了文件末尾，就是客户端连接没有内容了，并且客户端连接不是资源类型 is_resource：检测打开的文件是否是资源类型 */
                    if (feof($val) || !is_resource($val)) {
                        /** 触发关闭事件，关闭这个客户端 */
                        Selector::unsetResource($val);
                        continue;
                    }
                }
                /** 这一块的代码是用来处理异步客户端的读事件，因为接收的文件不完整，所以搞了这么大一块代码 */
                /** 正常读取到数据,触发消息接收事件,响应内容，如果读取的内容不为空，并且设置了onMessage回调函数 */
                if (!empty($buffer) && !empty(Selector::$success[(int)$val])) {
                    $_length = strlen($buffer);
                    $_end = stripos($buffer,"\r\n\r\n");
                    /** 完整的数据 */
                    if ($_end&&$_length&&($_length-$_end)>4){
                        /** 清空缓存 */
                        unset(Selector::$buffer[(int)$val]);
                        /** 调用用户的回调 */
                        call_user_func(Selector::$success[(int)$val], Container::set(Request::class,[$buffer,Selector::$clientIp[(int)$val] ?? '']));
                        /** 关闭客户端 */
                        Selector::unsetResource($val);
                    }
                    /** 开始 */
                    if ($_end&&($_end+4==$_length)&&empty(Selector::$buffer[(int)$val])){
                        Selector::$buffer[(int)$val] = $buffer;
                    }
                    /** 尾巴部分 */
                    if (!$_end){
                        $buffer = Selector::$buffer[(int)$val] . $buffer;
                        /** 清空缓存 */
                        unset(Selector::$buffer[(int)$val]);
                        /** 调用用户的回调 */
                        call_user_func(Selector::$success[(int)$val], Container::set(Request::class,[$buffer,Selector::$clientIp[(int)$val] ?? '']));
                        /** 关闭客户端 */
                        Selector::unsetResource($val);
                    }

                    /** 存在一个问题bug 只是接收了头部，没有接收body ,导致数据不完整，原因是在header和body之间有一个换行符，导致接收终止了 */
                } else {
                    /** 请求当前服务器的客户端，直接调用http服务的onMessage*/
                    if (!empty($buffer) && is_callable($this->onMessage)) {
                        /** 传入连接，接收的值到回调函数 */
                        call_user_func($this->onMessage, $val, $buffer, Selector::$clientIp[(int)$val] ?? '');
                    }
                }

            }
        }
    }

    /**
     * 处理可写连接
     * @param array $write
     * @return void
     * @note 实际处理的是异步客户端发送数据
     */
    public  function dealWriteEvent(array $write){
        /** 检查可写连接，这里是为了处理异步客户端的请求 */
        foreach ($write as $val) {
            $id = (int)$val;
            /** 如果这个客户端有需要发送的数据 */
            if (isset(Selector::$request[$id])) {
                /** 发送数据，这里的客户端是连接的其他的服务器，不是当前的http服务  */
                $res = fwrite($val, Selector::$request[$id], strlen(Selector::$request[$id]));
                if (!$res) {
                    if (isset(Selector::$fail[$id])) {
                        $function = (Selector::$fail[$id]);
                        unset(Selector::$fail[$id]);
                        call_user_func($function, throw new \RuntimeException("请求失败"));
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
    public static function unsetResource($val){
        /** 关闭这个客户端 */
        fclose($val);
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
    }

}
