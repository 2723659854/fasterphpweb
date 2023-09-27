<?php

namespace Root\Queue;

use Root\Timer;

class TimerConsumer
{

    /** 执行定时器 */
    public function consume()
    {
        /** 在这里添加定时任务 ，然后发送信号 */
        foreach (config('timer') as $name => $value) {
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
            usleep(1000);
        }
    }

}