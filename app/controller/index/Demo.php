<?php

namespace App\Controller\Index;

use Root\Request;

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
        return $request->param();
    }
}