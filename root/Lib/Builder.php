<?php

namespace Root\Lib;

use DI\ContainerBuilder;

class Builder
{

    /** 容器 */
    protected static object|null $container = null;

    /**
     * 获取注解容器php-di
     * @return object
     * @throws \Exception
     */
    public static function container():object{
        if (empty(self::$container)){
            $builder = new \DI\ContainerBuilder();
            $builder->useAutowiring(true);
            $builder->useAnnotations(true);
            self::$container = $builder->build();
        }
        return  self::$container ;
    }
}