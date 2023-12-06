<?php

namespace Root\Queue;

use Root\Annotation\AnnotationRoute;
use Root\Xiaosongshu;

/**
 * @purpose http服务
 * @note 仅限于windows环境
 */
class HttpConsumer
{

    public function handle(){
        /** 加载路由 */
        G(\Root\Route::class)->loadRoute();
        AnnotationRoute::loadRoute();
        /** windows系统使用select模型 */
        G(Xiaosongshu::class)->select();
    }

}