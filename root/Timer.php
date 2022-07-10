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
     *添加任务
     */
    public static function add($interval, $func, $argv = array(), $persist = false)
    {
        if (is_null($interval)) {
            return;
        }
        self::$time = $interval;
        $time = time() + $interval;
        self::$task[$time][] = array('func' => $func, 'argv' => $argv, 'interval' => $interval, 'persist' => $persist);
    }

    /**
     *删除所有定时器任务
     */
    public function dellAll()
    {
        self::$task = array();
    }
}

