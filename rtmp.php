<?php
// 设置推流地址和端口
$rtmpServer = 'rtmp://localhost/live/';
$rtmpPort = 1935;
$streamKey = 'your_stream_key';
/*
 *
 RTMP_CHUNK_HEAD_1: 常量值为 0x03。它是 RTMP chunk header 的第一个字节的标识。
RTMP_CHUNK_HEAD_2: 常量值为 0x00。它是 RTMP chunk header 的第二个字节的标识。
RTMP_VERSION: 常量值为 0x03。它表示 RTMP 协议的版本号。
RTMP_TYPE_CHUNK: 常量值为 0x01。它表示 RTMP 数据包类型为 chunk 消息。
RTMP_CHUNK_SIZE: 常量值为 4096（或其他指定的块大小）。它表示每个 RTMP chunk 的最大大小（以字节为单位）。
RTMP_TYPE_INVOKE: 常量值为 0x10。它表示 RTMP 数据包类型为 invoke 消息。
RTMP_METHOD_SET_CHUNK_SIZE: 常量值通常为一个特定的方法 ID，用于标识设置 chunk 大小的 RTMP 方法。具体值可能根据 RTMP 实现而有所不同。
RTMP_METHOD_MESSAGE: 常量值通常为一个特定的方法 ID，用于标识普通的 RTMP 消息方法。具体值可能根据 RTMP 实现而有所不同。
 * */

const RTMP_CHUNK_HEAD_1 =0x03;
const RTMP_CHUNK_HEAD_2 = 0x00;
const RTMP_VERSION =0x03;
const RTMP_TYPE_CHUNK = 0x01;
const RTMP_CHUNK_SIZE = 4096;
const RTMP_TYPE_INVOKE =  0x10;
const RTMP_METHOD_SET_CHUNK_SIZE= 123;//todo 乱写的
const RTMP_METHOD_MESSAGE = 333;//todo 乱写的
// 创建socket连接
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    die("无法创建Socket: " . socket_strerror(socket_last_error()));
}

// 绑定socket到指定地址和端口
$result = socket_bind($socket, '0.0.0.0', $rtmpPort);
if ($result === false) {
    die("无法绑定Socket: " . socket_strerror(socket_last_error($socket)));
}

// 监听连接
$result = socket_listen($socket);
if ($result === false) {
    die("无法监听Socket: " . socket_strerror(socket_last_error($socket)));
}

echo "等待客户端连接...\n";

// 接受客户端连接
$clientSocket = socket_accept($socket);
if ($clientSocket === false) {
    die("无法接受客户端连接: " . socket_strerror(socket_last_error($socket)));
}

echo "客户端已连接。\n";

// 处理RTMP握手
handleHandshake($clientSocket);

// 接收并转发RTMP数据包
while (true) {
    $data = '';
    $bytes = @socket_recv($clientSocket, $buffer, 1024, 0);

    if ($bytes === false) {
        die("从客户端接收数据时出错: " . socket_strerror(socket_last_error($clientSocket)));
    } elseif ($bytes === 0) {
        // 客户端关闭连接
        break;
    } else {
        $data .= $buffer;
    }

    // 解析RTMP数据包并转发到目标地址
    forwardRtmpPacket($data, $rtmpServer, $streamKey);
}

// 关闭连接
socket_close($clientSocket);
socket_close($socket);

function handleHandshake($socket) {
    // 读取客户端发来的握手数据
    $data = '';
    $bytes = @socket_recv($socket, $buffer, 1536, 0);
    if ($bytes === false) {
        die("从客户端接收握手数据时出错: " . socket_strerror(socket_last_error($socket)));
    } else {
        $data .= $buffer;
    }

    // 解析握手数据并构建服务器端的握手响应
    $handshakeResponse = buildHandshakeResponse($data);

    // 发送握手响应给客户端
    $sent = @socket_send($socket, $handshakeResponse, strlen($handshakeResponse), 0);
    if ($sent === false) {
        die("发送握手响应时出错: " . socket_strerror(socket_last_error($socket)));
    }
}

