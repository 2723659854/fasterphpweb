<?php

/**
 * 获取终端宽度和高度
 * @return array|int[]
 */
function getTerminalSize()
{
    if (PHP_OS_FAMILY === 'Windows') {
        $cmd = 'mode con'; // Windows系统获取控制台尺寸的命令
        $output = shell_exec($cmd); // 执行命令并获取输出
        preg_match('/Columns:\s*(\d+)/', $output, $widthMatch); // 匹配列宽
        preg_match('/Lines:\s*(\d+)/', $output, $heightMatch); // 匹配行高
        $width = isset($widthMatch[1]) ? (int)$widthMatch[1] : 80; // 获取列宽
        $height = isset($heightMatch[1]) ? (int)$heightMatch[1] : 25; // 获取行高
    } else {
        $size = [];
        if (preg_match('/(\d+)x(\d+)/', shell_exec('stty size'), $size)) {
            $width = $size[1]; // 获取宽度
            $height = $size[2]; // 获取高度
        } else {
            $width = 80; // 默认宽度
            $height = 25; // 默认高度
        }
    }
    return [$width, $height]; // 返回宽度和高度
}

/**
 * 生成随机颜色（256色模式）
 */
function getRandomColor()
{
    /** 每6个数字一个渐变色段，先生成渐变色的最亮色 */
    $colors = range(21, 231, 6); // 生成色彩范围
    $numbers = count($colors) - 1; // 色彩数量
    /** 返回一个随机的亮色 */
    return $colors[rand(0, $numbers)]; // 随机返回一个色彩
}

/**
 * 使用Bresenham算法绘制直线
 * @param array $canvas 画布数组，二维数组，表示终端的显示区域
 * @param int $x0 起始点的x坐标
 * @param int $y0 起始点的y坐标
 * @param int $x1 结束点的x坐标
 * @param int $y1 结束点的y坐标
 */
function drawLine(&$canvas, $x0, $y0, $x1, $y1)
{
    $dx = abs($x1 - $x0); // x方向的距离
    $dy = abs($y1 - $y0); // y方向的距离
    $sx = ($x0 < $x1) ? 1 : -1; // x方向的步长
    $sy = ($y0 < $y1) ? 1 : -1; // y方向的步长
    $err = $dx - $dy; // 误差值

    while (true) {
        // 如果坐标在画布范围内
        if ($x0 >= 0 && $x0 < count($canvas[0]) && $y0 >= 0 && $y0 < count($canvas)) {
            $lastColor = getRandomColor(); // 获取随机颜色
            $string = "\033[38;5;{$lastColor}m*\033[0m"; // 生成带颜色的字符
            $canvas[$y0][$x0] = $string; // 在画布上绘制字符
        }
        if ($x0 == $x1 && $y0 == $y1) break; // 结束条件
        $e2 = $err * 2; // 误差值的两倍
        if ($e2 > -$dy) {
            $err -= $dy; // 调整误差
            $x0 += $sx; // 更新x坐标
        }
        if ($e2 < $dx) {
            $err += $dx; // 调整误差
            $y0 += $sy; // 更新y坐标
        }
    }
}


/**
 * 绘制立方体
 * @param int $width 终端的宽度
 * @param int $height 终端的高度
 * @param float $angleX 绕X轴的旋转角度
 * @param float $angleY 绕Y轴的旋转角度
 * @param float $scale 缩放因子
 * @return array 画布数组
 */
function drawCube($width, $height, $angleX, $angleY, $scale)
{
    $canvas = array_fill(0, $height, array_fill(0, $width, ' ')); // 初始化画布

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
    $cosX = cos($angleX); // 绕X轴的余弦值
    $sinX = sin($angleX); // 绕X轴的正弦值
    $cosY = cos($angleY); // 绕Y轴的余弦值
    $sinY = sin($angleY); // 绕Y轴的正弦值

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

        drawLine($canvas, $x1, $y1, $x2, $y2); // 绘制边
    }

    return $canvas; // 返回画布
}


list($width, $height) = getTerminalSize(); // 获取终端的宽度和高度
$angleX = 0; // 初始X轴旋转角度
$angleY = 0; // 初始Y轴旋转角度
$angleStep = 0.1; // 每次更新角度的步长
$scale = min($width, $height) / 7; // 缩放因子，使立方体适应终端

while (true) {
    echo "\033[H\033[J"; // 清屏
    $canvas = drawCube($width, $height, $angleX, $angleY, $scale); // 绘制立方体
    foreach ($canvas as $line) {
        echo implode('', $line) . PHP_EOL; // 输出画布内容
    }

    $angleX += $angleStep; // 更新X轴角度
    $angleY += $angleStep; // 更新Y轴角度
    if ($angleX >= 2 * M_PI) $angleX -= 2 * M_PI; // 保持X轴角度在0到2π之间
    if ($angleY >= 2 * M_PI) $angleY -= 2 * M_PI; // 保持Y轴角度在0到2π之间

    usleep(100000); // 100ms 延时
}

?>
