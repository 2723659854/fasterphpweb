<?php

namespace Root\Core\Provider;

use Root\Annotation\AnnotationRoute;
use Root\Xiaosongshu;

/**
 * @purpose 重启服务
 */
class RestartProvider implements IdentifyInterface
{

    public function handle(Xiaosongshu $app,array $param){
        global $_system,$_color_class,$_lock_file,$_listen,$_daemonize;
        $_daemonize = true;
        if ($_system) { $app->close();  echo $_color_class->info("服务重启中\r\n"); }
        else { echo $_color_class->info("当前环境是windows,只能在控制台运行\r\n"); }
        $fd  = fopen($_lock_file, 'w');
        $res = flock($fd, LOCK_EX | LOCK_NB);
        if (!$res) {
            echo $_color_class->info($_listen . "\r\n");
            echo $_color_class->info("服务正在运行，请勿重复启动，你可以使用stop停止运行或者使用restart重启\r\n");
            exit(0);
        }
        /** 加载路由 */
        G(\Root\Route::class)->loadRoute();
        G(AnnotationRoute::class)->loadRoute();
        $app->daemon();
    }

}