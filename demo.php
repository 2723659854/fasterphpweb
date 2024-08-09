<?php
require_once __DIR__.'/vendor/autoload.php';

use Xiaosongshu\Colorword\Transfer;
/** 实例化字体颜色转化类 */
$transfer = new Transfer();
/** 输入需要转换颜色的文字内容，设置文字颜色，设置背景色 */
echo $transfer->getColorString("红字蓝底","red","blue");
echo "\r\n";
echo $transfer->info("提示信息");
echo "\r\n";
echo $transfer->error("错误信息");
echo "\r\n";
echo $transfer->line("普通信息");
echo "\r\n";


