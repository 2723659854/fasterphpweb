<?php


// 监听 8080 端口的处理函数
function handle8080($clientSocket)
{
    $request = socket_read($clientSocket, 8192);
    $response = "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\n<h1>8080 端口响应</h1>";
    socket_write($clientSocket, $response);
    socket_close($clientSocket);
}

// 监听 8081 端口的处理函数
function handle8081($clientSocket)
{
    $request = socket_read($clientSocket, 8192);
    $response = "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\n<h1>8081 端口响应</h1>";
    socket_write($clientSocket, $response);
    socket_close($clientSocket);
}

// 创建 8080 端口的套接字
$socket8080 = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($socket8080, '0.0.0.0', 8080);
socket_listen($socket8080);

// 创建 8081 端口的套接字
$socket8081 = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($socket8081, '0.0.0.0', 8081);
socket_listen($socket8081);

while (true) {
    // 处理 8080 端口的连接
    if ($newClientSocket8080 = socket_accept($socket8080)) {
        handle8080($newClientSocket8080);
    }

    // 处理 8081 端口的连接
    if ($newClientSocket8081 = socket_accept($socket8081)) {
        handle8081($newClientSocket8081);
    }
}
