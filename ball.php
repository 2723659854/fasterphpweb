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
 * @note 已知起点和终点，绘制线段
 * @note Bresenham算法总结起来就是，为了维持直线的倾斜度，当y方向增量过大，倾斜度被增加了，就需要增加一个x方向单位，以降低倾斜度所以要减一个y方向增量。
 * 当x方向增量过大，那么说明直线的倾斜度变小了，需要增加一个y方向的单位使得倾斜度增加，倾斜度要增加一个x方向增量。
 */
function drawLine(&$canvas, $x0, $y0, $x1, $y1)
{
    /** x方向增量 */
    $dx = abs($x1 - $x0); // x方向的距离
    /** y方向增量 */
    $dy = abs($y1 - $y0); // y方向的距离
    /** 确定坐标变化方向 增大还是减小，步长为1 */
    $sx = ($x0 < $x1) ? 1 : -1; // x方向的步长
    $sy = ($y0 < $y1) ? 1 : -1; // y方向的步长
    /** 这个实际上是斜率，值越大说明直线的倾角越大 */
    $err = $dx - $dy; // 误差值
    /** 使用点绘制线 */
    while (true) {
        // 如果坐标在画布范围内
        if ($x0 >= 0 && $x0 < count($canvas[0]) && $y0 >= 0 && $y0 < count($canvas)) {
            $lastColor = getRandomColor(); // 获取随机颜色
            $string = "\033[38;5;{$lastColor}m*\033[0m"; // 生成带颜色的字符
            $canvas[$y0][$x0] = $string; // 在画布上绘制字符
        }
        /** 当绘制到终点，则不再绘制点 */
        if ($x0 == $x1 && $y0 == $y1) break; // 结束条件
        /** 计算误差值 */
        $e2 = $err * 2; // 误差值的两倍
        /** 调整x坐标: 如果误差值大于y方向增量，则需要在x方向上移动一个单位。*/
        /** 说明直线变陡了，是y方向变化过大，需要x方向补偿一个单位 以便维持斜率 ，那么斜率减一个y增量 */
        if ($e2 > -$dy) {
            /** 一次递减一个y方向增量 */
            $err -= $dy; // 调整误差
            $x0 += $sx; // 更新x坐标
        }
        /** 如果x方向增量大于误差，则需要在y方向上移动一个单位。 */
        /** 说明直线变平了，x防线增量过大，直线倾斜度变小，y方向补偿一个单位，斜率加一个x增量 */
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
 * @note 使用将三维图形降维到二维的方式绘制图形
 */
function drawCube($width, $height, $angleX, $angleY, $scale)
{
    $canvas = array_fill(0, $height, array_fill(0, $width, ' ')); // 初始化画布

    /** 一个立方体有8个顶点 ，通过点确定线，由线构成面 */
    // 立方体的顶点 在三维坐标系里面，一共有8个象限，每个象限里面有一个顶点，这个8个顶点构成了立方体
    $vertices = [
        /** 0坐标 */
        [-1, -1, -1],
        /** 1坐标 */
        [1, -1, -1],
        /** 2坐标 */
        [1, 1, -1],
        /** 3坐标 */
        [-1, 1, -1],
        /** 4坐标 */
        [-1, -1, 1],
        /** 5坐标 */
        [1, -1, 1],
        /** 6坐标 */
        [1, 1, 1],
        /** 7坐标 */
        [-1, 1, 1]
    ];

    // 旋转矩阵
    $cosX = cos($angleX); // 绕X轴的余弦值
    $sinX = sin($angleX); // 绕X轴的正弦值
    $cosY = cos($angleY); // 绕Y轴的余弦值
    $sinY = sin($angleY); // 绕Y轴的正弦值

    /** 旋转后的顶点坐标 */
    $rotatedVertices = [];

    /** 遍历所有顶点 */
    foreach ($vertices as $vertex) {
        /** 获取每一个顶点的坐标 */
        $x = $vertex[0];
        $y = $vertex[1];
        $z = $vertex[2];

        /**
         * 三角函数都快忘完了
         * 这里使用了降维的方式处理坐标旋转问题，绕x轴旋转的时候，相当于是在yz组成的平面上旋转角度a
         * 旋转后的坐标可以用三角函数计算
         * x1 = x0*cos(a) - y0*sin(a)
         * y1 = x0*sin(a) + y0*cos(a)
         *
         * 这一个公式的推导依赖于另外一个组三角函数和角公式
         * cos(a+b) = cos(a)cos(b) - sin(a)sin(b)
         * sin(a+b) = sin(a)cos(b) + cos(a)sin(b)
         *
         * */
        // 绕X轴旋转 那么x坐标不变，y坐标和z坐标发生变化

        /** 绕x轴旋转后，y坐标 = (y坐标 乘 绕X轴的余弦值) - (z坐标 乘 绕X轴的正弦值) */
        $yz = $y * $cosX - $z * $sinX;
        /** 绕x轴旋转后，z坐标 = (y坐标 乘 绕Y轴的正弦值) + (z坐标 乘 绕Y轴的余弦值) */
        $z = $y * $sinX + $z * $cosX;
        $y = $yz;

        // 绕Y轴旋转
        $xz = $x * $cosY - $z * $sinY;
        $z = $x * $sinY + $z * $cosY;
        $x = $xz;
        /** 将更新后的x坐标和y坐标等比例放大 */
        // 应用缩放因子
        $x *= $scale;
        $y *= $scale;

        /** 按顺序存入8个顶点的新坐标 */
        // 将坐标调整为画布坐标系
        $rotatedVertices[] = [$x + $width / 2, $y + $height / 2];
    }

    // 定义立方体的边
    /** 顶点构成线的原理：
     * 首先构建同一个平面的四条线，然后确定对面的四个顶点，将四个顶点连接成线，然后连接这两个平行面的顶点。
     * 同一个平面，四个顶点递增链接，不可交叉链接，公差是1
     * 平行平面链接，每一个顶点和对面的顶点的公差是4
     * */

    /** 8个顶点构成12条线 */
    $edges = [[0, 1],[1, 2],[2, 3],[3, 0],[4, 5],[5, 6],[6, 7],[7, 4],[0, 4],[1, 5],[2, 6],[3, 7]];

    /** 遍历12条边 一条边有两个端点确定 */
    foreach ($edges as $edge) {
        /** 起点的坐标 */
        $x1 = (int)$rotatedVertices[$edge[0]][0];
        $y1 = (int)$rotatedVertices[$edge[0]][1];
        /** 终点的坐标 */
        $x2 = (int)$rotatedVertices[$edge[1]][0];
        $y2 = (int)$rotatedVertices[$edge[1]][1];
        /** 已知起点和终点，则可以绘制直线线段 */
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
    /** 清屏并移除历史记录 */
    echo "\033[H\033[J";
    /** 隐藏光标 */
    echo "\033[?25l";
    //echo "\033[H\033[J"; // 清屏
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
