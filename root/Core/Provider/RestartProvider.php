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
        global $_system,$_color_class,$_lock_file,$_listen,$_daemonize,$_start_server_file_lock;
        $_daemonize = true;
        if ($_system) {
            $app->close();
            echo $_color_class->info("\r\n服务重启中\r\n");
            /** 等待2秒，因为关闭服务需要消耗一定时间 */
            sleep(2);
        } else {
            echo $_color_class->info("当前环境是windows,只能在控制台运行\r\n");
        }
        $fd  = fopen($_lock_file, 'w');
        $_start_server_file_lock = $fd;
        $res = flock($fd, LOCK_EX | LOCK_NB);
        if (!$res) {
            //echo $_color_class->info($_listen . "\r\n");
            echo $_color_class->info("服务正在运行，请勿重复启动，你可以使用stop停止运行或者使用restart重启\r\n");
            exit(0);
        }
        /** 加载路由 */
        G(\Root\Route::class)->loadRoute();
        AnnotationRoute::loadRoute();
        $app->daemon();
    }
}