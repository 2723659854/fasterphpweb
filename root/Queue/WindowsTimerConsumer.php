<?php

namespace Root\Queue;

use Root\Lib\NacosConfigManager;
use Root\Timer;
use Root\TimerData;
use SuperClosure\Serializer;

/**
 * @purpose windows版本的定时器
 */
class WindowsTimerConsumer
{

    /** 执行定时器 */
    public static function handle($config = [])
    {
        /** 创建表 */
        TimerData::first();
        /** 这里存在问题，不同的进程先后执行问题 */
        Timer::deleteAll();
        /** 在这里添加定时任务 ，然后发送信号 */
        foreach ($config as $name => $value) {
            if ($value['enable']) {
                Timer::add($value['time'], $value['function'], [], $value['persist']);
            }
        }
        while (1){
            self::task();
            /** 防止进程空转，占用资源 */
            usleep(100);
        }
    }

    /**
     *执行回调
     * @具体的业务逻辑
     */
    public static function task()
    {
        $current = time();
        /** 定时任务只允许1秒的误差 ，如果执行时间超过1秒，建议使用其他方式 */
        $task       = TimerData::where([['status', '>', 0],['time','>=',$current-1],['time','<=',$current]])->get();
        $serializer = new Serializer();
        foreach ($task as $v) {
            /** 解码定时任务 */
            $job = json_decode(base64_decode($v['data']), true);
            /** 回调方法 */
            $func = $job['func'];
            /** 参数 */
            $argv = $job['argv'];
            /** 周期 */
            $interval = $job['interval'];
            /** 是否持久化 */
            $persist = $job['persist'];
            /** 下次执行时间 */
            $next_time = $current + $interval;
            if ($persist) {
                /** 更新任务的下一次执行周期 */
                TimerData::where([['id', '=', $v['id']]])->update(['time' => $next_time]);
            } else {
                /** 关闭这个任务 */
                TimerData::where([['id', '=', $v['id']]])->update(['status' => 0]);
            }
            /** 处理用户定义的回调函数 */
            if (is_array($func)) {
                call_user_func([G($func[0]), $func[1]], $argv);
            } else {
                $func = $serializer->unserialize($func);
                call_user_func_array($func, $argv);
            }
        }
    }
}