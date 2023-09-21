<?php
namespace Root;
use RuntimeException;

abstract class Facade
{
    protected static $instances = [];

    /**
     * @return string
     */
    protected static function getAccessor()
    {
        throw new RuntimeException("请设置facade类");
    }


    /**
     * 获取静态实例
     * @return mixed
     */
    private static function getInstance()
    {
        $name = static::getAccessor();
        if (!isset(static::$instances[$name])) {

            static::$instances[$name] = new $name();
        }
        return static::$instances[$name];
    }


    /**
     * 静态调用实例
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($method, $arguments)
    {
        $instance = static::getInstance();
        return $instance->$method(...$arguments);
    }
}
