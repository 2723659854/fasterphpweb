<?php

namespace Root\Lib;

/**
 * @purpose ws连接
 * @note 心跳检测，需要使用定时器每隔5秒检测一次客户端是否发送了心跳，若没有发送心跳，则断开连接，
 * 这里定时器需要单独开一个进程，同时客户端心跳信息单独保存到一个静态数组中，直接使用一个while循环就行了
 * @note 客户端分组，需要用户自己创建一个数组保存给个分组的用户
 */
abstract class WsService
{

    /** 监听地址 */
    public string $host = '0.0.0.0';
    /** 监听端口 */
    public int $port = 9501;
    /** 所有的用户*/
    private array $users = [];
    /** 所有的连接 */
    private array $sockets = [];

    /** 存所有的客户端事件 */
    public array $events = [];

    /**  整个服务的事件 */
    public  $event;

    /**  $event_base eventBase实例 使用的epoll模型 */
    public  $event_base;

    /**  tcp 服务 */
    public  $serv;

    /** @var string $protocol 通信协议 */
    public string $protocol = 'tcp';


    /**
     * 启动服务
     * @param $param
     * @return void
     * @note 用于windows启动
     */
    public function handle($param)
    {
        /** 根据传入的配置 设置需要监听的ip和端口 */
        $this->host = $param['host'] ?? '0.0.0.0';
        $this->port = $param['port'] ?? 9501;
        /** 启动服务*/
        $this->start();
    }

    /**
     * 启动服务
     * @return mixed
     */
    public function start()
    {
        if (class_exists('\EventBase')) {
            $_has_epoll = (new \EventBase())->getMethod() == 'epoll';
        } else {
            $_has_epoll = false;
        }
        /** 如果当前环境支持Events事件 */
        if ($_has_epoll) {
            $this->startEpollWebsockets();
        } else {
            /** 当前环境不支持events事件 */
            $this->startSelectWebsockets();
        }
    }

    /**
     * 启动epoll服务
     * @return void
     */
    public function startEpollWebsockets()
    {
        /** 首先创建ws服务 */
        $this->makeEpollWebSocket();
        /** 添加事件 */
        $this->event->add();
        /** 执行事件循环 */
        $this->event_base->loop();
    }

    /**
     * 构建selectWs服务
     * @return void
     */
    public function startSelectWebsockets()
    {
        /** 首先开启服务端监听 */
        $master = $this->makeSelectWebSocket($this->host, $this->port);
        /** 保存服务端连接 ，必须保存保存到内存中，否则后面的连接马上就销毁，无法建立连接 */
        $this->sockets = array($master);
        while (true) {
            /** 将所有的连接复制给change */
            $read = $write = $this->sockets;
            /** 这里使用了select模型监听io读写事件 */
            /** 客户端连接写入数据后select需要手动遍历连接， */
            stream_select($read, $write, $except, 1);
            /** 遍历每一个连接 */
            foreach ($read as $socket) {

                /** 如果是服务端连接 */
                if ($socket == $master) {
                    /** 读取服务端连接的数据 socket_accept需要配合socket_create， socket_bind，socket_listen,socket_recv， socket_write，socket_close使用*/
                    //$client = socket_accept($master);
                    /** 改版后 ，stream_socket_accept 配合fread,fwrite，fclose使用*/
                    $client = stream_socket_accept($master, 0, $remote_address); //阻塞监听 设置超时0，并获取客户端地址
                    /** 没有数据，说明没有新的客户端发起连接请求 */
                    if ($client < 0) {
                        continue;
                    } else {
                        /** 保存客户端到内存 */
                        $this->connect($client, $remote_address);
                    }
                } else {
                    /** 如果是客户端，则读取连接中的数据 */
                    //$bytes = @socket_recv($socket, $buffer, 2048, 0);
                    $buffer = '';
                    $flag = true;
                    while ($flag) {
                        $_content = fread($socket, 1024);
                        if (strlen($_content) < 1024) {
                            $flag = false;
                        }
                        $buffer = $buffer . $_content;
                    }
                    $bytes = $buffer;

                    if ($bytes == 0) {
                        /** 如果客户端没有数据，则断开客户端连接 */
                        $this->disconnect($socket);
                    } else {
                        /** 通过socket获取用户信息 */
                        $user = $this->getuserbysocket($socket);

                        if (!$user->handshake) {
                            /** 如果没有握手，则先握手 */
                            $this->dohandshake($user, $buffer);
                            /** 调用用户自定义的onConnect方法 */
                            try {
                                $this->onConnect($socket);
                            } catch (\Exception|\RuntimeException $exception) {
                                $this->onError($socket, $exception);
                            }
                        } else {
                            /** 处理客户端发送的数据 */
                            $this->process($user, $buffer);
                        }
                    }
                }
            }
        }
    }

