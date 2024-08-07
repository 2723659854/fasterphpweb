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
$string = <<<eof
刚才发送了邮件，收一下，
临时帐号：xxxxxx@gamehoursm365.onmicrosoft.com
密码：GH.77267770
可以测试下临时帐号能否登入微软网站，

08/22会切换为正式的 xxxxxx@gamehours.com，
用于：M365、邮箱、teams 等微软服务的登入。


========================================
以下为正式帐号，08/22才可以使用：
郭喜領 fengye.guo@gamehours.com
陳彬 bin.chen@gamehours.com
文朝均 chaojun.wen@gamehours.com
李生彬 shengbin.li@gamehours.com
楊龍 long.yang@gamehours.com
譚浩 hao.tan@gamehours.com
董俊傑 junjie.dong@gamehours.com
張橋 qiao.zhang@gamehours.com
李建華 jianhua.li@gamehours.com
李文娟 wenjuan.li@gamehours.com
胡冬雪 dongxue.hu@gamehours.com
管錫夢 ximeng.guan@gamehours.com
王婭楠 nicole.wang@gamehours.com
何華蘋 huaping.he@gamehours.com
鄧順心 shunxin.deng@gamehours.com
eof;
