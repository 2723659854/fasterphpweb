<?php

namespace Root\Queue;

/**
 * @purpose ws服务
 */
class WsConsumer
{

    public function consume(){
        foreach (config('ws') as $name => $value){
            if ($value['enable']){
                if (!class_exists($value['handler'])){
                    throw new \Exception("{$value['handler']}不存在");
                }
                $create = pcntl_fork();
                if ($create>0){
                    usleep(1000);
                    writePid();
                    cli_set_process_title($name . '_' .rand(10000,99999));
                    $server = G($value['handler']);
                    $server->host = $value['host'];
                    $server->port = $value['port'];
                    $server->start();
                }
            }
        }
    }

}