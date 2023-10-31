<?php
namespace Root\Core\Provider;
use Root\Xiaosongshu;

/**
 * @purpose 关闭服务
 */
class StopProvider implements IdentifyInterface
{

    public function handle(Xiaosongshu $app,array $param){
        global $_system,$_color_class;
        if ($_system) { $app->close();}
        else { echo $_color_class->info("当前环境是windows,只能在控制台运行\r\n"); }
        $flag = false;
        echo $_color_class->info("服务关闭完成\r\n");
        exit;
    }
}