    /**
     * 构建epollWs服务
     * @return void
     */
    public function makeEpollWebSocket()
    {
        /** @var string $listeningAddress 拼接监听地址 */
        $listeningAddress = $this->protocol . '://' . $this->host . ':' . $this->port;
        /** 创建tcp服务器套接字 */
        $this->serv = stream_socket_server($listeningAddress, $errno, $error);
        /** 如果有错误则直接退出 */
        $errno && exit($error);
        /** 设置为异步，不然fread,stream_socket_acceptd等会堵塞 */
        stream_set_blocking($this->serv, 0);
        /** 获取eventBase实例 因为本文件使用了命名空间 所以这里必须指定eventbase 和 event 类的名命名空间是根目录\ 否则会在本类所在的命名空间查找，会报错，找不到这个类 */
        $this->event_base = new \EventBase();
        /** 建立事件监听服务器socket可读事件， 获取event实例，这个是获取php的event扩展的基类 */
        /** 在react中，SyntheticEvent在调用事件回调之后该对象将被重用，并且其所有属性都将无效。如果要以异步方 式访问事件属性，则应调用event.persist()事件，这将从池中删除事件，并允许用户代码保留对该事件的引用。 */
        $this->event = new \Event($this->event_base, $this->serv, \Event::READ | \Event::PERSIST, function ($serv) {
            /** 获取新的连接 stream_socket_accept语法：socket连接，超时，客户端地址 */
            $cli = @stream_socket_accept($serv, 0, $remote_address);

            /** 如果有连接 */
            if ($cli) {
                /** 设置为异步 */
                stream_set_blocking($cli, 0);
                /** 保存客户端连接和ip */
                $this->connect($cli, $remote_address);
                /** 将新的客户端连接投入到事件，构建客户端事件， */
                $client_event = new \Event($this->event_base, $cli, \Event::READ | \Event::PERSIST, function ($cli) use ($remote_address) {
                    /** 客户端连接再添加监听可读事件，读取客户端连接的数据 */
                    $buffer = '';
                    $flag = true;
                    while ($flag) {
                        $_content = fread($cli, 1024);
                        if (strlen($_content) < 1024) {
                            $flag = false;
                        }
                        $buffer = $buffer . $_content;
                    }
                    /** 如果用户输入为空或者输入不是资源 */
                    if (!$buffer || !is_resource($cli)) {
                        /** 关闭客户端 */
                        $this->disconnect($cli);
                        /** 释放事件 */
                        unset($this->events[(int)$cli]);
                        unset($cli);
                        return;
                    }
                    /** 正常读取到数据,触发消息接收事件,响应内容，如果读取的内容不为空，并且设置了onMessage回调函数 */
                    if (!empty($buffer)) {
                        /** 传入连接，接收的值到回调函数 */
                        $user = $this->getuserbysocket($cli);
                        if (!$user->handshake) {
                            /** 握手 */
                            $this->dohandshake($user, $buffer);
                            /** 调用用户自定义的onConnect方法 */
                            try {
                                $this->onConnect($cli);
                            } catch (\Exception|\RuntimeException $exception) {
                                $this->onError($cli, $exception);
                            }
                        } else {
                            /** 处理用户信息 */
                            $this->process($user, $buffer);
                        }
                    }
                }, $cli);
                /** 将构建的客户端事件添加到epoll当中 */
                $client_event->add();
                /** 添加事件到全局数组,不然无法持久化连接,这是个大坑，这里是把每一个连接的事件都保存,这里必须持久化，否则无法回复消息，无法读取消息 */
                $this->events[(int)$cli] = $client_event;
            }
        }, $this->serv);
    }

