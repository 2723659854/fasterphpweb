<?php
namespace Root\Lib;
/**
 * @purpose 客户端随时可以发送消息，并且一直接收消息
 * @note 客户端和服务端对于数据的解码和编码是有差别的
 */
class WsClient
{
    /** 服务器ip */
    private static string $host  ;
    /** 服务器端口 */
    private  static int $port  ;
    /** 客户端 */
    private  static  $socket = null;
    /** 客户端状态 */
    private  static bool $connected = false;
    /** 握手秘钥 */
    private  static string $key = '';
    /** 用户自定义消息处理 回调函数 */
    public static  $onMessage ;

    /**
     * 设置要连接的服务器
     * @param $host
     * @param $port
     * @return void
     */
    public static function setUp($host = '127.0.0.1',$port = 9501){
        self::$host = $host;
        self::$port= $port;
        self::$connected = false;
    }

    /**
     * 建立连接
     * @return void
     * @throws \Exception
     */
    private  static function connect(){
        try {
            /** 创建套接字 */
            self::$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            /** 连接服务端 */
            socket_connect(self::$socket , self::$host, self::$port);
        }catch (\Exception $exception){
            self::$connected = false;
            throw new \Exception("连接服务器失败");
        }
        self::$key = md5(rand(10000,99999).time());
        /** ws握手 */
        self::handshake();
    }

