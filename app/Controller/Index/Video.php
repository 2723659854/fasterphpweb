<?php

namespace App\Controller\Index;

use Root\Annotation\Mapping\RequestMapping;
use Root\Request;
use Root\Response;
/**
 * @purpose 控制器
 * @author administrator
 * @time 2023-10-11 07:02:03
 */
class Video
{
    /**
     * index方法
     * @param Request $request 请求类
      * @return Response
     * @note 直播演示
     */
    #[RequestMapping(methods:'get',path:'/video/play')]
    public function index(Request $request):Response{
        return view('video/play');
    }
}