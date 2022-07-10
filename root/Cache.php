<?php
namespace Root;
use Redis;
class Cache
{
    private $client=null;
    private static $instance = null;

    public function __construct()
    {
        try{
            $config=config('redis');
            $client=new Redis();
            $client->connect($config['host'],$config['port']);
            $this->client=$client;
            $this->client=$client;
        }catch (\Exception $exception){
            die('致命错误：redis连接失败！详情：'.$exception->getMessage());
        }

    }

    /**
     * 设置缓存
     * @param string $key 缓存名称
     * @param string $value 缓存值
     * @param int|null $time 有效期
     */
    public function set(string $key,string $value,int $time=null){
        if ($time){
            $this->client->set($key,$value,$time);
        }else{
            $this->client->set($key,$value);
        }

    }

    /**
     * 获取缓存
     * @param string $key 缓存名称
     * @return string|null 返回值
     */
    public function get(string $key){
        return $this->client->get($key);
    }

    /**
     * 单例模式 ,这里暂时不用单例，因为要用到观察者模式
     * @return object
     */
    public static function getInstance(): object
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function lpush($key,array $value){
        return $this->client->lPush($key,$value);
    }

    public function lpop($key){
        return $this->client->lPop($key);
    }

    public function rpush($key,$value){
        return $this->client->rPush($key,$value);
    }

    public function rpop($key){
        return $this->client->rPop($key);
    }

    public function setex($key, $ttl, $value)
    {
        return $this->client->setex($key, $ttl, $value);
    }

    public function psetex($key, $ttl, $value)
    {
        return $this->client->psetex($key, $ttl, $value);
    }
    public function setnx($key, $value)
    {
        return $this->client->setnx($key, $value);
    }
    public function delete($key1, $key2 = null, $key3 = null)
    {
        return $this->client->delete($key1, $key2 = null, $key3 = null);
    }
    public function del($key1, ...$otherKeys)
    {
        return $this->client->del($key1, ...$otherKeys);
    }
    public function exists($key)
    {
        return $this->client->exists($key);
    }
    public function decr($key)
    {
        return $this->client->decr($key);
    }

    public function incrBy($key, $value)
    {
        return $this->client->incrBy($key, $value);
    }

    public function __call($name, $arguments)
    {
        return $this->client->$name($arguments);
    }
}
