<?php

/**
 * 本控制器演示了门面facade类的用法
 */

namespace App\Controller\Admin;

use APP\Facade\Cache;
use APP\Facade\User;

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
}
