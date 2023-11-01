<?php

namespace Root;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Root\Lib\MiddlewareInterface;
use function FastRoute\simpleDispatcher;

/**
 * @purpose 路由调度器
 */
class Route
{
    /** 路由调度器 */
    public static $dispatcher;

    /** 所有的路由 */
    public static $urls = [];
    /** 中间件 */
    public static $middlewares = [];

    /**
     * 路由加载器
     * @return void
     */
    public static function loadRoute()
    {
        $config = config('route');
        $routeFileList = array_merge($config,self::$urls);
        // 加载所有路由文件配置的路由
        self::$dispatcher = self::make_dispatcher([$routeFileList]);
    }

    /**
     * 路由调度
     * @param string $httpMethod 请求方法
     * @param string $uri 请求路由
     * @param Request|mixed $request 请求内容
     * @return mixed
     * @throws \Exception
     */
    public static function dispatch(string $httpMethod, string $uri, Request $request): mixed
    {
        $uri = rawurldecode($uri);
        /** 路由调度 */
        $routeInfo = self::$dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                /** 找不到请求方法 */
                // ... 404 Not Found
                throw new \Exception("请求方法不存在:{$uri}");
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                /** 请求类型错误 */
                $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                throw new \Exception("请求类型错误({$httpMethod}),当前方法允许请求类型({$allowedMethods[0]})");
                break;
            case Dispatcher::FOUND:
                /** 找到请求方法：调用方法即可 */
                $handler = $routeInfo[1];
                $class   = $handler[0];
                $method  = $handler[1];
                if (!class_exists($class)) {
                    throw new \Exception("{$class}不存在");
                }
                if (!method_exists($class, $method)) {
                    throw new \Exception("{$class}::class->{$method}()方法不存在");
                }
                /** 将控制器和方法封装成为一个闭包回调函数 */
                $next = function ($request) use ($class, $method) {
                    /** 里面正常执行控制器的方法，并返回结果 */
                    return G($class)->{$method}($request);
                };
                /** 处理这个路由的中间件 */
                $middlewares = self::$middlewares[strtoupper($httpMethod) . '@@@' . $uri] ?? [];
                foreach (array_reverse($middlewares) as $middleware) {
                    if (!class_exists($middleware)) {
                        throw new \Exception("{$middleware}不存在");
                    }
                    if (!G($middleware) instanceof MiddlewareInterface) {
                        throw new \Exception("{$middleware}不合法");
                    }
                    /** 使用foreach 层层嵌套,将next作为参数，m的process作为方法，构建新的闭包next */
                    $next = function ($request) use ($middleware, $next) {
                        return G($middleware)->process($request, $next);
                    };
                }
                return $next($request);
        }
    }


    /**
     * 加载路由
     * @param $routeFileList
     * @return Dispatcher
     */
    private static function make_dispatcher($routeFileList)
    {
        return simpleDispatcher(function (RouteCollector $router) use ($routeFileList) {
            foreach ($routeFileList as $routeFile) {
                if (isset($routeFile['prefix'])) {
                    $routers = $routeFile[0];
                    $router->addGroup($routeFile['prefix'], function (RouteCollector $router) use ($routers, $routeFile) {
                        if ($routers) {
                            foreach ($routers as $routeItem) {
                                /** 因为存在路由文件注册路由和注解注册路由，这里会存在注册相同路由，会失败，导致服务无法启动 ，这里使用了事务屏蔽错误 */
                                try {
                                    $router->addRoute(strtoupper($routeItem[0]), $routeItem[1], $routeItem[2]);
                                    self::$middlewares[strtoupper($routeItem[0]) . '@@@' . $routeFile['prefix'] . $routeItem[1]] =  $routeItem[3] ?? [];
                                }catch (\Exception|\RuntimeException $exception){ }
                            }
                            unset($routeItem);
                        }
                    });
                } else {
                    if ($routeFile) {
                        foreach ($routeFile as $routeItem) {
                            /** 因为存在路由文件注册路由和注解注册路由，这里会存在注册相同路由，会失败，导致服务无法启动 ，这里使用了事务屏蔽错误 */
                            try {
                                $router->addRoute(strtoupper($routeItem[0]), $routeItem[1], $routeItem[2]);
                                /** 保存路由和中间件 */
                                self::$middlewares[strtoupper($routeItem[0]) . '@@@' . $routeItem[1]] = $routeItem[3] ?? [];
                            }catch (\Exception|\RuntimeException $exception){ }
                        }
                        unset($routeItem);
                    }
                }
            }
            unset($routeFile);
        });
    }

    /**
     * 添加路由
     * @param array $methods
     * @param string $url
     * @param array $middleware
     * @return void
     */
    public static function add(array $methods, string $url,array $callback, array $middleware= [])
    {
        $params = [];
        foreach ($methods as $method){
            $params[]=[$method,$url,$callback,$middleware];
        }
        self::$urls = array_merge(self::$urls,$params);
        self::loadRoute();
    }
}
