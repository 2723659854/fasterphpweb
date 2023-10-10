<?php
$handshakecontent = <<<EOF
GET / HTTP/1.1
Host: localhost:9501
Connection: Upgrade
Pragma: no-cache
Cache-Control: no-cache
User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/116.0.0.0 Safari/537.36
Upgrade: websocket
Origin: http://127.0.0.1:8000
Sec-WebSocket-Version: 13
Accept-Encoding: gzip, deflate, br
Accept-Language: zh-CN,zh;q=0.9
Sec-WebSocket-Key: 123
Sec-WebSocket-Extensions: permessage-deflate; client_max_window_bits
EOF;

function encodeMessage($message='Ping'){
    $final=true; $payload=$message;  $masked=true;
    $data = '';
    $byte_1 = $final ? 0b10000000 : 0b00000000; // Final fragment marker.
    $byte_1 |= 1; // Set opcode.
    $data .= pack('C', $byte_1);

    $byte_2 = $masked ? 0b10000000 : 0b00000000; // Masking bit marker.

    // 7 bits of payload length...
    $payload_length = strlen($payload);
    if ($payload_length > 65535) {
        $data .= pack('C', $byte_2 | 0b01111111);
        $data .= pack('J', $payload_length);
    } elseif ($payload_length > 125) {
        $data .= pack('C', $byte_2 | 0b01111110);
        $data .= pack('n', $payload_length);
    } else {
        $data .= pack('C', $byte_2 | $payload_length);
    }
    // Handle masking
    if ($masked) {
        // generate a random mask:
        $mask = '';
        for ($i = 0; $i < 4; $i++) {
            $mask .= chr(rand(0, 255));
        }
        $data .= $mask;

        // Append payload to frame:
        for ($i = 0; $i < $payload_length; $i++) {
            $data .= $payload[$i] ^ $mask[$i % 4];
        }
    } else {
        $data .= $payload;
    }
    return $data;
}

// 创建套接字
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

// 连接服务端
socket_connect($socket, '127.0.0.1', 9501);
//握手
socket_write($socket, $handshakecontent);
//读取服务端返回的握手数据
$handShakeSuccess = socket_read($socket, 1024);
//var_dump($handShakeSuccess);

//$pong = socket_read($socket, 1024);
$msg = encodeMessage('you are a student !');
socket_write($socket, $msg,strlen($msg));

while(true){
    fwrite(STDOUT,'请输入你要发送的内容');
    $content  = fgets(STDIN);
    if (($content)){
        $msg = encodeMessage($content);
        socket_write($socket, $msg,strlen($msg));
    }
    decodeMessage2($socket);
}

function decodeMessage2($socket){

    $data = socket_read($socket,2);

    list ($byte_1, $byte_2) = array_values(unpack('C*', $data));

    // Masking bit
    $masked = (bool)($byte_2 & 0b10000000);
    $payload = '';
    // Payload length
    $payload_length = $byte_2 & 0b01111111;
    if ($payload_length > 125) {
        if ($payload_length === 126) {
            // 126: Payload is a 16-bit unsigned int
            $data = socket_read($socket,2);
            $payload_length = current(unpack('n', $data));

        } else {
            // 127: Payload is a 64-bit unsigned int
            $data = socket_read($socket,8);
            $payload_length = current(unpack('J', $data));

        }
    }
    // Get masking key.
    if ($masked) {

        $masking_key = socket_read($socket,4);
    }
    if ($payload_length > 0) {
        $data = socket_read($socket,$payload_length);

        if ($masked) {
            for ($i = 0; $i < $payload_length; $i++) {
                $payload .= ($data[$i] ^ $masking_key[$i % 4]);
            }
        } else {
            $payload = $data;
        }
    }
    var_dump($payload);
}