    /**
     * 握手
     * @return void
     * @throws \Exception
     */
    private  static function handshake(){
        $key = self::$key;
        $host = self::$host;
        $port = self::$port;
        /** 发送http请求，申请升级http连接为ws连接 */
        $handShakeContent = "GET / HTTP/1.1\r\n";
        $handShakeContent .="Host: {$host}:{$port}\r\n";
        $handShakeContent .="Connection: Upgrade\r\n";
        $handShakeContent .="Pragma: no-cache\r\n";
        $handShakeContent .="Cache-Control: no-cache\r\n";
        $handShakeContent .="User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36\r\n";
        $handShakeContent .="Upgrade: websocket\r\n";
        $handShakeContent .="Origin: http://{$host}:{$port}\r\n";
        $handShakeContent .="Sec-WebSocket-Version: 13\r\n";
        $handShakeContent .="Accept-Encoding: gzip, deflate, br\r\n";
        $handShakeContent .="Accept-Language: zh-CN,zh;q=0.9\r\n";
        $handShakeContent .="Sec-WebSocket-Key: {$key}\r\n";
        $handShakeContent .="Sec-WebSocket-Extensions: permessage-deflate; client_max_window_bits\r\n";
        $handShakeContent .="\r\n";
        self::write(self::$socket,$handShakeContent);
        $response = self::read(self::$socket, 1024);
        if (!preg_match('#Sec-WebSocket-Accept:\s(.*)$#mUi', $response, $matches)) {
            self::$connected = false;
            throw new \Exception("握手失败,请检查服务端是否已开启");
        }
        $responseKey =trim($matches[1]);
        $myKey = base64_encode( pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')) );
        if ($myKey != $responseKey){
            self::$connected = false;
            throw new \Exception("握手失败，服务端拒绝连接");
        }
        self::$connected = true;
    }


    /**
     * 写入消息
     * @param $socket
     * @param $msg
     * @return false|int
     */
    private static function write($socket,$msg){
        return socket_write($socket, $msg);
    }

    /**
     * 读取消息
     * @param $socket
     * @param $length
     * @return false|string
     */
    private static function read($socket,$length=1024){
        return socket_read($socket, $length);
    }

    /**
     * 消息解码
     * @param $socket
     * @return false|string
     */
    private static function decodeMessage($socket){
        /** 读取编码方式 */
        $data = self::read($socket,2);
        if (!$data) return '';
        list ($byte_1, $byte_2) = array_values(unpack('C*', $data));
        /** 读取掩码 */
        $masked = (bool)($byte_2 & 0b10000000);
        $payload = '';
        /** 计算数据长度 */
        $payload_length = $byte_2 & 0b01111111;
        if ($payload_length > 125) {
            if ($payload_length === 126) {
                // 126: Payload is a 16-bit unsigned int
                /** 16位无符号整数 */
                $data = self::read($socket,2);
                $payload_length = current(unpack('n', $data));
            } else {
                // 127: Payload is a 64-bit unsigned int
                /** 64位无符号整数 */
                $data = self::read($socket,8);
                $payload_length = current(unpack('J', $data));
            }
        }
        // Get masking key.
        /** 获取掩码 就是key */
        if ($masked) {
            $masking_key = self::read($socket,4);
        }
        /** 如果数据长度大于0 */
        if ($payload_length > 0) {
            /** 读取指定长度数据 */
            $data = self::read($socket,$payload_length);
            /** 如果有掩码，则需要对数据解码 */
            if ($masked) {
                for ($i = 0; $i < $payload_length; $i++) {
                    $payload .= ($data[$i] ^ $masking_key[$i % 4]);
                }
            } else {
                /** 否则，直接返回数据，低于125个字节的字符，没有加密 */
                $payload = $data;
            }
        }
        return $payload;
    }

    /**
     * 消息编码
     * @param $message
     * @return string
     */
    private static function encodeMessage($message='Ping'){
        $final=true; $payload=$message;  $masked=true;
        $data = '';
        /** 获取ws协议第一位字符的编码方式 */
        # 0b10000000 =-128  0b00000000 =128  这里是二进制数
        $byte_1 = $final ? 0b10000000 : 0b00000000;
        $byte_1 |= 1;
        /** ws协议第一位是0 */
        $data .= pack('C', $byte_1);
        /** 获取掩码的编码方式 */
        $byte_2 = $masked ? 0b10000000 : 0b00000000;

        /** 编译出7个字节的长度 */
        /** 获取消息长度 */
        $payload_length = strlen($payload);
        /** 长度大于65535 */
        if ($payload_length > 65535) {
            /** unsigned char 先把掩码方式按无符号编译成二进制 */
            $data .= pack('C', $byte_2 | 0b01111111);
            /** 再把消息长度编译成64位长整数 */
            $data .= pack('J', $payload_length);
        } elseif ($payload_length > 125) {
            /** 无符号编译掩码方式 或运算 */
            $data .= pack('C', $byte_2 | 0b01111110);
            /** 无符号16位短数据 编译成二进制 */
            $data .= pack('n', $payload_length);
        } else {
            /** 如果消息很短，直接把掩码和长度进行或运算，然后编译成无符号的二进制数 */
            $data .= pack('C', $byte_2 | $payload_length);
        }

        /** 处理掩码 */
        if ($masked) {
            /** 随机生成掩码 */
            $mask = '';
            for ($i = 0; $i < 4; $i++) {
                $mask .= chr(rand(0, 255));
            }
            $data .= $mask;
            /** 消息内容使用掩码进行编译成二进制 */
            for ($i = 0; $i < $payload_length; $i++) {
                $data .= $payload[$i] ^ $mask[$i % 4];
            }
        } else {
            $data .= $payload;
        }
        return $data;
    }

    /**
     * 启动服务
     * @return void
     * @throws \Exception
     */
    public  static function start(){
        echo "客户端监听已开启  ws://".self::$host.':'.self::$port."\r\n";
        while(true){
            if (self::$connected==false){
                self::connect();
            }
            $message = self::decodeMessage(self::$socket);
            if ($message==null){
                echo "服务器已关闭，退出客户端\r\n";
                break;
            }
            /** 调用用户设置的回调处理消息 */
            if (is_callable(self::$onMessage)){
                call_user_func(self::$onMessage,$message);
            }
        }
    }

    /**
     * 发送消息
     * @param $message
     * @return false|int
     * @throws \Exception
     */
    public static function send($message){
        if (self::$connected==false){
            self::connect();
        }
        if (!is_string($message)) {
            $message = json_encode($message);
        }
        return self::write(self::$socket,self::encodeMessage( $message));
    }

    /**
     * 读取一条数据
     * @return false|string
     * @throws \Exception
     */
    public static function get(){
        if (self::$connected==false){
            self::connect();
        }
        return self::decodeMessage(self::$socket);
    }
}