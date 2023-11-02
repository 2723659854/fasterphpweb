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
        cli_set_process_title("xiaosongshu_master");
        writePid();
        $my_process_id = posix_getpid();
        file_put_contents(app_path().'/root/master_id.txt',$my_process_id);
        /** 启动定时器 */
        Timer::run();
        while (true) {
            /** 拦截闹钟信号*/
            Timer::tick();
            /** 切换CPU */
            usleep(100);
            if (time()%30==0){
                NacosConfigManager::sync();
            }
        }
    }

}