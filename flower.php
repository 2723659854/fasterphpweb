<?php
/**
 * @purpose 万花筒
 * @note php在cli模式下，是以字节的形式输出，所以分辨率很低。php也有做UI的gdk，比如PHP-GDK，但是已经很久没有更新了。因为php的初衷是做服务端
 * 开发语言，是做web开发的语言。不是用来做客户端开发语言。
 * @note php在cli模式下，也可以实现语音播放，需要特定的扩展。
 */
/** 画布的尺寸 */
$width = 40; // 画布宽度
$height = 20; // 画布高度
/** 圆心 */
$centerX = $width / 2; // 几何中心X
$centerY = $height / 2; // 几何中心Y
/** 每次刷新页面只生成一个星星 */
$numStars = 1; // 每一帧生成的星星数量
/** 最大页面同时存在10个星星 */
$maxStars = 20; // 最大星星数量
/** 刷新时间 */
$delay = 0.1; // 延迟（秒）
/** 流星尾巴长度 因为一个色系的长度是6所以最长设置为6 */
$trailLength = 6; // 轨迹长度（星星的单位）
/** 是否流线型，确定了尾巴是否紧紧的跟随流星 */
$isWaterLine = true;//是否流线型运动

/**
 * 获取终端宽度和高度
 * @return array|int[]
 */
function getTerminalSize()
{

    if (PHP_OS_FAMILY === 'Windows') {
        // Windows 系统
        $cmd = 'mode con'; // Windows 命令行获取控制台尺寸
        $output = shell_exec($cmd);

        // 解析控制台尺寸
        preg_match('/Columns:\s*(\d+)/', $output, $widthMatch);
        preg_match('/Lines:\s*(\d+)/', $output, $heightMatch);

        $width = isset($widthMatch[1]) ? (int)$widthMatch[1] : 60; // 默认值
        $height = isset($heightMatch[1]) ? (int)$heightMatch[1] : 40; // 默认值
    } else {
        // Linux 系统
        $size = [];
        if (preg_match('/(\d+)x(\d+)/', shell_exec('stty size'), $size)) {
            $width = $size[1];
            $height = $size[2];
        } else {
            $width = 60; // 默认值
            $height = 40; // 默认值
        }
    }

    return ['width' => $width, 'height' => $height];
}

/** 控制台尺寸 */
$terminalSize = getTerminalSize();

$termWidth = $terminalSize['width'];
$termHeight = $terminalSize['height'];

/** 计算固定区域的起始位置以居中显示 */
$startX = ($termWidth - $width) / 2;
$startY = ($termHeight - $height) / 2;

/** 存储星星的数组 */
$stars = [];
/** 画布 */
$canvas = array_fill(0, $height, array_fill(0, $width, ' '));
/** 轨迹画布，存储轨迹 */
$trail = array_fill(0, $height, array_fill(0, $width, []));

/**
 * 生成随机颜色（256色模式）
 */
function getRandomColor()
{
    /** 每6个数字一个渐变色段，先生成渐变色的最亮色 */
    $colors = range(21, 231, 6);
    $numbers = count($colors)-1;
    /** 返回一个随机的亮色 */
    return $colors[rand(0,$numbers)];
}

/**
 * 生成渐变颜色
 * @param $baseColor
 * @param $fadeLevel
 * @return string
 * @note 但是cli模式下这个颜色对比实在太小了吧，
 */
function getFadedColor($baseColor, $fadeLevel)
{
    /** 颜色逐渐变暗 */
    return intval($baseColor) - $fadeLevel;
}

/**
 * 生成新的星星
 * @param int $numStars 流星总数
 * @param bool $isWaterLine 是否流线型
 * @return array
 */
function generateStars(int $numStars, bool $isWaterLine)
{
    $stars = [];
    for ($i = 0; $i < $numStars; $i++) {
        $stars[] = [
            /** 随机初始角度，转换为弧度 决定流星在圆心中抛射出来的方向 */
            'angle' => mt_rand(0, 360) * M_PI / 180, //
            /** 从中心开始生成流星 若大于0则中间会留一个空腔 */
            'radius' => 0, //
            /** 调整星星速度,半径增加的速度 ，值越大，轨迹沿直径方向变化越大 */
            'speed' => $isWaterLine ? 0.1 : (0.1 + 0.1 * mt_rand(0, 5)), //
            /** 调整角速度，角度增加的速度，值越大，星星旋转的越快，绕的圆周越多 */
            'angleSpeed' => $isWaterLine ? 0.03 : (0.03 * mt_rand(1, 2)), //
            /** 随机颜色 */
            'color' => getRandomColor() //
        ];

    }
    return $stars;
}

