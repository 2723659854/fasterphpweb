<?php

$path = __DIR__;
echo "上传接口http://127.0.0.1:10008\r\n";
$tcp = getprotobyname("tcp");
$socket = socket_create(AF_INET, SOCK_STREAM, $tcp);
socket_bind($socket, '0.0.0.0', 10008); //绑定要监听的端口
socket_listen($socket); //监听端口
//初始化一个数据，和客户端通信
$buffer = "connect";
$path = __DIR__ . '/public/file/';
//echo $path;
while (true) {
    // 接受一个socket连接
    $connection = socket_accept($socket);
    if (!$connection) {
        echo "connect fail";
    } else {
        echo "Socket connected\n";
        // 向客户端传递一个信息数据
        if ($buffer != "") {
            echo "send data to client\n";
            socket_write($connection, $buffer . "\n");
            echo "Wrote to socket\n";
        } else {
            echo "no data in the buffer\n";
        }
        //从客户端取得数据
        $i=1;
        while ($flag = @socket_recv($connection, $data, 102, 0)) {
            echo $i++;
            echo "\r\n";
            //var_dump($data);
            if (false !== strpos($data, 'filename:')) {
                $filename = substr($data, 9);
                $filename = __DIR__.'/mabi.txt';
                file_put_contents($filename,'');
                sleep(1);
                //根据传过来的名子打开一个文件
                $fp = fopen($filename, "wb");
                continue;
            }
            fwrite($fp, $data);
        }
        fclose($fp);
    }
    socket_write($connection, $filename, strlen($filename));
    socket_close($connection);
    //关闭 socket
    printf("Closed the socket\n");

}
