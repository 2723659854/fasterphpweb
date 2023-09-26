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

if (!function_exists('redirect')){
    /**
     * 重定向
     * @param string $location 跳转地址
     * @param int $status 状态码
     * @param array $headers 头部信息
     * @return Response
     */
    function redirect(string $location, int $status = 302, array $headers = [])
    {
        $response = new Response($status, ['Location' => $location]);
        if (!empty($headers)) {
            $response->withHeaders($headers);
        }
        return $response;
    }
}