/**
 * 清除画布中的内容
 * @param array $canvas
 * @return void
 */
function clearCanvas(array &$canvas)
{
    foreach ($canvas as $y => &$line) {
        foreach ($line as $x => &$pixel) {
            $pixel = ' ';
        }
    }
}

/**
 * 保存光标位置
 * @return void
 */
function saveCursorPosition()
{
    echo "\033[s";
}

/**
 * 恢复光标位置
 * @return void
 */
function restoreCursorPosition()
{
    echo "\033[u";
}

while (true) {
    /** 清屏并移除历史记录 */
    echo "\033[H\033[J";
    /** 隐藏光标 */
    echo "\033[?25l";
    /** 保存光标位置 */
    saveCursorPosition();

    /** 每一帧生成新的星星（只在最大星星数量内）*/
    if (count($stars) <= $maxStars) {
        $stars = array_merge($stars, generateStars($numStars, $isWaterLine));
    }

    /** 更新每个星星的位置 临时存储有效的星星 */
    $newStars = [];
    foreach ($stars as &$star) {
        /** 坐标使用了三角函数计算 */
        $star['radius'] += $star['speed']; // 半径增加，模拟径向位移
        $star['angle'] += $star['angleSpeed']; // 角度增加，模拟旋转
        /** x 坐标 = 圆心x坐标 + 半径 x 角度的余弦 */
        $x = $centerX + (int)($star['radius'] * cos($star['angle']));
        /** y 坐标 = 圆心y坐标 + 半径 x 角度的正弦 */
        $y = $centerY + (int)($star['radius'] * sin($star['angle']));

        /** 确保星星位置在画布内 */
        if ($x >= 0 && $x < $width && $y >= 0 && $y < $height) {
            // 更新轨迹
            for ($i = 0; $i <= $trailLength; $i++) {
                /** 尾巴总是离圆心更近一些，越是后面的尾巴，离圆心越近 */
                /** 尾巴的x坐标 = 圆心点x的坐标 + （头部的半径 - 尾巴的长度） x 圆角的余弦 */
                $trailX = $centerX + (int)(($star['radius'] - $i * $star['speed']) * cos($star['angle']));
                /** 尾巴的y坐标 = 圆心的y坐标 + （头部的半径 - 尾巴的长度） x 圆角的正弦 */
                $trailY = $centerY + (int)(($star['radius'] - $i * $star['speed']) * sin($star['angle']));
                /** 尾巴还在画布内 */
                if ($trailX >= 0 && $trailX < $width && $trailY >= 0 && $trailY < $height) {
                    // 添加颜色到轨迹，并保证第二个星星颜色较暗
                    /** 因为流星的速度不一样，存在交叉的情况，所以会存在流星尾巴重合的情况，所以同一个坐标会有多个星星，按顺序存储星星 */
                    $trail[$trailY][$trailX][] = getFadedColor($star['color'], $i);
                }

            }
            /** 记录有效的星星 */
            $newStars[] = $star;
        }
    }
    /** 更新星星数组 */
    $stars = $newStars;
    /** 清除画布并绘制新的内容 */
    clearCanvas($canvas);
    /** 绘制轨迹：只绘制了轨迹，而并没有绘制流星本身 */
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            /** 如果这个坐标有流星的尾巴 */
            if (!empty($trail[$y][$x])) {
                /** 这里必须按顺序获取星星的数据，否则流星颜色会混乱，这一步很关键 */
                $lastColor = array_shift($trail[$y][$x]);
                /** 渲染当前坐标的流星 */
                $canvas[$y][$x] = "\033[38;5;{$lastColor}m*\033[0m";
            }
        }
    }

    /** 恢复光标位置 */
    restoreCursorPosition();

    /** 输出画布 */
    for ($y = 0; $y < $height; $y++) {
        /** 这里是逐行渲染动画内容 先移动光标到左边界 打印前导空格以居中 */
        echo str_repeat(' ', (int)$startX);
        /** 将这一行的数据全部串联起来打印，换行 */
        echo implode('', $canvas[$y]) . PHP_EOL;
    }

    /** usleep的单位是微秒 画面需要停留一定时间，以便在用户眼中停留形成影像 当刷新时间设置为200000 ，20000画面稳定一些，不会闪烁 */
    usleep($delay * 200000);
}
