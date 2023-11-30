<?php

namespace Root\Lib;

use Workerman\Lib\Timer;

/**
 * @purpose 重构worker
 */
class Worker extends \Workerman\Worker
{
    /**
     * 屏蔽乱七八糟的打印信息
     * @param $msg
     * @param $decorated
     * @return bool|void
     */
    public static function safeEcho($msg, $decorated = false){

    }

    /**
     * Init.
     *
     * @return void
     * @note 因为在phar://文件当中是不允许写入的，那么就需要真实的物理地址了，否则无法启动rtmp服务
     */
    protected static function init()
    {
        \set_error_handler(function($code, $msg, $file, $line){
            Worker::safeEcho("$msg in file $file on line $line\n");
        });

        // Start file.
        $backtrace        = \debug_backtrace();
        static::$_startFile = $backtrace[\count($backtrace) - 1]['file'];


        $unique_prefix = \str_replace('/', '_', static::$_startFile);

        is_dir(phar_app_path() . "/vendor/workerman/")||mkdir(phar_app_path() . "/vendor/workerman/",0777,true);
        // Pid file.
        if (empty(static::$pidFile)) {
            static::$pidFile = phar_app_path() . "/vendor/workerman/$unique_prefix.pid";
        }

        // Log file.
        if (empty(static::$logFile)) {
            static::$logFile = phar_app_path() . '/vendor/workerman/workerman.log';
        }
        $log_file = (string)static::$logFile;
        if (!\is_file($log_file)) {
            \touch($log_file);
            \chmod($log_file, 0622);
        }

        // State.
        static::$_status = static::STATUS_STARTING;

        // For statistics.
        static::$_globalStatistics['start_timestamp'] = \time();

        // Process title.
        static::setProcessTitle(static::$processTitle . ': master process  start_file=' . static::$_startFile);

        // Init data for worker id.
        static::initId();

        // Timer init.
        Timer::init();
    }
}