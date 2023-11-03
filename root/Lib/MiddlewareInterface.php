<?php

namespace Root\Lib;
use Root\Request;
use Root\Response;

/**
 * @purpose 中间件接口
 */
interface MiddlewareInterface
{
    public function process(Request $request, callable $next): Response;
}