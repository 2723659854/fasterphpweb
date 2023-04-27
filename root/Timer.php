<?php

namespace Root;

/**
 *定时器
 */
class Timer
{

    public static $task = array();

    public static $time = 1;

    /**
     *开启服务
     * @param $time int
     */
    public static function run()
    {
        self::installHandler();
        pcntl_alarm(1);
    }

    /**
     *注册信号处理函数
     */
    public static function installHandler()
    {
        pcntl_signal(SIGALRM, array('Root\Timer', 'signalHandler'));
    }

    /**
     *信号处理函数
     */
    public static function signalHandler()
    {
        self::task();
        pcntl_alarm(1);
    }

    /**
     *执行回调
     */
    public static function task()
    {
        if (empty(self::$task)) {
            return;
        }
        foreach (self::$task as $time => $arr) {
            $current = time();
            foreach ($arr as $k => $job) {
                $func = $job['func'];
                $argv = $job['argv'];
                $interval = $job['interval'];
                $persist = $job['persist'];
                if ($current == $time) {
                    call_user_func_array($func, $argv);
                    unset(self::$task[$time][$k]);
                    if ($persist) {
                        self::$task[$current + $interval][] = $job;
                    }
                }
            }
            if (empty(self::$task[$time])) {
                unset(self::$task[$time]);
            }
        }
    }

    /**
     * 添加定时任务
     * @param int $interval 周期
     * @param callable $func 回调函数
     * @param array $argv 参数
     * @param bool $persist 是否持久化
     * @return void
     */

    public static function add(int $interval, callable $func, array $argv = array(), bool $persist = false)
    {
        if (!($interval)) {
            return ;
        }
        self::$time = $interval;
        $time = time() + $interval;
        self::$task[$time][] = array('func' => $func, 'argv' => $argv, 'interval' => $interval, 'persist' => $persist);
        /**
         * 存在两个问题
         * 1，直接添加的话，因为存在进程隔离，导致定时任务不能添加到另外一个进程中
         * 2，如果在添加的时候创建一个子进程处理，那么http的进程就被阻塞了
         * 3，解决办法，添加了子进程后，怎么脱离子进程控制
         */
//        $id=pcntl_fork();
//        if ($id>0){
//            \cli_set_process_title("xiaosongshu_timer_".rand(1,100));
//            writePid();
//            Timer::run();
//            while (true) {
//                pcntl_signal_dispatch();
//                sleep(1);
//            }
//        }

    }

    /**
     * 删除所有定时器任务
     */
    public function dellAll()
    {
        self::$task = array();
    }
}