    /**
     * 消息解码
     * @param $buffer
     * @return string|null
     * @note 这个解码难度有点高，属于二进制解码，将二进制数据转化为明文数据，密码都是4位，
     * @note socket消息格式：表头+长度+密码+密文
     */
    private function decode($buffer)
    {
        $decoded = null;
        /** 获取消息长度：返回buffer的第一个asc码 ，然后和127 进行补码运算 */
        /** "&" 按位与运算：只有对应的两个二进位均为1时，结果位才为1，否则为0。 参考地址：https://blog.csdn.net/alashan007/article/details/89885879 */
        $len = @ord($buffer[1]) & 127;
        /** 长度为126 */
        if ($len === 126) {
            /** 获取masks，就是密码 */
            $masks = substr($buffer, 4, 4);
            /** 获取data，加密后的数据 */
            $data = substr($buffer, 8);
        } else if ($len === 127) {
            /** 获取masks */
            $masks = substr($buffer, 10, 4);
            /** 获取data */
            $data = substr($buffer, 14);
        } else {
            /** 获取masks */
            $masks = substr($buffer, 2, 4);
            /** 获取data */
            $data = substr($buffer, 6);
        }
        /** 逐个字节解码 */
        for ($index = 0; $index < strlen($data); $index++) {
            /** 消息内容解码方式，"^" 按位异或运算：参与运算的两数各对应的二进位相异或，当两对应的二进位相异时，结果为1。
             * 参考地址：https://blog.csdn.net/alashan007/article/details/89885879
             */
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }
        return $decoded;
    }

    /**
     * 消息编码
     * @param $message
     * @return string
     * @note 将明文数据信息，编码成二进制，
     */
    private function encode($message)
    {
        /** 首先计算消息长度 */
        $len = strlen($message);
        /** 小于125位*/
        if ($len <= 125) {
            return "\x81" . chr($len) . $message;
        } else if ($len <= 65535) {
            /** 将126转换为字符 ~ ,然后将长度转化为二进制  */
            return "\x81" . chr(126) . pack("n", $len) . $message;
        } else {
            return "\x81" . chr(127) . pack("xxxxN", $len) . $message;
        }
    }

    /**
     * 处理客户端消息
     * @param $user
     * @param $msg
     * @return void
     */
    private function process($user, $msg)
    {
        /** 首先 将二进制数据转化为明文数据 */
        $action = $this->decode($msg);
        /** 调用用户定义的message方法处理业务逻辑 */
        try {
            $this->onMessage($user->socket, $action);
        } catch (\Exception|\RuntimeException $exception) {
            $this->onError($user->socket, $exception);
        }

    }

    /**
     * 发送消息
     * @param $socket
     * @param $msg
     * @return void
     * @note 将消息发送给客户端
     */
    protected function sendTo($socket, $msg)
    {
        if (!is_string($msg)) {
            $msg = json_encode($msg);
        }
        $this->send($socket, $msg);
    }

    /**
     * 广播给所有客户端
     * @param $msg
     * @return void
     */
    protected function sendToAll($msg)
    {
        if (!is_string($msg)) {
            $msg = json_encode($msg);
        }
        foreach ($this->users as $user) {
            $this->send($user->socket, $msg);
        }
    }

    /**
     * 向客户端发送消息
     * @param $client
     * @param $msg
     * @return void
     * @note 将消息编码然后发送给客户端连接
     */
    private function send($client, $msg)
    {
        /** 对消息进行编码 */
        $msg = $this->encode($msg);
        /** 发送消息  */
        //socket_write($client, $msg, strlen($msg));
        @fwrite($client, $msg, strlen($msg));
    }

