<?php
namespace Root\Middleware;
use Root\Request;
use Root\Response;
use Root\Lib\MiddlewareInterface;
/**
 * 测试中间件A
 */
class MiddlewareB implements MiddlewareInterface
{
    public function process(Request $request, callable $next):Response
    {
        /** 检测header请求头包含token信息 */
        if (!$request->header('token')){
            return response('<h1>403 forbidden</h1>', 403);
        }
        return $next($request);
    }
}