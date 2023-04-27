<?php

/**
 * 本控制器演示了门面facade类的用法
 */

namespace App\Controller\Admin;

use APP\Facade\Cache;
use APP\Facade\User;
use App\Time\OtherTimer;
use Root\Timer;

class Index
{

    public function index()
    {
        return '/admin/index/index';
    }

    //模型
    public function model()
    {
        //echo __METHOD__;
        $res = User::table('user')->where('username', '=', 'test')->first();

        return "use facade/model ,the model data is " . json_encode($res);
    }

    //缓存
    public function cache()
    {
        Cache::set('hot', '55');
        //print_r(Cache::get('hot'));
        return 'use facade/cache,and the cache data is :' . Cache::get('hot');
    }

    public function timer(){
        /** 因为是不同的进程，所以没有执行 */
        $res=\root\Timer::add(5, function () {
            var_dump("喔喔");
        }, [], true);
    return 45;
    }

}
