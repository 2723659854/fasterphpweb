<?php
use Root\Response;
/*
 * 自定义函数
 * */
if (!function_exists('version')){
    /**
     * 当前版本号
     * @return void
     */
    function version(){
        echo "5.2.0\r\n";
    }
}

if (!function_exists('response')){
    /**
     * Response 响应
     * @param mixed $body
     * @param int $status
     * @param array $headers
     * @return Response
     */
    function response( mixed $body = '', int $status = 200, array $headers = []): Response
    {
        if (!is_string($body)) $body = json_encode($body);
        return new Response($status, $headers, $body);
    }
}