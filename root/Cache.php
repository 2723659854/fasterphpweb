<?php
namespace Root;

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
     * @return mixed
     */
    public static function connect(){
        try{
            $config=config('redis');
            $client=new \Redis();
            $client->connect($config['host'],$config['port']);
            if ($config['password'])$client->auth($config['password']);
            self::$client=$client;
        }catch (\Exception $e){
            throw new \RuntimeException($e->getMessage());
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
        try {
            if (!self::$client){
                self::connect();
            }
            return self::$client->$name(...$arguments);
        }catch (\RedisException $exception){
            if ($exception->getMessage()=='Connection lost'){
                /** redis默认没有语法错误 */
                self::connect();
                return self::$client->$name(...$arguments);
            }else{
                throw new \RuntimeException($exception->getMessage());
            }
        }

    }

    /**
     * 静态化调用
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name,$arguments){
        try {
            if (!self::$client){
                self::connect();
            }
            return self::$client->$name(...$arguments);
        }catch (\Exception $exception){
          if ($exception->getMessage()=='Connection lost'){
              /** redis默认没有语法错误 */
              self::connect();
              return self::$client->$name(...$arguments);
          }else{
              throw new \RuntimeException($exception->getMessage());
          }
        }
    }
}
