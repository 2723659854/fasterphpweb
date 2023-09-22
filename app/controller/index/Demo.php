<?php

namespace App\Controller\Index;

use App\Service\DemoService;
use Root\Request;
use Root\Timer;

/**
 * @purpose 控制器
 * @author administrator
 * @time 2023年9月8日13:59:53
 */
class Demo
{
    /**
     * index方法
     * @param Request $request 请求类
     * @return string|string[]|null
     */
    public function index(Request $request){
        var_dump(runtime_path().'/timer');
        Timer::add(4,function (){
            echo "sdfhkasghasgss\r\n";

        },[],false);
        /** 测试使用容器获取服务类 */
        return ['data'=>G(DemoService::class)->talk(1)];
    }
}