    /**
     * 构建websocket服务
     * @param $address
     * @param $port
     * @return resource|\Socket|void
     * @note 这个是select模型
     */
    private function WebSocket1($address, $port)
    {
        /** 创建一个通讯节点，套字节语法： IPv4 网络协议，流式套接字。使用全双工协议tcp协议 参考：https://blog.csdn.net/sn_qmzm521/article/details/80756771 */
        $master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("socket_create() failed");
        /** 设置节点信息，语法：socket节点，socket协议号，端口复用，同步，参考地址:https://www.kancloud.cn/a173512/php_note/2399141 */
        socket_set_option($master, SOL_SOCKET, SO_REUSEADDR, 1) or die("socket_option() failed");
        /** 套字节绑定本地的ip和端口 */
        socket_bind($master, $address, $port) or die("socket_bind() failed");
        /** 开始监听端口 语法：套字节，队列长度（tcp连接可以排队的长度，因为建立连接不是一瞬间就成功，是有三次握手的一个过程，在上一个连接握手的时候，下一个请求发起握手，这个时候
         * 就需要排队，这里就需要设置排队的长度，参考地址：https://blog.csdn.net/kety2001/article/details/7953921）
         */
        socket_listen($master, 20) or die("socket_listen() failed");
        return $master;
    }

