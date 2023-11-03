<?php

namespace App\Controller\Index;

use Root\Request;
use Root\Response;
/**
 * @purpose 控制器
 * @author administrator
 * @time 2023-11-03 04:29:29
 */
class Fuck
{
    /**
     * index方法
     * @param Request $request 请求类
      * @return Response
     */
    public function index(Request $request):Response{
        return response($request->all());
    }
}