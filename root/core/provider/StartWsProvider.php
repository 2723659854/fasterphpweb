<?php

namespace Root\Core\Provider;

use Root\Queue\RedisQueueConsumer;
use Root\Xiaosongshu;

/**
 * @purpose 开启ws服务
 * @note 调试用
 */
class StartWsProvider implements IdentifyInterface
{

    public function handle(Xiaosongshu $app,array $param){
        $file  = $param[2]??'';
        $className= str_replace('.',"\\",$file);
        if ($className){
            try {
                $server = G($className);
            }catch (\Exception $exception){
                echo "找不到指定的类{$className}\r\n";
                exit;
            }
            $host = $server->host??'127.0.0.1';
            $port = $server->port??'9501';
            echo "开启ws服务：ws://{$host}:{$port} 按ctrl+c关闭服务\r\n";
            $server->start();
        }else{
            echo "{$file}文件不存在\r\n";
        }
    }

}