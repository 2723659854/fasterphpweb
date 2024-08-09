<?php

/** 屏幕大小 */
$width = 220;
$height = 35;

/** 雪花池 */
$snowColor = [
    /** 白色 */
    "\033[37m*\033[0m",
    /** 绿色 */
    "\033[32m*\033[0m",
    /** 红色 */
    "\033[31m*\033[0m",
    /** 蓝色 */
    "\033[34m*\033[0m",
    /** 紫色 */
    "\033[35m*\033[0m",
    /** 橙色 */
    "\033[33m*\033[0m",
    /** 青色 */
    "\033[36m*\033[0m",
    /** 加一个字母 */
    "\033[37m雪\033[0m",
    "\033[35m人\033[0m",
//    "\033[36m雪落人间\033[0m",
//    "\033[34m雪落人间\033[0m",


];


// 初始化雪花数组
$snowflakes = [];

// 生成初始的雪花
for ($i = 0; $i < 60; $i++) {

    $snowflakes[] = [
        'x' => rand(0, $width - 1),
        'y' => rand(0, $height - 1),
        'speedX' => rand(-1, 1),  // 左右移动速度，-1 表示向左，1 表示向右，0 表示静止
        'speedY' => rand(1, 2),  // 下落速度
        'word'=>$snowColor[rand(0,count($snowColor)-1)],//要显示的文案
    ];
}

while (true) {
    // 清屏
    system('cls');
    // 隐藏光标
    echo "\033[?25l";
    // 移动到固定区域的起始位置
    echo "\033[1;1H";

    // 绘制背景
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            /** 初始化当前位置没有雪花 */
            $isSnowflake = false;
            foreach ($snowflakes as $snowflake) {
                /** 当前坐标有雪花 */
                if ($snowflake['x'] == $x && $snowflake['y'] == $y) {
                    $isSnowflake = true;
                    break;
                }
            }
            if ($isSnowflake) {
                 /** 随机颜色雪花 */
                 //echo $snowColor[rand(0,count($snowColor)-1)];
                 /** 固定颜色雪花 */
                 echo $snowflake['word'];
            } else {
                echo ' ';
            }
        }
        echo PHP_EOL;
    }

    // 移动雪花
    foreach ($snowflakes as &$snowflake) {
        $snowflake['x'] += $snowflake['speedX'];
        $snowflake['y'] += $snowflake['speedY'];
        // 处理到达左右边界的情况
        if ($snowflake['x'] < 0) {
            $snowflake['x'] = 0;
            $snowflake['speedX'] = -$snowflake['speedX'];  // 改变方向
        } elseif ($snowflake['x'] >= $width) {
            $snowflake['x'] = $width - 1;
            $snowflake['speedX'] = -$snowflake['speedX'];  // 改变方向
        }
        // 处理雪花到达底部的情况
        if ($snowflake['y'] >= $height) {
            $snowflake['y'] = 0;
            $snowflake['x'] = rand(0, $width - 1);
            $snowflake['speedX'] = rand(-1, 1);  // 重新随机左右移动速度
            $snowflake['speedY'] = rand(1, 2);  // 重新随机下落速度
        }
    }

    // 暂停一段时间
    usleep(1000);
}
