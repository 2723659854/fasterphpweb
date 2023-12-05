<?php
namespace Root\Core\Provider;
use Root\Annotation\AnnotationRoute;
use Root\Xiaosongshu;

/**
 * @purpose 开启服务
 */
class StartProvider implements IdentifyInterface
{

    public function handle(Xiaosongshu $app,array $param){
        global $_system,$_color_class,$_listen,$_system_table,$_has_epoll,$_lock_file,$_daemonize,$_start_server_file_lock;
        if (isset($param[2]) && ($param[2] == '-d')) {
            if ($_system) { $daemonize = true; $_daemonize = true;}
            else { echo $_color_class->info("当前环境是windows,只能在控制台运行\r\n"); echo "\r\n"; }
        }
        /** 运行加锁 */
        $fd  = fopen($_lock_file, 'w');
        $_start_server_file_lock = $fd;
        $res = flock($fd, LOCK_EX | LOCK_NB);
        if (!$res) {
            echo $_color_class->info($_listen . "\r\n");
            echo $_color_class->info("服务正在运行，请勿重复启动，你可以使用stop停止运行或者使用restart重启\r\n");
            exit(0);
        }
        echo $_color_class->info("进程启动中...\r\n");
        /** 加载路由 */
        G(\Root\Route::class)->loadRoute();
        AnnotationRoute::loadRoute();
        if (!empty($daemonize)){
            $app->daemon();
        }else{

            if ($_system){
                /** 只开启http服务 */
                $open = [
                    ['http', '正常', '1', $_listen]
                ];
                $_system_table->table(['名称', '状态', '进程数', '服务'], $open);
                echo $_color_class->info("进程启动完成,你可以按ctrl+c停止运行\r\n");
            }

            /** 开启http调试模式 */
            if ($_system && $_has_epoll) {
                /** linux系统使用epoll模型 */
                $app->epoll();
            } else {
                /** windows系统使用select模型 */
                $app->select();
            }
        }
    }
}