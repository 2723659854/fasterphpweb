<?php

namespace Root\Queue;

use Root\Lib\NacosConfigManager;
use Root\Timer;

class TimerConsumer
{

    /** 执行定时器 */
    public function consume()
    {
        /** 如果是主进程，则设置进程名称为master，管理定时器 */
        $create = pcntl_fork();
        
        if ($create) {
            cli_set_process_title("xiaosongshu_timer");
            /** 这里存在问题，不同的进程先后执行问题 */
            Timer::deleteAll();
            /** 在这里添加定时任务 ，然后发送信号 */
            foreach (config('timer') as $value) {
                if ($value['enable']) {
                    Timer::add($value['time'], $value['function'], [], $value['persist']);
                }
            }
            /** 启动定时器 */
            Timer::run();
            while (true) {
                /** 拦截闹钟信号*/
                Timer::tick();
                /** 切换CPU */
                usleep(100);
            }
        }
    }

}