function buildHandshakeResponse($handshakeData) {
    // 读取客户端发来的握手数据
    $clientHandshake = $handshakeData;

    // 生成服务器端的握手响应数据包
    $serverHandshake = '';
    $serverHandshake .= str_repeat("\x00", 3); // 0x00 0x00 0x02
    $serverHandshake .= chr(RTMP_CHUNK_HEAD_1); // RTMP chunk header 1
    $serverHandshake .= chr(RTMP_CHUNK_HEAD_2); // RTMP chunk header 2
    $serverHandshake .= chr(RTMP_VERSION); // RTMP version
    $serverHandshake .= chr(RTMP_TYPE_CHUNK); // RTMP packet type (chunk message)
    $serverHandshake .= str_repeat("\x00", 63); // Filler (63 bytes of zero)
    $serverHandshake .= str_repeat("\x00", 1); // TimeStamp (1 byte of zero)
    $serverHandshake .= chr(RTMP_CHUNK_SIZE); // Chunk size (2 bytes, big-endian)
    $serverHandshake .= chr(RTMP_TYPE_INVOKE); // RTMP packet type (invoke message)
    $serverHandshake .= str_repeat("\x00", 24); // Stream ID (24 bytes of zero)
    $serverHandshake .= chr(RTMP_METHOD_SET_CHUNK_SIZE); // Method ID (set chunk size)
    $serverHandshake .= chr(RTMP_CHUNK_SIZE); // Chunk size (2 bytes, big-endian)

    return $serverHandshake;
}

function forwardRtmpPacket($packetData, $rtmpServer, $streamKey) {
    // 解析 RTMP 数据包，获取关键信息
    $packetHeader = substr($packetData, 0, 12);
    $packetSize = unpack('N', substr($packetData, 12, 4));
    $packetSize = $packetSize[1];
    $packetType = unpack('n', substr($packetData, 16, 2));
    $packetType = $packetType[1];
    $timestamp = unpack('N', substr($packetData, 18, 4));
    $timestamp = $timestamp[1];
    $chunkStreamId = unpack('n', substr($packetData, 22, 2));
    $chunkStreamId = $chunkStreamId[1];
    $packetData = substr($packetData, 24);

    // 构建 RTMP 消息并发送到目标 RTMP 服务器上的指定应用和应用实例（streamKey）上
    $message = '';
    $message .= str_repeat("\x00", 3); // 0x00 0x00 0x02
    $message .= chr(RTMP_CHUNK_HEAD_1); // RTMP chunk header 1
    $message .= chr(RTMP_CHUNK_HEAD_2); // RTMP chunk header 2
    $message .= chr(RTMP_VERSION); // RTMP version
    $message .= chr($packetType); // RTMP packet type
    $message .= str_repeat("\x00", 63); // Filler (63 bytes of zero)
    $message .= str_repeat("\x00", 1); // TimeStamp (1 byte of zero)
    $message .= chr(RTMP_CHUNK_SIZE); // Chunk size (2 bytes, big-endian)
    $message .= chr(RTMP_TYPE_INVOKE); // RTMP packet type (invoke message)
    $message .= str_repeat("\x00", 24); // Stream ID (24 bytes of zero)
    $message .= chr(RTMP_METHOD_MESSAGE); // Method ID (message)
    $message .= chr(strlen($packetData)); // Message length (4 bytes, big-endian)
    $message .= $packetData; // Packet data

    // 使用 fsockopen 或类似函数打开到目标 RTMP 服务器的连接，并发送消息数据
    $socket = fsockopen($rtmpServer, $rtmpPort=1935);
    if ($socket === false) {
        die("无法连接到 RTMP 服务器: $rtmpServer");
    }
    fwrite($socket, $message);
    fclose($socket);
}