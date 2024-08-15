<?php

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

        $width = isset($widthMatch[1])? (int)$widthMatch[1] : 80; // 调整默认值为 80
        $height = isset($heightMatch[1])? (int)$heightMatch[1] : 24; // 调整默认值为 24
    } else {
        // Linux 系统
        $size = [];
        if (preg_match('/(\d+)x(\d+)/', shell_exec('stty size'), $size)) {
            $width = $size[1];
            $height = $size[2];
        } else {
            $width = 80; // 调整默认值为 80
            $height = 24; // 调整默认值为 24
        }
    }

    return ['width' => $width, 'height' => $height];
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

/**
 * 生成随机颜色（256 色模式）
 * @return int
 */
function getRandomColor()
{
    return rand(16, 231); // ANSI 256 色的范围
}

/**
 * 生成渐变颜色
 * @param int $baseColor
 * @param int $fadeLevel
 * @return int
 */
function getFadedColor($baseColor, $fadeLevel)
{
    return max(16, intval($baseColor) - $fadeLevel); // 保证颜色不低于 16
}

// 设置半径
$radius = 8;
$aspectRatio = 1; // 将纵横比调整为 1

// 获取控制台的宽度和高度
$terminalSize = getTerminalSize();
$width = $terminalSize['width'];
$height = $terminalSize['height'];

// 计算圆心
$centerX = round($width / 2);
$centerY = round($height / 2);

// 计算圆的起始位置
$startX = $centerX - $radius;
$startY = $centerY - $radius;

// 设置旋转参数
$angle = 0; // 初始旋转角度
$angleStep = 0.1; // 每次旋转的角度步长

while (true) {
    // 创建一个二维数组来表示图形
    $canvas = array_fill(0, $height, array_fill(0, $width, ' '));

    // 清屏并移除历史记录
    echo "\033[H\033[J";
    // 隐藏光标
    echo "\033[?25l";
    // 保存光标位置
    saveCursorPosition();

    // 计算圆形的旋转
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            // 计算每个点相对于圆心的坐标
            $dx = ($x - $centerX);
            $dy = $y - $centerY;

            // 应用旋转矩阵
            $rotX = cos($angle) * $dx - sin($angle) * $dy;
            $rotY = sin($angle) * $dx + cos($angle) * $dy;

            // 计算旋转后的点在圆上的距离
            $distance = sqrt(pow($rotX, 2) + pow($rotY, 2));

            // 如果距离接近半径，就绘制字符
            if (abs($distance - $radius) < 0.5) {
                $color = getRandomColor();
                $canvas[$y][$x] = "\033[38;5;{$color}m*\033[0m";
            }
        }
    }

    // 恢复光标位置
    restoreCursorPosition();

    // 输出画布
    for ($y = 0; $y < $height; $y++) {
        // 计算前导空格的数量以居中圆圈
        $leadingSpaces = floor(($width - count($canvas[$y])) / 2);
        // 这里是逐行渲染动画内容
        echo str_repeat(' ', $leadingSpaces). implode('', $canvas[$y]). PHP_EOL;
    }

    // 更新角度
    $angle += $angleStep;
    if ($angle >= 2 * M_PI) {
        $angle -= 2 * M_PI;
    }

    usleep(100000); // 控制帧率（100ms）
}