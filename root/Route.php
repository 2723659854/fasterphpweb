<?php

namespace Root;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;

class Route
{
    public static $dispatcher;

    public static function loadRoute(){
        $routeFileList =[config('route')];
        // 加载所有路由文件配置的路由
        self::$dispatcher = self::make_dispatcher($routeFileList);
    }

//    public static function load()
//    {// 获取已配置的路由列表
//
//        $routeFileList =[config('route')];
//        // 加载所有路由文件配置的路由
//        $dispatcher = self::make_dispatcher($routeFileList);
//        // Fetch method and URI from somewhere
//        $httpMethod = $_SERVER['REQUEST_METHOD'];
//        $uri        = $_SERVER['REQUEST_URI'];
//        // Strip query string (?foo=bar) and decode URI
//        if (false !== $pos = strpos($uri, '?')) {
//            $uri = substr($uri, 0, $pos);
//        }
//        $uri = rawurldecode($uri);
//        // 路由调度
//        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
//        switch ($routeInfo[0]) {
//            case Dispatcher::NOT_FOUND:
//                // 找不到请求方法
//                // ... 404 Not Found
//                throw new \Exception("请求方法不存在:{$uri}");
//                break;
//            case Dispatcher::METHOD_NOT_ALLOWED:
//                // 请求类型错误
//                $allowedMethods = $routeInfo[1];
//                // ... 405 Method Not Allowed
//                throw new \Exception("请求类型错误({$httpMethod}),当前方法允许请求类型({$allowedMethods[0]})");
//                break;
//            case Dispatcher::FOUND:
//                // 找到请求方法：调用方法即可
//                $handler = $routeInfo[1];
//                $vars    = $routeInfo[2];
//                call_user_func([new $handler[0], $handler[1]], $vars);
//                break;
//        }
//    }

    public static function dispatch($httpMethod, $uri,$request){
        $uri = rawurldecode($uri);
        // 路由调度
        $routeInfo = self::$dispatcher->dispatch($httpMethod, $uri);
        //var_dump($routeInfo);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                // 找不到请求方法
                // ... 404 Not Found
                throw new \Exception("请求方法不存在:{$uri}");
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                // 请求类型错误
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                throw new \Exception("请求类型错误({$httpMethod}),当前方法允许请求类型({$allowedMethods[0]})");
                break;
            case Dispatcher::FOUND:
                // 找到请求方法：调用方法即可
                $handler = $routeInfo[1];
                return G($handler[0])->{$handler[1]}($request);

        }
    }

    private static function make_dispatcher($routeFileList)
    {
        return simpleDispatcher(function (RouteCollector $router) use ($routeFileList) {
            foreach ($routeFileList as $routeFile) {
                if (isset($routeFile['prefix'])) {
                    $routers = $routeFile[0];
                    $router->addGroup($routeFile['prefix'], function (RouteCollector $router) use ($routers) {
                        if ($routers) {
                            foreach ($routers as $routeItem) {
                                $router->addRoute(strtoupper($routeItem[0]), $routeItem[1], $routeItem[2]);
                            }
                            unset($routeItem);
                        }
                    });
                } else {
                    if ($routeFile) {
                        foreach ($routeFile as $routeItem) {
                            $router->addRoute(strtoupper($routeItem[0]), $routeItem[1], $routeItem[2]);
                        }
                        unset($routeItem);
                    }
                }
            }
            unset($routeFile);
        });
    }
}
