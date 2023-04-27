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
        /** 创建子进程，在子进程中执行定时任务，否则主进程被阻塞 */
        $id=pcntl_fork();
        writePid();
        if ($id){
            $son=pcntl_fork();
            writePid();
            /** 在二级子进程中执行定时器 */
            if ($son){
                \cli_set_process_title("xiaosongshu_timer_".rand(1,1000000));
                writePid();
                self::run();
                while (true) {
                    pcntl_signal_dispatch();
                    sleep(1);
                    /** 定时器里面的任务为空，则退出进程 */
                    if (empty(self::$task)){
                        exit;
                    }
                }
            }else{
                /** 脱离一级子进程控制 */
                exit;
            }
        }
        /** 主进程不阻塞，继续执行后面的逻辑 */
    }

    /**
     * 删除所有定时器任务
     */
    public function dellAll()
    {
        self::$task = array();
    }
}

