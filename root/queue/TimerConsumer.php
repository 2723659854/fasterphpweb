<?php

namespace Root\Queue;

use Root\Lib\NacosConfigManager;
use Root\Timer;
use Root\TimerData;

class TimerConsumer
{

    /** 执行定时器 */
    public function consume()
    {
        /** 先清空所有的定时器 */
        Timer::deleteAll();
        /** 在这里添加定时任务 ，然后发送信号 */
        foreach (config('timer') as $name => $value) {
            if ($value['enable']) {
                Timer::add($value['time'], $value['function'], [], $value['persist']);
            }
        }
        /** 主进程内加一个定时器负责处理 */
        $nacos_enable = config('nacos')['enable']??false;
        /** 如果开起了nacos配置管理，则添加到定时任务中 */
        if ($nacos_enable){
            Timer::add(5,[NacosConfigManager::class,'sync'],[],true);
        }
        /** 如果是主进程，则设置进程名称为master，管理定时器 */
        cli_set_process_title("xiaosongshu_master");
        writePid();
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