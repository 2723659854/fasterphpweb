<?php
namespace Root\Lib;

/**
 * @purpose ws连接
 * @note 心跳检测，需要使用定时器每隔5秒检测一次客户端是否发送了心跳，若没有发送心跳，则断开连接，
 * 这里定时器需要单独开一个进程，同时客户端心跳信息单独保存到一个静态数组中，直接使用一个while循环就行了
 * @note 客户端分组，需要用户自己创建一个数组保存给个分组的用户
 */
abstract class Websocket
{

    /** 监听地址 */
    public string $host = '0.0.0.0';
    /** 监听端口 */
    public int $port = 9501;
    /** 所有的用户*/
    private array $users = [];
    /** 所有的连接 */
    private array $sockets = [];

    /**
     * 启动服务
     * @return mixed
     */
    public function start()
    {
        /** 首先开启服务端监听 */
        $master        = $this->WebSocket($this->host, $this->port);
        /** 保存服务端连接 ，必须保存保存到内存中，否则后面的连接马上就销毁，无法建立连接 */
        $this->sockets = array($master);
        while (true) {
            /** 将所有的连接复制给change */
            $changed = $this->sockets;
            /** 这里使用了select模型监听io读写事件 */
            //todo 后期需要根据系统自动切换epoll模型和select模型 ，
            //todo 这个select模型默认只支持1024个客户端连接，可以通过重新编译PHP实现更大连接，但是效率很低的，建议换epoll模型
            /** 客户端连接写入数据后select需要手动遍历连接， */
            socket_select($changed, $write, $except, NULL);
            /** 遍历每一个连接 */
            foreach ($changed as $socket) {
                /** 如果是服务端连接 */
                if ($socket == $master) {
                    /** 读取服务端连接的数据 */
                    $client = socket_accept($master);
                    /** 没有数据，说明没有新的客户端发起连接请求 */
                    if ($client < 0) {
                        continue;
                    } else {
                        /** 保存客户端到内存 */
                        $this->connect($client);
                    }
                } else {
                    /** 如果是客户端，则读取连接中的数据 */
                    $bytes = @socket_recv($socket, $buffer, 2048, 0);
                    if ($bytes == 0) {
                        /** 如果客户端没有数据，则断开客户端连接 */
                        $this->disconnect($socket);
                    } else {
                        /** 通过socket获取用户信息 */
                        $user = $this->getuserbysocket($socket);
                        if (!$user->handshake) {
                            /** 如果没有握手，则先握手 */
                            $this->dohandshake($user, $buffer);
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
        $len     = ord($buffer[1]) & 127;
        /** 长度为126 */
        if ($len === 126) {
            /** 获取masks，就是密码 */
            $masks = substr($buffer, 4, 4);
            /** 获取data，加密后的数据 */
            $data  = substr($buffer, 8);
        } else if ($len === 127) {
            /** 获取masks */
            $masks = substr($buffer, 10, 4);
            /** 获取data */
            $data  = substr($buffer, 14);
        } else {
            /** 获取masks */
            $masks = substr($buffer, 2, 4);
            /** 获取data */
            $data  = substr($buffer, 6);
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
        }catch (\Exception|\RuntimeException $exception){
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
    protected function sendToAll($msg){
        if (!is_string($msg)) {
            $msg = json_encode($msg);
        }
        foreach ($this->users as $user){
            $this->send($user->socket,$msg);
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
        socket_write($client, $msg, strlen($msg));
    }

    /**
     * 构建websocket服务
     * @param $address
     * @param $port
     * @return resource|\Socket|void
     */
    private function WebSocket($address, $port)
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
     * 连接客户端
     * @param $socket
     * @return void
     * @note 保存客户端连接
     */
    private function connect($socket)
    {
        $user            = new \stdClass();
        $user->id        = uniqid();
        $user->socket    = $socket;
        $user->handshake = false;
        /** 保存客户端 */
        array_push($this->users, $user);
        /** 保存连接 */
        array_push($this->sockets, $socket);
        /** 调用用户自定义的onConnect方法 */
        try {
            $this->onConnect($socket);
        }catch (\Exception|\RuntimeException $exception){
            $this->onError($socket,$exception);
        }

    }

    /**
     * 关闭客户端连接
     * @param $socket
     * @return void
     */
    protected function close($socket){
        $this->disconnect($socket);
    }

    /**
     * 关闭客户端连接
     * @param $socket
     * @return void
     */
    private function disconnect($socket)
    {
        /** 吊起用户自定义 的onClose方法 */
        try {
            $this->onClose($socket);
        }catch (\Exception|\RuntimeException $exception){
            $this->onError($socket,$exception);
        }
        $found = null;
        $n     = count($this->users);
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
        socket_close($socket);
        if ($index >= 0) {
            array_splice($this->sockets, $index, 1);
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
    private function dohandshake($user, $buffer)
    {
        /** 解码http请求头部信息 */
        list($resource, $host, $upgrade, $connection, $key, $protocol, $version, $origin, $data) = $this->getheaders($buffer);
        /** 将获取到的key和常量258EAFA5-E914-47DA-95CA-C5AB0DC85B11拼接后加密，这个常量是文档约定俗成的，是一个常量 */
        $acceptkey = base64_encode(sha1($key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true));
        /** 握手需要返回给客户端的数据 */
        $upgrade   = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: $acceptkey\r\n\r\n";
        /** 将消息返回给客户端 */
        socket_write($user->socket, $upgrade, strlen($upgrade));
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
    public abstract function onError($socket,\Exception $exception);
}

