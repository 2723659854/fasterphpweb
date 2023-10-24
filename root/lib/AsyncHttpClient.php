<?php

namespace Root\Lib;

use Root\Io\Selector;

class AsyncHttpClient
{

    /** 参考地址 */
    public $content = 'http://www.lvesu.com/blog/main/cms-8.html';
    public static function request(){
        $contextOptions['ssl']=[
            'verify_peer' => false,
            'verify_peer_name' => false
        ];
        $host = '192.168.4.97';
        $port = 8080;
        $scheme = 'tcp';
        /** 设置参数 */
        $context = stream_context_create($contextOptions);
        /** 创建客户端 STREAM_CLIENT_CONNECT 同步请求，STREAM_CLIENT_ASYNC_CONNECT 异步请求*/
        $socket = stream_socket_client("{$scheme}://{$host}:{$port}", $errno, $errstr, 1, STREAM_CLIENT_CONNECT, $context);
        /** 设置位非阻塞状态 */
        stream_set_blocking($socket,false);
        $request = HttpClient::makeRequest($host,$port);

        var_dump('id:'.((int)$socket));

        Selector::addFunction($socket,$request,function($message){
            var_dump('我是异步客户端回调',$message);
        });

    }
}