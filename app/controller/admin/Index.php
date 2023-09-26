<?php

/**
 * 本控制器演示了门面facade类的用法
 */

namespace App\Controller\Admin;

use APP\Facade\Cache;
use APP\Facade\User;
use Root\ESClient;
use Root\Request;
use Root\Response;


class Index
{

    public function index(Request $request)
    {
        $request->all();
        //return view('/index/index', ['time' => date('Y-m-d H:i:s')]);
        //return response(json_encode(['name'=>'zhangsan']),200)->file(public_path().'/favicon.ico');
        //return response()->download(public_path().'/favicon.ico','demo.ico');
        return \response()->cookie('zhangsan','tom');
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

    public function timer()
    {
        /** 添加定时任务，周期，回调函数，参数，是否循环执行 */
        \root\Timer::add(5, function ($a, $b) {
            var_dump("我只执行一次额");
            var_dump($a);
            var_dump($b);
        }, [3, 5], false);
        return 45;
    }

    /** 测试elasticsearch用法 */
    public function search()
    {
        /** 实例化es客户端 */
        $client = new ESClient();
        /** 查询节点的所有数据 */
        return $client->all('v2_es_user3','_doc');
    }

}
