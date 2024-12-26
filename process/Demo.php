<?php

namespace Process;

use Root\Lib\HttpClient;

class Demo
{
    /**
     * 逻辑处理函数
     * @return void
     * @note 这里面写你自己的逻辑，可以是监听端口，也可以是其他常驻内存进程
     */
    public static function handle($param = []){
        while (1){
            $host = "http://54.77.139.23:80";
            HttpClient::requestAsync($host, 'GET', ['lesson_id' => 201], [], [], function ($response) use ($host) {
                $statusCode = $response->getStatusCode();
                echo "请求地址：{$host},状态码：{$statusCode}\r\n";
            }, function ($error) {
                file_put_contents(app_path().'/response.txt', $error->getMessage()."\r\n", FILE_APPEND);
            });
        }
    }

}