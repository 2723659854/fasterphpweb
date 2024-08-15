<?php


/**
 * 获取终端宽度和高度
 * @return array|int[]
 */
function getTerminalSize()
{
    if (PHP_OS_FAMILY === 'Windows') {
        $cmd = 'mode con';
        $output = shell_exec($cmd);
        preg_match('/Columns:\s*(\d+)/', $output, $widthMatch);
        preg_match('/Lines:\s*(\d+)/', $output, $heightMatch);
        $width = isset($widthMatch[1]) ? (int)$widthMatch[1] : 80;
        $height = isset($heightMatch[1]) ? (int)$heightMatch[1] : 25;
    } else {
        $size = [];
        if (preg_match('/(\d+)x(\d+)/', shell_exec('stty size'), $size)) {
            $width = $size[1];
            $height = $size[2];
        } else {
            $width = 80;
            $height = 25;
        }
    }
    return [$width, $height];
}

/**
 * 生成随机颜色（256色模式）
 */
function getRandomColor()
{
    $colors = range(21, 231, 6);
    $numbers = count($colors) - 1;
    return $colors[rand(0, $numbers)];
}

/**
 * 使用Bresenham算法绘制直线
 */
function drawLine(&$canvas, $x0, $y0, $x1, $y1)
{
    $dx = abs($x1 - $x0);
    $dy = abs($y1 - $y0);
    $sx = ($x0 < $x1) ? 1 : -1;
    $sy = ($y0 < $y1) ? 1 : -1;
    $err = $dx - $dy;

    while (true) {
        if ($x0 >= 0 && $x0 < count($canvas[0]) && $y0 >= 0 && $y0 < count($canvas)) {
            $canvas[$y0][$x0] = '*'; // 绘制像素点
        }
        if ($x0 == $x1 && $y0 == $y1) break;
        $e2 = $err * 2;
        if ($e2 > -$dy) {
            $err -= $dy;
            $x0 += $sx;
        }
        if ($e2 < $dx) {
            $err += $dx;
            $y0 += $sy;
        }
    }
}

/**
 * 绘制x轴和y轴
 */
function drawAxis(&$canvas, $width, $height, $color = 15)
{
    // 绘制x轴
    drawLine($canvas, 0, round($height / 2), $width - 1, round($height / 2));

    // 绘制y轴
    drawLine($canvas, round($width / 2), 0, round($width / 2), $height - 1);

    // 绘制坐标轴交点
    $canvas[round($height / 2)][round($width / 2)] = "\033[38;5;{$color}m+\033[0m";
}

// 获取终端大小
list($width, $height) = getTerminalSize();

// 创建画布
$canvas = array_fill(0, $height, array_fill(0, $width, ' '));

// 绘制坐标轴
drawAxis($canvas, $width, $height);

// 输出画布
echo "\033[H\033[J"; // 清屏
foreach ($canvas as $line) {
    echo implode('', $line) . PHP_EOL;
}

