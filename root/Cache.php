<?php
namespace Root;
use Redis;
class Cache
{
    /** redis连接 */
    private static $client;

    /**
     * 初始化
     */
    public function __construct()
    {
        if (!self::$client){
            $this->connect();
        }
    }

    /**
     * 连接redis服务器
     * @return void
     */
    public function connect(){
        try{
            $config=config('redis');
            $client=new Redis();
            $client->connect($config['host'],$config['port']);
            self::$client=$client;
        }catch (\Exception $exception){
            die('致命错误：redis连接失败！详情：'.$exception->getMessage());
        }
    }

    /**
     * 动态调用某一个某有定义的方法
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return self::$client->$name(...$arguments);
    }

    /**
     * 静态化调用
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name,$arguments){
        return self::$client->$name(...$arguments);
    }
}
