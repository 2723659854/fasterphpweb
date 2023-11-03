<?php

namespace Root\Lib;

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
}