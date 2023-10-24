<?php

namespace Root\Lib;

use Root\Io\Selector;
use Root\Request;

class AsyncHttpClient
{

    /** 参考地址 */
    public $content = 'http://www.lvesu.com/blog/main/cms-8.html';
    public static function request(){
        $contextOptions['ssl']=[
            'verify_peer' => false,
            'verify_peer_name' => false
        ];
        $host = 'www.baidu.com';
        $port = 80;
        $scheme = 'tcp';
        /** 设置参数 */
        $context = stream_context_create($contextOptions);
        /** 创建客户端 STREAM_CLIENT_CONNECT 同步请求，STREAM_CLIENT_ASYNC_CONNECT 异步请求*/
        $socket = stream_socket_client("{$scheme}://{$host}:{$port}", $errno, $errstr, 1, STREAM_CLIENT_ASYNC_CONNECT, $context);
        /** 设置位非阻塞状态 */
        stream_set_blocking($socket,false);
        $request = HttpClient::makeRequest($host,$port);

        Selector::addFunction($socket,$request,function($buffer){
            var_dump('我是异步客户端回调');

        },function ( \RuntimeException $exception){
            var_dump($exception->getMessage());
        });

    }
}