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
        /** 注册信号 */
        self::installHandler();
        /** 注册完成后，设一个1秒的闹钟 */
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
     * @note 闹钟被唤醒后，执行这个函数
     */
    public static function signalHandler()
    {
        /** 处理业务逻辑 */
        self::task();
        /** 1秒后，触发信号sigalrm,执行Root\Timer::class 的signalHandler() */
        pcntl_alarm(1);
    }


    /**
     *执行回调
     * @具体的业务逻辑
     */
    public static function task()
    {
        $current = time();
        /** 取出所有的需要执行的定时任务 */
        $task = TimerData::where([['status', '>', 0], ['time', '=', $current]])->get();
        foreach ($task as $v) {
            /** 解码定时任务 首选内存，若没有则读取数据库 */
            $job = self::$task[$v['time']][$v['id']] ?? json_decode(base64_decode($v['data']), true);
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
            /** 处理用户定义的回调函数 */
            if (is_array($func)) {
                call_user_func([G($func[0]), $func[1]], $argv);
            } else {
                call_user_func_array($func, $argv);
            }
            /** 删除这个任务 */
            unset(self::$task[$v['time']][$v['id']]);
            if (empty(self::$task[$v['time']])){
                unset(self::$task[$v['time']]);
            }
            if ($persist) {
                /** 更新任务的下一次执行周期 */
                TimerData::where([['id', '=', $v['id']]])->update(['time' => $next_time]);
                /** 将任务投递到下一个周期 */
                self::$task[$next_time][$v['id']] = $job;
            } else {
                /** 关闭这个任务 */
                TimerData::where([['id', '=', $v['id']]])->update(['status' => 0]);
            }
        }
    }

    /**
     * 添加定时任务
     * @param int $interval 周期
     * @param mixed $func 回调函数
     * @param array $argv 参数
     * @param bool $persist 是否持久化
     * @return mixed
     */
    public static function add(int $interval, mixed $func, array $argv = array(), bool $persist = false)
    {
        if (!($interval)) {
            return false;
        }
        $time   = time() + $interval;
        $params = ['func' => $func, 'argv' => $argv, 'interval' => $interval, 'persist' => $persist];

        $data = base64_encode(json_encode($params));
        $id   = md5($data);
        /** 存入到sqlite */
        $add = TimerData::insert(['data' => $data, 'id' => $id, 'time' => $time, 'status' => 1]);
        /** 存入到内存，因为匿名函数不能转字符串存入数据库 */
        self::$task[$time][$id] = $params;
        return $add ? $id : false;
    }

    /**
     * 删除任务
     * @param string $id
     * @return mixed
     */
    public static function delete(string $id)
    {
        foreach (self::$task as $time=>$item){
            foreach ($item as $index=>$task){
                if ($id==$index){
                    unset(self::$task[$time][$id]);
                    if (empty(self::$task[$time])){
                        unset(self::$task[$time]);
                    }
                }
            }
        }
        return TimerData::where(['id', '=', $id])->delete();
    }


    /**
     * 触发闹钟
     * @return void
     */
    public static function tick()
    {
        \pcntl_signal_dispatch();
    }

    /**
     * 删除所有定时器任务
     * @return mixed
     */
    public static function deleteAll()
    {
        self::$task = [];
        return TimerData::where(['status', '>', 0])->update(['status' => 0]);
    }
}

