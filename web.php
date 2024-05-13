<?php

$server = stream_socket_server("tcp://0.0.0.0:1935", $errno, $errstr);

if (!$server) {
    die("Error: $errstr ($errno)\n");
}

$readSockets = [$server];

while (true) {
    $writeSockets = [];
    $exceptSockets = [];

    if (stream_select($readSockets, $writeSockets, $exceptSockets, null) === false) {
        break;
    }

    foreach ($readSockets as $socket) {
        if ($socket === $server) {
            $newSocket = stream_socket_accept($server);
            $readSockets[] = $newSocket;
        } else {
            // 处理客户端连接的逻辑
            $buffer = '';
            while ($content = fread($socket,1024)){
                $buffer.= $content;
                if (strlen($content)<1024){
                    break;
                }
            }
            if ($buffer){
                $string = "<h1>1</h1>";
                $length = strlen($string);
                $fuck = "HTTP/1.1 200 OK\r\nServer: xiaosongshu\r\nContent-Type: text/html; charset=UTF-8\r\nSet-Cookie: name=how%20are%20you%20%21\r\n";
                $fuck .= "Access-Control-Allow-Credentials: true\r\n";
                $fuck .= "Access-Control-Allow-Origin: \r\n";
                $fuck .= "Access-Control-Allow-Methods: *\r\n";
                $fuck .= "Access-Control-Allow-Headers: *\r\n";
                $fuck .= "Connection: keep-alive\r\n";
                $fuck .= "Content-Length: {$length}\r\n\r\n{$string}\r\n\r\n";
                fwrite($socket,$fuck);
            }else{
                //TODO 暫不處理關閉客戶端的問題
            }


        }
    }
}

fclose($server);
