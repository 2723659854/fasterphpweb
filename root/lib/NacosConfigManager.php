<?php

namespace Root\Lib;

use Root\Core\Provider\RestartProvider;
use Root\Xiaosongshu;

class NacosConfigManager
{

    public static $dataId = 'xiaosongshu';
    public static $group = 'public';
    public static $serviceName = 'demo';
    public static $namespace = 'public';

    /**
     * 监听配置
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @note 如果配置发生了变化，那么系统将会重启服务
     */
    public static function sync()
    {
        $config = config('nacos');
        $host = $config['host']??'127.0.0.1';
        $port = $config['port']??'8848';
        $username = $config['username'];
        $password = $config['password'];
        $client      = new \Xiaosongshu\Nacos\Client("http://{$host}:{$port}",$username,$password);
        /** 获取本地配置 */
        $content = yaml();
        /** 监听配置 */
        $response = $client->listenerConfig(self::$dataId, self::$group, json_encode($content));

        /** 说明配置发生了变化 */
        if (isset($response['content'])&&($response['content'])){

            /** 获取服务器上的配置 */
            $config_from_nacos = $client->getConfig(self::$dataId,self::$group,self::$namespace);

            if ($config_from_nacos['status']==200){
                /** 更新配置文件 */
                $config_content = json_decode($config_from_nacos['content'],true);
                /** 保存yaml文件 */
                array2yaml($config_content,'/config.yaml');
                /** 然后重启所有服务 */
                Xiaosongshu::restart();
            }
        }
    }

    /**
     * 发布配置
     * @return array
     * @throws \Exception
     */
    public static function publish(){
        $config = config('nacos');
        $host = $config['host']??'127.0.0.1';
        $port = $config['port']??'8848';
        $username = $config['username'];
        $password = $config['password'];
        $client      = new \Xiaosongshu\Nacos\Client("http://{$host}:{$port}",$username,$password);

        /** 获取本地配置 */
        $content = yaml();
        /** 发布配置 */
        return $client->publishConfig(self::$dataId,self::$group,json_encode($content));
    }
}