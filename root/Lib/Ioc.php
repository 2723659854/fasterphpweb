<?php

namespace Root\Lib;
/**
 *
 * 工具类，使用该类来实现自动依赖注入。
 *
 */
class Ioc
{
    // 获得类的对象实例
    protected static function getInstance($className)
    {
        $paramArr = self::getMethodParams($className);
        return (new \ReflectionClass($className))->newInstanceArgs($paramArr);
    }

    /**
     * 执行类的方法
     * @param [type] $className [类名]
     * @param [type] $methodName [方法名称]
     * @param [type] $params   [额外的参数]
     * @return [type]       [description]
     */
    public static function make($className, $methodName = '', $params = [])
    {
        // 获取类的实例
        $instance = self::getInstance($className);
        // 获取该方法所需要依赖注入的参数
        $paramArr = self::getMethodParams($className, $methodName);
        if ($methodName){
            return $instance->{$methodName}(...array_merge($paramArr, $params));
        }else{
            return $instance;
        }
    }

    /**
     * 获得类的方法参数，只获得有类型的参数
     * @param [type] $className  [description]
     * @param [type] $methodsName [description]
     * @return [type]       [description]
     * @note  这个是原来的解析参数的方法
     */
    protected static function getMethodParamsCopy($className, $methodsName = '__construct')
    {
        // 通过反射获得该类
        $class    = new \ReflectionClass($className);
        $paramArr = []; // 记录参数，和参数类型
        // 判断该类是否有构造函数
        if ($methodsName) {
            if ($class->hasMethod($methodsName)) {
                // 获得构造函数
                $construct = $class->getMethod($methodsName);
                // 判断构造函数是否有参数
                $params = $construct->getParameters();
                if (count($params) > 0) {
                    // 判断参数类型
                    foreach ($params as $key => $param) {
                        if ($paramClass = $param->getType()) {
                            // 获得参数类型名称
                            $paramClassName = $paramClass->getName();
                            // 获得参数类型
                            $args       = self::getMethodParams($paramClassName);
                            $paramArr[] = (new \ReflectionClass($paramClass->getName()))->newInstanceArgs($args);
                        }
                    }
                }
            }
        }
        return $paramArr;
    }

    /**
     * 获得类的方法参数，只获得有类型的参数
     * @param string $className 类名
     * @param string $methodsName 方法名
     * @return array
     * @throws \ReflectionException
     * @note 这是被修正后的解析对象的依赖的参数
     */
    protected static function getMethodParams(string $className, string $methodsName = '__construct')
    {
        /** 通过反射获得该类 */
        $class = new \ReflectionClass($className);
        /** 记录参数，和参数类型 */
        $paramArr = [];
        /** 判断该类是否有构造函数 */
        if ($methodsName) {
            if ($class->hasMethod($methodsName)) {
                /** 获得构造函数 */
                $construct = $class->getMethod($methodsName);
                /** 判断构造函数是否有参数 */
                $params = $construct->getParameters();
                if (count($params) > 0) {
                    /** 判断参数类型 */
                    foreach ($params as $key => $param) {
                        /** 获取参数类型 */
                        if ($paramClass = $param->getType()) {
                            /** 获得参数类型名称 */
                            $paramClassName = $paramClass->getName();

                            /** 如果参数是回调函数 ，那么就设置一个空的回调函数 */
                            if (strtolower($paramClassName) == 'string'){
                                $paramArr[] = '';
                                continue;
                            }
                            /** 如果参数是回调函数 ，那么就设置一个空的回调函数 */
                            if (strtolower($paramClassName) == 'int'){
                                $paramArr[] = 0;
                                continue;
                            }
                            /** 如果参数是回调函数 ，那么就设置一个空的回调函数 */
                            if (strtolower($paramClassName) == 'float'){
                                $paramArr[] = 0.0;
                                continue;
                            }
                            /** 如果参数是回调函数 ，那么就设置一个空的回调函数 */
                            if (strtolower($paramClassName) == 'bool'){
                                $paramArr[] = false;
                                continue;
                            }
                            /** 如果参数是回调函数 ，那么就设置一个空的回调函数 */
                            if (strtolower($paramClassName) == 'array'){
                                $paramArr[] = [];
                                continue;
                            }
                            /** 如果参数是回调函数 ，那么就设置一个空的回调函数 */
                            if (strtolower($paramClassName) == 'null'){
                                $paramArr[] = null;
                                continue;
                            }
                            /** 如果参数是回调函数 ，那么就设置一个空的回调函数 */
                            if (strtolower($paramClassName) == 'callable'){
                                $paramArr[] = function (){};
                                continue;
                            }
                            /** 如果参数类型是object ,那么直接上静态类 */
                            if (strtolower($paramClassName) == 'object') {
                                $paramArr[] = new \stdClass();
                                continue;
                            }

                            if (strtolower($paramClassName) == 'resource'){
                                $paramArr[] = null;
                                continue;
                            }
                            /** 加载特殊的自定义类型 */
                            if (strpos($paramClassName, '\\')) {
                                //self::loadFile($paramClassName);
                            }
                            /** 获得参数类型 */
                            $args = self::getMethodParams($paramClassName);
                            $paramArr[] = (new \ReflectionClass($paramClass->getName()))->newInstanceArgs($args);
                        }else{
                            /** 没有定义数据类型，那么参数设置位null */
                            $paramArr[] = null;
                            continue;
                        }
                    }
                }
            }
        }
        return $paramArr;
    }

    /**
     * 加载对象的文件
     * @param string $paramClass
     * @return bool
     * @throws \Exception
     * @note 这个加载文件的方法在本框架中不适用，因为本项目默认会加载所有的文件到内存中
     */
    protected static function loadFile(string $paramClass)
    {
        if (class_exists($paramClass)){
            return true;
        }
        $file = APP_PATH . "/" . $paramClass . ".php";
        if (!is_file($file)) {
            $file = APP_PATH . "/" . $paramClass . ".class.php";
            if (!is_file($file)) {
                throw new \Exception("class not found:" . $paramClass);
            }
        }
        require_once $file;
        return true;
    }

    /**
     * 手动创建对象
     * @param string $name
     * @param mixed $buffer
     * @return mixed
     * @throws \Exception
     */
    public static function set(string $name,$buffer){
        self::loadFile($name);
        return new $name(...$buffer);
    }
}