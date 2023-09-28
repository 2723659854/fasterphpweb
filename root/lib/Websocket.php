<?php
namespace Root\Lib;

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
        $master        = $this->WebSocket($this->host, $this->port);
        $this->sockets = array($master);
        while (true) {
            $changed = $this->sockets;
            socket_select($changed, $write, $except, NULL);
            foreach ($changed as $socket) {
                if ($socket == $master) {
                    $client = socket_accept($master);
                    if ($client < 0) {
                        continue;
                    } else {
                        $this->connect($client);
                    }
                } else {
                    $bytes = @socket_recv($socket, $buffer, 2048, 0);
                    if ($bytes == 0) {
                        $this->disconnect($socket);
                    } else {
                        $user = $this->getuserbysocket($socket);
                        if (!$user->handshake) {
                            $this->dohandshake($user, $buffer);
                        } else {
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
     */
    private function decode($buffer)
    {
        $decoded = null;
        $len     = ord($buffer[1]) & 127;
        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data  = substr($buffer, 8);
        } else if ($len === 127) {
            $masks = substr($buffer, 10, 4);
            $data  = substr($buffer, 14);
        } else {
            $masks = substr($buffer, 2, 4);
            $data  = substr($buffer, 6);
        }
        for ($index = 0; $index < strlen($data); $index++) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }
        return $decoded;
    }

    /**
     * 处理客户端消息
     * @param $user
     * @param $msg
     * @return void
     */
    private function process($user, $msg)
    {
        $action = $this->decode($msg);
        $this->onMessage($user->socket, $action);
    }

    /**
     * 发送消息
     * @param $socket
     * @param $msg
     * @return void
     */
    protected function sendTo($socket, $msg)
    {
        if (!is_string($msg)) {
            $msg = json_encode($msg);
        }
        $this->send($socket, $msg);
    }

    /**
     * 消息编码
     * @param $message
     * @return string
     */
    private function encode($message)
    {
        $len = strlen($message);
        if ($len <= 125) {
            return "\x81" . chr($len) . $message;
        } else if ($len <= 65535) {
            return "\x81" . chr(126) . pack("n", $len) . $message;
        } else {
            return "\x81" . chr(127) . pack("xxxxN", $len) . $message;
        }
    }

    /**
     * 向客户端发送消息
     * @param $client
     * @param $msg
     * @return void
     */
    private function send($client, $msg)
    {
        $msg = $this->encode($msg);
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
        $master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("socket_create() failed");
        socket_set_option($master, SOL_SOCKET, SO_REUSEADDR, 1) or die("socket_option() failed");
        socket_bind($master, $address, $port) or die("socket_bind() failed");
        socket_listen($master, 20) or die("socket_listen() failed");
        return $master;
    }

    /**
     * 连接客户端
     * @param $socket
     * @return void
     */
    private function connect($socket)
    {
        $user            = new \stdClass();
        $user->id        = uniqid();
        $user->socket    = $socket;
        $user->handshake = false;
        array_push($this->users, $user);
        array_push($this->sockets, $socket);
        $this->onConnect($socket);
    }

    /**
     * 关闭客户端连接
     * @param $socket
     * @return void
     */
    private function disconnect($socket)
    {
        $this->onClose($socket);
        $found = null;
        $n     = count($this->users);
        for ($i = 0; $i < $n; $i++) {
            if ($this->users[$i]->socket == $socket) {
                $found = $i;
                break;
            }
        }
        if (!is_null($found)) {
            array_splice($this->users, $found, 1);
        }
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
     */
    private function dohandshake($user, $buffer)
    {
        $this->console("\nRequesting handshake...");
        list($resource, $host, $upgrade, $connection, $key, $protocol, $version, $origin, $data) = $this->getheaders($buffer);
        $this->console("Handshaking...");
        $acceptkey = base64_encode(sha1($key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true));
        $upgrade   = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Accept: $acceptkey\r\n\r\n";
        socket_write($user->socket, $upgrade, strlen($upgrade));
        $user->handshake = true;
        $this->console("Done handshaking...");
        return true;
    }

    /**
     * 解析http头部
     * @param $req
     * @return array|null[]
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
     * 打印消息
     * @param $message
     * @return void
     */
    private function console($message)
    {
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
}

