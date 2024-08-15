<?php

// 获取终端宽度和高度
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
    /** 每6个数字一个渐变色段，先生成渐变色的最亮色 */
    $colors = range(21, 231, 6);
    $numbers = count($colors) - 1;
    /** 返回一个随机的亮色 */
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
            $lastColor = getRandomColor();
            $string = "\033[38;5;{$lastColor}m*\033[0m";
            //$canvas[$y0][$x0] = '*';
            $canvas[$y0][$x0] = $string;
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

function drawCube($width, $height, $angleX, $angleY, $scale)
{
    $canvas = array_fill(0, $height, array_fill(0, $width, ' '));

    // 立方体的顶点
    $vertices = [
        [-1, -1, -1],
        [1, -1, -1],
        [1, 1, -1],
        [-1, 1, -1],
        [-1, -1, 1],
        [1, -1, 1],
        [1, 1, 1],
        [-1, 1, 1]
    ];

    // 旋转矩阵
    $cosX = cos($angleX);
    $sinX = sin($angleX);
    $cosY = cos($angleY);
    $sinY = sin($angleY);

    $rotatedVertices = [];

    foreach ($vertices as $vertex) {
        $x = $vertex[0];
        $y = $vertex[1];
        $z = $vertex[2];

        // 绕X轴旋转
        $yz = $y * $cosX - $z * $sinX;
        $z = $y * $sinX + $z * $cosX;
        $y = $yz;

        // 绕Y轴旋转
        $xz = $x * $cosY - $z * $sinY;
        $z = $x * $sinY + $z * $cosY;
        $x = $xz;

        // 应用缩放因子
        $x *= $scale;
        $y *= $scale;

        // 将坐标调整为画布坐标系
        $rotatedVertices[] = [$x + $width / 2, $y + $height / 2];
    }

    // 定义立方体的边
    $edges = [
        [0, 1], [1, 2], [2, 3], [3, 0],
        [4, 5], [5, 6], [6, 7], [7, 4],
        [0, 4], [1, 5], [2, 6], [3, 7]
    ];

    foreach ($edges as $edge) {
        $x1 = (int)$rotatedVertices[$edge[0]][0];
        $y1 = (int)$rotatedVertices[$edge[0]][1];
        $x2 = (int)$rotatedVertices[$edge[1]][0];
        $y2 = (int)$rotatedVertices[$edge[1]][1];

        drawLine($canvas, $x1, $y1, $x2, $y2);
    }

    return $canvas;
}

list($width, $height) = getTerminalSize();
$angleX = 0;
$angleY = 0;
$angleStep = 0.1;
$scale = min($width, $height) / 7; // 缩小比例，确保立方体适应终端

while (true) {
    echo "\033[H\033[J";
    $canvas = drawCube($width, $height, $angleX, $angleY, $scale);
    foreach ($canvas as $line) {
        echo implode('', $line) . PHP_EOL;
    }

    $angleX += $angleStep;
    $angleY += $angleStep;
    if ($angleX >= 2 * M_PI) $angleX -= 2 * M_PI;
    if ($angleY >= 2 * M_PI) $angleY -= 2 * M_PI;

    usleep(100000); // 100ms
}
?>