    /**
     * 创建socket服务
     * @param $address
     * @param $port
     * @return false|resource
     */
    public function makeSelectWebSocket($address, $port)
    {
        /** 配置socket流参数 */
        $context = stream_context_create();
        /** 设置端口复用 */
        stream_context_set_option($context, 'socket', 'so_reuseport', 1);
        stream_context_set_option($context, 'socket', 'so_reuseaddr', 1);
        /** 设置服务端：监听地址+端口 */
        $socket = stream_socket_server('tcp://' . $address . ':' . $port, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
        /** 设置非阻塞，语法是关闭阻塞 */
        stream_set_blocking($socket, 0);
        return $socket;
    }

    /**
     * 连接客户端
     * @param $socket
     * @return void
     * @note 保存客户端连接
     */
    private function connect($socket, $remote_address)
    {
        $user = new \stdClass();
        $user->id = uniqid();
        $user->socket = $socket;
        $user->handshake = false;
        $user->remote_address = $remote_address;
        /** 保存客户端 */
        array_push($this->users, $user);
        /** 保存连接 */
        array_push($this->sockets, $socket);
    }

    /**
     * 通过socket获取用户信息
     * @param $socket
     * @return mixed|null
     */
    protected function getUserInfoBySocket($socket)
    {
        return $this->getuserbysocket($socket);
    }

    /**
     * 根据uid获取客户端
     * @param $uid
     * @return mixed|null
     */
    protected function getUserInfoByUid($uid)
    {
        $found = null;
        foreach ($this->users as $user) {
            if ($user->id == $uid) {
                $found = $user;
                break;
            }
        }
        return $found;
    }

    /**
     * 关闭客户端连接
     * @param $socket
     * @return void
     */
    protected function close($socket)
    {
        $this->disconnect($socket);
    }

    /**
     * 关闭客户端连接
     * @param $socket
     * @return void
     *
     */
    private function disconnect($socket)
    {
        /** 吊起用户自定义 的onClose方法 */
        try {
            $this->onClose($socket);
        } catch (\Exception|\RuntimeException $exception) {
            $this->onError($socket, $exception);
        }
        $found = null;
        $n = count($this->users);
        for ($i = 0; $i < $n; $i++) {
            if ($this->users[$i]->socket == $socket) {
                $found = $i;
                break;
            }
        }
        if (!is_null($found)) {
            /** 删除这个用户 */
            array_splice($this->users, $found, 1);
        }
        /** 关闭这个连接 */
        $index = array_search($socket, $this->sockets);
        //socket_close($socket);
        fclose($socket);
        if ($index >= 0) {
            array_splice($this->sockets, $index, 1);
        }
        /** 如果这是epoll，则需要清理这个连接的读事件 */
        if (isset($this->events[(int)$socket])){
            $this->events[(int)$socket]->del();
            /** 释放内存 */
            unset($this->events[(int)$socket]);
        }
    }

    /**
     * 握手
     * @param $user
     * @param $buffer
     * @return bool
     * @note 这个是建立socket连接的关键，首先是接受到http连接，然后http连接里面 有升级websocket的要求，服务端对key加密后返回给客户端，客户端
     * 会使用自己的key和服务端返回的key进行对比，如果相等，则建立连接成功，否则建立连接失败。
     */
    private function dohandshake(&$user, $buffer)
    {
        /** 解码http请求头部信息 */
        list($resource, $host, $upgrade, $connection, $key, $protocol, $version, $origin, $data) = $this->getheaders($buffer);
        /** 将获取到的key和常量258EAFA5-E914-47DA-95CA-C5AB0DC85B11拼接后加密，这个常量是文档约定俗成的，是一个常量 */
        $acceptkey = base64_encode(sha1($key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true));
        /** 握手需要返回给客户端的数据，并允许跨域,这个http报文不允许有多余的空格换行符，需要特别注意的，否则无法完成握手 */
        $upgrade = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: $acceptkey\r\nAccess-Control-Allow-Credentials: true\r\nAccess-Control-Allow-Origin: $origin\r\nAccess-Control-Allow-Methods: *\r\nAccess-Control-Allow-Headers: *\r\n\r\n";
        /** 将消息返回给客户端 */
        @fwrite($user->socket, $upgrade, strlen($upgrade));
        /** 标记为已完成握手 */
        $user->handshake = true;
        return true;
    }

    /**
     * 解析http头部
     * @param $req
     * @return array|null[]
     * @使用正则解析http头部
     */
    private function getheaders($req)
    {
        $resource = $host = $upgrade = $connection = $key = $protocol = $version = $origin = $data = null;
        if (preg_match("/GET (.*) HTTP/", $req, $match)) {
            $resource = $match[1];
        }
        if (preg_match("/Host: (.*)\r\n/", $req, $match)) {
            $host = $match[1];
        }
        if (preg_match("/Upgrade: (.*)\r\n/", $req, $match)) {
            $upgrade = $match[1];
        }
        if (preg_match("/Connection: (.*)\r\n/", $req, $match)) {
            $connection = $match[1];
        }
        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $req, $match)) {
            $key = $match[1];
        }
        if (preg_match("/Sec-WebSocket-Protocol: (.*)\r\n/", $req, $match)) {
            $protocol = $match[1];
        }
        if (preg_match("/Sec-WebSocket-Version: (.*)\r\n/", $req, $match)) {
            $version = $match[1];
        }
        if (preg_match("/Origin: (.*)\r\n/", $req, $match)) {
            $origin = $match[1];
        }
        if (preg_match("/\r\n(.*?)\$/", $req, $match)) {
            $data = $match[1];
        }
        return [$resource, $host, $upgrade, $connection, $key, $protocol, $version, $origin, $data];
    }

    /**
     * 通过用户获取客户端连接
     * @param $socket
     * @return mixed|null
     */
    private function getuserbysocket($socket)
    {
        $found = null;
        foreach ($this->users as $user) {
            if ($user->socket == $socket) {
                $found = $user;
                break;
            }
        }
        return $found;
    }

    /**
     * 获取所有的用户
     * @return array
     */
    protected function getAllUser()
    {
        return $this->users;
    }

    /**
     * 连接成功事件
     * @param $socket
     * @return mixed
     */
    public abstract function onConnect($socket);

    /**
     * 接收到消息事件
     * @param $socket
     * @param $message
     * @return mixed
     */
    public abstract function onMessage($socket, $message);


    /**
     * 断开连接事件
     * @param $socket
     * @return mixed
     */
    public abstract function onClose($socket);

    /**
     * 异常
     * @param $socket
     * @param \Exception $exception
     * @return mixed
     */
    public abstract function onError($socket, \Exception $exception);
}

