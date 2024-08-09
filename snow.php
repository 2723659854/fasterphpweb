<?php
/**
 * @purpose 动画《雪飘人间》，使用php编写，不依赖第三份工具。
 * @note 原理：创建一块矩形的显示区域，然后初始化一组雪花，每一片雪花随机设置文案和颜色，以及横向和纵向的移动速度。然后使用死循环，在循环内渲染每一个
 * 坐标的图案，有雪花就渲染雪花，没有就渲染空气。然后根据移动速度分别更新雪花的横向和纵向的坐标。渲染完整个显示区域的所有左边，然后间隔一定时间后重新
 * 渲染显示区域。
 * @note 动画的本质是不停的刷新显示区域图像。只要事件足够短，那么两张画面看起来就是连贯的。
 * @note 从理论上来说，使用cli模式可以制作网络游戏。从窗口获取用户的输入，根据输入数据操作角色在动画中的显示。画面显示，用户输入，网络数据交换可以
 * 分别使用不动的cli窗口。那么是可以建立互联网游戏的。但是，工程量太大了，没有人会这么做。有兴趣的朋友可以自己尝试。
 * @note 最终版本了，不改了
 */

/** 屏幕大小 */
$width = 200;
$height = 35;
/** 文案池，雪花必须多余云朵和文字，更真实 */
$words = [
    "☁️", "☁️", "雪", "飘", "人", "间",  "雪飘人间", "*", "*", "*", "*", "*", "*", "*", "*","*", "*",
    "*", "*", "*", "*", "*", "*", "*", "*", "*", "*","*", "*", "*", "*", "*", "*", "*", "*", "*",
    "*", "*", "*", "*", "*", "*", "*", "*", "*", "*","*", "*", "*", "*", "*", "*", "*", "*", "*",
];
/** 云朵和闪电 */
$cloud = "☁️";
$light = "⚡";
/** 雪花密度 数据越大，雪越大 */
$snowCount = 100;
/** 初始化雪花池 */
$snowflakes = [];

/** 生成初始的雪花 */
for ($i = 0; $i < $snowCount; $i++) {
    /** 随机文案 */
    $index = rand(0, count($words) - 1);
    $text = $words[$index];
    /** 随机颜色，暗色系 */
    $rgb = rand(31, 37);
    /** 组合文案 */
    $word = "\033[{$rgb}m{$text}\033[0m";
    /** 云朵只能出现在天空 */
    $flag = false;
    if (in_array($index, [0, 1])) {
        $flag = true;
    }
    /** 如果有云朵 */
    $direction = 0;
    if ($flag) {
        $direction = rand(0, 1);
    }
    /** 雪花数据 */
    $snowflakes[] = [
        'x' => rand(0, $width - 1),
        'y' => $flag ? 0 : rand(0, $height - 1),
        /** 左右移动速度，-1 表示向左，1 表示向右，0 表示静止 */
        'speedX' => $flag ? ($direction ? 1 : -1) : rand(-1, 1),
        /** 下落速度 */
        'speedY' => rand(1, 1),
        /** 要显示的文案 */
        'word' => $word,
        /** 文案的坐标 */
        'index' => $index,
        "rgb" =>$rgb,
        "text"=>$text,
    ];
}

/** 循环渲染图像 */
while (true) {
    /** 清屏并移除历史记录 */
    echo "\033[2J\033[H";
    /** 隐藏光标 */
    echo "\033[?25l";
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
                    /** 模拟自然界的天空 随机出现闪电 */
                    if ((in_array($snowflake['y'], [1, 2, 3, 4, 5])) && (!in_array($snowflake['index'], [0, 1])) && (rand(1, 10) > 9)) {
                        /** 闪电只能出现在天空上面 ，并且不能覆盖云朵 */
                        //$line .= $light;
                        $rgb = rand(31, 37);
                        $num = rand(1,3);
                        $word = "";
                        while($num--){
                            $word .=$light;
                        }
                        $line .= "\033[{$rgb}m{$word}\033[0m";
                    } else {
                        /** 原始颜色 */
                        $rgb = $snowflake['rgb'];
                        /** 随机修改颜色为亮色，模拟真实雪花飘落的时候，看起来会闪烁 */
                        if (rand(0,100)>50){
                            $rgb +=60;
                        }
                        $text = $snowflake['text'];
                        $word = "\033[{$rgb}m{$text}\033[0m";
                        //$line .= $snowflake['word'];
                        $line .= $word;
                    }
                    break;
                }
            }
            /** 此坐标有雪花 */
            if (!$isSnowflake) {
                /** 此坐标没有雪花，渲染空气 */
                $line .= ' ';
            }
        }
        /** 一次性输出每行的字符串 */
        echo $line . PHP_EOL;
    }

    /** 移动雪花  更新每一片雪花的坐标 */
    foreach ($snowflakes as &$snowflake) {
        /** 2%的概率改变飘落方向，模拟雪花打折璇儿降落的情况，更真实 */
        if (!in_array($snowflake['index'], [0, 1])) {
            if (rand(1, 100) <= 2) {
                $snowflake['speedX'] = -$snowflake['speedX'];
            }
        }
        /** 更新雪花横向坐标 */
        $snowflake['x'] += $snowflake['speedX'];
        /** 更新雪花纵向坐标 */
        if (!in_array($snowflake['index'], [0, 1])) {
            $snowflake['y'] += $snowflake['speedY'];
        }
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
