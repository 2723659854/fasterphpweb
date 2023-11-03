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
            /** nacos 服务管理必须放这里，放定时器太耗时了，而且会导致定时器数据丢失  */
            if (time()%30==0){/** 每隔30秒检查一次 */
                if (config('nacos')['enable']){
                    NacosConfigManager::sync();
                }
            }
        }
    }

}