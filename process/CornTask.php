<?php

namespace Process;

use Root\Timer;

/**
 * 定时器测试用例
 */
class CornTask
{

    /**
     * 测试静态方法
     * @param array $params
     * @return void
     */
    public static function handle(array $params){

        var_dump('我是静态方法');
    }

    /**
     * 测试动态方法
     * @return void
     */
    public  function say(){
        var_dump("我是动态方法");
    }

}