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

class Worker
{
    /** 服务端 */
    protected $socket = NULL;
    /** 设置连接回调事件 */
    public $onConnect = NULL;
    /** 设置接收消息回调 */
    public $onMessage = NULL;
    /** 存放所有socket */
    public $allSocket;

    /** 初始化 */
    public function __construct($socket_address)
    {
        /** 设置服务端：监听地址+端口 */
        $this->socket = stream_socket_server($socket_address);
        /** 设置非阻塞，语法是关闭阻塞 */
        stream_set_blocking($this->socket, 0);
        /** 将服务端保存 */
        $this->allSocket[(int)$this->socket] = $this->socket;
    }

    /** 启动服务 */
    public function start()
    {
        /** 获取配置文件,创建子进程，这里省了 */
        $this->fork();
    }

    /** 子进程 */
    public function fork()
    {
        /** 子进程负责接收客户端请求 */
        $this->accept();
    }

    /** 接收客户端消息 */
    public function accept()
    {
        /** 创建多个子进程阻塞接收服务端socket */
        while (true) {
            /** 初始化需要监测的可写入的客户端，需要排除的客户端都为空 */
            $write = $except = [];
            /** 需要监听socket */
            $read = $this->allSocket;
            //状态谁改变
            /** 使用stream_select函数监测可读，可写的连接，如果某一个连接接收到数据，那么数据就会改变，select使用的foreach遍历所有的连接，查看是否可读，就是有消息的时候标记为可读 */
            /** 这里设置了阻塞60秒 */
            stream_select($read, $write, $except, 60);
            //怎么区分服务端跟客户端
            /** 遍历所有可读的连接 */
            foreach ($read as $val) {
                //当前发生改变的是服务端，有连接进入
                /** 如果这个可读的连接是服务端，那么说明是有新的客户端连接进来 */
                if ($val === $this->socket) {
                    /** 读取服务端接收到的 消息，这个消息的内容是客户端连接 ，stream_socket_accept方法负责接收客户端连接 */
                    $clientSocket = stream_socket_accept($this->socket); //阻塞监听
                    //触发事件的连接的回调
                    /** 如果这个客户端连接不为空，并且本服务的onConnect是回调函数 */
                    if (!empty($clientSocket) && is_callable($this->onConnect)) {
                        /** 把客户端连接传递到onConnect回调函数 */
                        call_user_func($this->onConnect, $clientSocket);
                    }
                    /** 将这个客户端连接保存，目测这里如果不保存，应该是无法发送和接收消息的，就是要把所有的连接都保存在内存中 */
                    $this->allSocket[(int)$clientSocket] = $clientSocket;
                } else {

                    $buffer = '';
                    $flag    = true;
                    while ($flag) {
                        $_content = fread($val, 1024);
                        if (strlen($_content) < 1024) {
                            $flag = false;
                        }
                        $buffer = $buffer. $_content;
                    }
                    /** 如果是客户端连接可读 */
                    /** 从连接当中读取客户端的内容 */
                    //$buffer = fread($val, 1024);
                    /** 如果数据为空，或者为false,不是资源类型 */
                    if (empty($buffer)) {
                        /** feof：如果检测已经到了文件末尾，就是客户端连接没有内容了，并且客户端连接不是资源类型 is_resource：检测打开的文件是否是资源类型 */
                        if (feof($val) || !is_resource($val)) {
                            /** 触发关闭事件，关闭这个客户端 */
                            fclose($val);
                            /** 移除这个连接 */
                            unset($this->allSocket[(int)$val]);
                            continue;
                        }
                    }
                    /** 正常读取到数据,触发消息接收事件,响应内容，如果读取的内容不为空，并且设置了onMessage回调函数 */
                    if (!empty($buffer) && is_callable($this->onMessage)) {
                        /** 传入连接，接收的值到回调函数 */
                        call_user_func($this->onMessage, $val, $buffer);
                    }
                }
            }
        }

    }

}
