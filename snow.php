<?php
/**
 * @purpose 动画《雪飘人间》，使用php编写，不依赖第三份工具。
 * @note 原理：创建一块矩形的显示区域，然后初始化一组雪花，每一片雪花随机设置文案和颜色，以及横向和纵向的移动速度。然后使用死循环，在循环内渲染每一个
 * 坐标的图案，有雪花就渲染雪花，没有就渲染空。然后根据移动速度分别更新雪花的横向和纵向的坐标。渲染完整个显示区域的所有左边，然后间隔一定时间后重新
 * 渲染显示区域。
 * @note 动画的本质是不停的刷新显示区域图像。只要事件足够短，那么两张画面看起来就是连贯的。
 * @note 从理论上来说，使用cli模式可以制作网络游戏。从窗口获取用户的输入，根据输入数据操作角色在动画中的显示。画面显示，用户输入，网络数据交换可以
 * 分别使用不动的cli窗口。那么是可以建立互联网游戏的。但是，工程量太大了，没有人会这么做。有兴趣的朋友可以自己尝试。
 */

/** 屏幕大小 */
$width = 220;
$height = 35;

/** 颜色池 */
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
];

/** 文案池 */
$words = [
    "雪", "落", "人", "间", "雪落人间", "*", "*", "*", "*", "*", "*", "*", "*", "*",
    "*", "*", "*", "*", "*", "*", "*", "*", "*", "*", "*", "*", "*", "*", "*", "*",
];


/** 初始化雪花池 */
$snowflakes = [];

/** 生成初始的雪花 */
for ($i = 0; $i < 60; $i++) {
    /** 随机文案 */
    $text = $words[rand(0, count($words) - 1)];
    /** 随机颜色 */
    $rgb = rand(31, 37);
    /** 组合文案 */
    $word = "\033[{$rgb}m{$text}\033[0m";
    /** 雪花数据 */
    $snowflakes[] = [
        'x' => rand(0, $width - 1),
        'y' => rand(0, $height - 1),
        /** 左右移动速度，-1 表示向左，1 表示向右，0 表示静止 */
        'speedX' => rand(-1, 1),
        /** 下落速度 */
        'speedY' => rand(1, 1),
        /** 要显示的文案 */
        'word' => $word,
    ];
}
/** 循环渲染图像 */
while (true) {
    /** 清屏 */
    system('cls');
    /** 隐藏光标 */
    echo "\033[?25l";
    /** 移动到固定区域的起始位置 固定区域显示 */
    echo "\033[1;1H";

    /** 绘制背景 ，检测显示区域的每一个坐标是否有雪花，并渲染 */
    for ($y = 0; $y < $height; $y++) {
        /** 先在内存中构建每行的字符串 */
        $line = '';
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
            /** 此坐标有雪花 */
            if ($isSnowflake) {
                /** 固定颜色雪花 */
                $line .= $snowflake['word'];
            } else {
                /** 此坐标没有雪花 */
                $line .= ' ';
            }
        }
        /** 一次性输出每行的字符串 */
        echo $line . PHP_EOL;
    }

    /** 移动雪花  更新每一片雪花的坐标 */
    foreach ($snowflakes as &$snowflake) {
        /** 更新雪花横向坐标 */
        $snowflake['x'] += $snowflake['speedX'];
        /** 更新雪花纵向坐标 */
        $snowflake['y'] += $snowflake['speedY'];
        /** 处理到达左右边界的情况 如果横向已经超出屏幕 ，然后更换方向 */
        if ($snowflake['x'] < 0) {
            $snowflake['x'] = 0;
            /** 改变方向 */
            $snowflake['speedX'] = -$snowflake['speedX'];
        } elseif ($snowflake['x'] >= $width) {
            $snowflake['x'] = $width - 1;
            /** 改变方向 */
            $snowflake['speedX'] = -$snowflake['speedX'];
        }
        /** 处理雪花到达底部的情况 */
        if ($snowflake['y'] >= $height) {
            $snowflake['y'] = 0;
            $snowflake['x'] = rand(0, $width - 1);
            /** 重新随机左右移动速度 */
            $snowflake['speedX'] = rand(-1, 1);
            /** 重新随机下落速度 */
            $snowflake['speedY'] = rand(1, 1);
        }
    }

    /** 暂停一段时间 刷新画面间隔 */
    usleep(50000);
}
