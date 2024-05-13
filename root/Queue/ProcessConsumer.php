<?php

namespace Root\Queue;

/**
 * @purpose 自定义进程服务
 */
class ProcessConsumer
{

    public function consume(){

        foreach (config('process') as $name => $value){
            if ($value['enable']){
                if (!class_exists($value['handler'])){
                    throw new \Exception("{$value['handler']}不存在");
                }
                $count = $value['count']??1;
                while($count){
                    $create = pcntl_fork();
                    if ($create>0){
                        usleep(1000);
                        cli_set_process_title('xiaosongshu_process_.'.$name.'_'.$count);
                        G($value['handler'])->handle();
                    }
                    $count--;
                }

            }
        }
    }

}