<?php
namespace Root\Middleware;
use Root\Lib\MiddlewareInterface;
use Root\Request;
use Root\Response;

/**
 * 测试中间件A
 */
class MiddlewareA implements MiddlewareInterface
{
    public function process(Request $request, callable $next):Response
    {
        //var_dump("我是MiddlewareA");
        return $next($request);
    }
}