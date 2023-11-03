<?php
namespace App\Middleware;
use Root\Lib\MiddlewareInterface;
use Root\Request;
use Root\Response;

/**
 * @purpose 中间件
 * @author administrator
 * @time 2023-09-28 05:51:21
 */
class Auth implements MiddlewareInterface
{
    public function process(Request $request, callable $next):Response
    {
        //todo 这里处理你的逻辑
        return $next($request);
    }
}