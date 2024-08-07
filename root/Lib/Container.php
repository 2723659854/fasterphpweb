<?php

namespace Root\Lib;
/**
 * 容器类
 */
class Container
{
    /** @var array */
    public static array $providers = [];

    /**
     * 获取一个已存储的对象
     * @param string $name
     * @return object
     */
    public static function get(string $name)
    {
        if (!isset(self::$providers[$name])) {
            if (class_exists($name)){
                /** 必须使用一个反射类，因为这个对象可能需要实现自动依赖注入 */
                $class = Ioc::make($name);
                self::$providers[$name]=$class;
            }else{
                throw new \RuntimeException("[$name]类不存在！");
            }
        }
        return self::$providers[$name];
    }

    /**
     * 设置容器内的对象
     * @param string $name
     * @param $buffer
     * @return object|null
     */
    public static function set(string $name,$buffer){
        self::$providers[$name]=new $name(...$buffer);
        return self::$providers[$name];
    }

    /**
     * 实例化一个新的对象
     * @param string $name
     * @return mixed
     */
    public static function make(string $name){
        if (class_exists($name)){
            return  Ioc::make($name);
        }else{
            throw new \RuntimeException("[$name]类不存在！");
        }
    }

}