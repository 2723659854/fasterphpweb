<?php
namespace root;
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


    private static function getInstance()
    {
        $name = static::getAccessor();
        if (!isset(static::$instances[$name])) {

            static::$instances[$name] = new $name();
        }
        return static::$instances[$name];
    }


    public static function __callStatic($method, $arguments)
    {
        $instance = static::getInstance();
        return $instance->$method(...$arguments);
    }
}
