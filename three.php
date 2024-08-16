<?php

/**
 * 本程序旨在绘制三维动画，实现原理就是给定一组构成图像的基本点，以及连接这些基本点构成图像的的方法，然后给出不同时间这些点所在的位置。当程序运行的
 * 时候，没一个时间点都计算当前时刻基本点的位置，然后使用给定的路径连接这些基本点，构成了图像。
 * 本程序将三维图像投射到二维平面，然后获取二维平面各点的投影坐标。使得生成图像的透视效果。
 * 可以增加x方向和y方向偏移量，实现对三维图像的位移控制，那么理论上是可以实现动漫的效果。需要控制角色的位移路线，也就是每一个时刻的x和y的便宜量，
 * 用来做动漫的话，估计数据量很庞大，做普通的游戏应该没问题，遇到边界就返回。
 * 然后不停的擦除旧的图像，绘制新的图像，就形成了动画。
 */

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
 * 绘制金字塔
 * @param $width
 * @param $height
 * @param $angleX
 * @param $angleY
 * @param $angleZ
 * @param $scale
 * @return array
 * @note 只要确定了三维图形的每一个顶点的坐标，以及这些顶点的连线路径，就可以绘制三维图形。
 * @note 这里可以抽象成一个公共方法，传入终端的宽高，x,y,z方向的旋转角度，缩放比例，三维图像的顶点坐标，三维图像的边构成方式。
 * 如果要实现在三维空间的位移，那么可以在转换过的坐标加上偏移量即可。
 */
function drawPyramid($width, $height, $angleX, $angleY, $angleZ, $scale, $distancX = 0, $distanceY = 0, $canvas = [])
{
    //$canvas = array_fill(0, $height, array_fill(0, $width, ' '));

    if (empty($canvas)) {
        $canvas = array_fill(0, $height, array_fill(0, $width, ' '));
    }
    // 定义金字塔的顶点
    $vertices = [
        // 底面四个点
        [-1, -1, -1], // 0
        [1, -1, -1],  // 1
        [1, 1, -1],   // 2
        [-1, 1, -1],  // 3
        // 顶点
        [0, 0, 1]     // 4
    ];

    // 旋转矩阵和坐标转换（与立方体相同）
    $cosX = cos($angleX);
    $sinX = sin($angleX);
    $cosY = cos($angleY);
    $sinY = sin($angleY);
    $cosZ = cos($angleZ);
    $sinZ = sin($angleZ);

    /** 这里是将三维图像的顶点投影到二维平面 */
    $rotatedVertices = [];
    foreach ($vertices as $vertex) {
        $x = $vertex[0];
        $y = $vertex[1];
        $z = $vertex[2];

        $xz = $x * $cosZ - $y * $sinZ;
        $y = $x * $sinZ + $y * $cosZ;
        $x = $xz;

        $yz = $y * $cosX - $z * $sinX;
        $z = $y * $sinX + $z * $cosX;
        $y = $yz;

        $xz = $x * $cosY - $z * $sinY;
        $z = $x * $sinY + $z * $cosY;
        $x = $xz;

        $x *= $scale;
        $y *= $scale;
        /** 只取被位移后的x和y坐标 ，就完成了三维到二维的转换 */
        $rotatedVertices[] = [$x + $distancX + $width / 2, $y + $distanceY + $height / 2];
    }

    // 定义金字塔的边
    $edges = [
        [0, 1], [1, 2], [2, 3], [3, 0], // 底面四条边
        [0, 4], [1, 4], [2, 4], [3, 4]  // 从顶点到底面四个点的边
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


// 使用上述相同的终端绘制和更新逻辑


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
 * @param float $angleZ 绕Z轴的旋转角度
 * @param float $scale 缩放因子
 * @param int $distancX x轴方向偏移量，用于控制动画位移
 * @param int $distanceY y轴方向偏移量，用于控制动画位移
 * @return array 画布数组
 * @note 使用将三维图形降维到二维的方式绘制图形
 */
function drawCube($width, $height, $angleX, $angleY, $angleZ, $scale, $distancX = 0, $distanceY = 0, $canvas = [])
{
    //$canvas = array_fill(0, $height, array_fill(0, $width, ' ')); // 初始化画布
    if (empty($canvas)) {
        $canvas = array_fill(0, $height, array_fill(0, $width, ' '));
    }
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
    $cosZ = cos($angleZ); // 绕Z轴的余弦值
    $sinZ = sin($angleZ); // 绕Z轴的正弦值

    /** 旋转后的顶点坐标 */
    $rotatedVertices = [];

    /** 遍历所有顶点 */
    foreach ($vertices as $vertex) {
        /** 获取每一个顶点的坐标 */
        $x = $vertex[0];
        $y = $vertex[1];
        $z = $vertex[2];

        // 绕Z轴旋转
        $xz = $x * $cosZ - $y * $sinZ;
        $y = $x * $sinZ + $y * $cosZ;
        $x = $xz;

        // 绕X轴旋转
        $yz = $y * $cosX - $z * $sinX;
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
        $rotatedVertices[] = [$x + $distancX + $width / 2, $y + $distanceY + $height / 2];
    }

    // 确保$rotatedVertices数组包含8个顶点
    if (count($rotatedVertices) !== 8) {
        throw new Exception('未能正确计算所有顶点坐标');
    }

    // 定义立方体的边
    /** 顶点构成线的原理：
     * 首先构建同一个平面的四条线，然后确定对面的四个顶点，将四个顶点连接成线，然后连接这两个平行面的顶点。
     * 同一个平面，四个顶点递增链接，不可交叉链接，公差是1
     * 平行平面链接，每一个顶点和对面的顶点的公差是4
     * */

    /** 8个顶点构成12条线 */
    $edges = [[0, 1], [1, 2], [2, 3], [3, 0], [4, 5], [5, 6], [6, 7], [7, 4], [0, 4], [1, 5], [2, 6], [3, 7]];

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
            'speed' => $isWaterLine ? 0.09 : (0.1 + 0.1 * mt_rand(0, 5)), //
            /** 调整角速度，角度增加的速度，值越大，星星旋转的越快，绕的圆周越多 */
            'angleSpeed' => $isWaterLine ? 0.03 : (0.03 * mt_rand(1, 2)), //
            /** 随机颜色 */
            'color' => getRandomColor() //
        ];

    }
    return $stars;
}

/**
 * 计算物体的坐标
 * @param array $config
 * @param array $canvas
 * @return array
 * @note 将三维坐标透视到二维坐标
 */
function computeCoordinateFor3D(array $config = [], array $canvas = [])
{
    /** 终端宽度  */
    $width = $config['width'] ?? 80;
    /** 终端高度 */
    $height = $config['height'] ?? 40;
    /** 绕x旋转角度 */
    $angleX = $config['angleX'] ?? 0;
    /** 绕y轴旋转角度 */
    $angleY = $config['angleY'] ?? 0;
    /** 绕z轴旋转角度 */
    $angleZ = $config['angleZ'] ?? 0;
    /** 图形相对于终端尺寸缩放比例 */
    $scale = $config['scale'] ?? (min($width, $height) / 8);
    /** 二维平面x轴方向偏移量 */
    $distanceX = $config['distanceX'] ?? 1;
    /** 二维平面y轴方向偏移量 */
    $distanceY = $config['distanceY'] ?? 1;
    /** 三维图像构图关键点 */
    $vertices = $config['vertices'] ?? [];
    /** 三维图形绘图路径 */
    $edges = $config['edges'] ?? [];
    /** 如果没有涂层，则需要创建新的图层 */
    if (empty($canvas)) {
        $canvas = array_fill(0, $height, array_fill(0, $width, ' '));
    }

    // 旋转矩阵和坐标转换（与立方体相同）
    $cosX = cos($angleX);
    $sinX = sin($angleX);
    $cosY = cos($angleY);
    $sinY = sin($angleY);
    $cosZ = cos($angleZ);
    $sinZ = sin($angleZ);

    /** 这里是将三维图像的顶点投影到二维平面 */
    $rotatedVertices = [];
    foreach ($vertices as $vertex) {
        $x = $vertex[0];
        $y = $vertex[1];
        $z = $vertex[2];

        $xz = $x * $cosZ - $y * $sinZ;
        $y = $x * $sinZ + $y * $cosZ;
        $x = $xz;

        $yz = $y * $cosX - $z * $sinX;
        $z = $y * $sinX + $z * $cosX;
        $y = $yz;

        $xz = $x * $cosY - $z * $sinY;
        $z = $x * $sinY + $z * $cosY;
        $x = $xz;

        $x *= $scale;
        $y *= $scale;
        /** 只取被位移后的x和y坐标 ，就完成了三维到二维的转换 */
        $rotatedVertices[] = [$x * 2 + $distanceX + $width / 2, $y + $distanceY + $height / 2];
    }

    /** 根据配置使用两个端点绘制三维图像的边 */
    foreach ($edges as $edge) {
        $x1 = (int)$rotatedVertices[$edge[0]][0];
        $y1 = (int)$rotatedVertices[$edge[0]][1];
        $x2 = (int)$rotatedVertices[$edge[1]][0];
        $y2 = (int)$rotatedVertices[$edge[1]][1];
        drawLine($canvas, $x1, $y1, $x2, $y2);
    }

    return $canvas;
}

/**------------------------------------------3D-------------------------------------------------------------*/
/** 获取终端的宽度和高度 */
list($width, $height) = getTerminalSize(); //
/** 缩放因子，使立方体适应终端 */
$scale = min($width, $height) / 8; //

/** 三维坐标的旋转参数 */
$angleX = 0; // 初始X轴旋转角度
$angleY = 0; // 初始Y轴旋转角度
$angleZ = 0; // 初始Z轴旋转角度
/** 通过控制三个角速度达到控制旋转方向 */
$angleStepX = 0.01; // 每次更新角度的步长
$angleStepY = 0.1; // 每次更新角度的步长
$angleStepZ = 0.01; // 每次更新角度的步长

/** x方向位移量 */
$distanceX = 0;

/** x方向移动方向，默认向右 */
$directionX = 1;

/** 纵向实现上下快速跳动 */
$distanceY = 0;
/** 默认向上跳动 */
$directionY = 1;

/**-----------------------------------------------------2D-----------------------------------------------------------*/

/** 圆心 */
$centerX = round($width / 2); // 几何中心X
$centerY = round($height / 2); // 几何中心Y
/** 每次刷新页面只生成一个星星 */
$numStars = 1; // 每一帧生成的星星数量
/** 最大页面同时存在10个星星 */
$maxStars = 100; // 最大星星数量

/** 流星尾巴长度 因为一个色系的长度是6所以最长设置为6 */
$trailLength = 6; // 轨迹长度（星星的单位）
/** 是否流线型，确定了尾巴是否紧紧的跟随流星 */
$isWaterLine = true;//是否流线型运动
/** 横向和纵向的修正系数 就是cli模式下字符宽度和高度的比值 宽度：高度，若取值1 ，则为纵向的椭圆 ，经过测试2.1是最理想的状态 */
$rateForWithAndHeight = 2.1; # 2.1

/** 计算固定区域的起始位置以居中显示 */
$startX =  $width / 2;
$startY =  $height / 2;
/** 轨迹画布，存储轨迹 */
$trail = array_fill(0, $height, array_fill(0, $width, []));
/** 存储星星的数组 */
$stars = [];



while (true) {
    /** 清屏并移除历史记录 */
    echo "\033[H\033[J";
    /** 隐藏光标 */
    echo "\033[?25l";
    /**----------------------------------渲染3D图像开始---------------------------------------------------------------*/
    /** 金字塔配置 */
    $config = [
        'width' => $width,
        'height' => $height,
        'angleX' => $angleX,
        'angleY' => $angleY,
        'angleZ' => $angleZ,
        'scale' => $scale,
        'distanceX' => $distanceX,
        'distanceY' => $distanceY,
        'vertices' => [
            [-1, -1, -1],
            [1, -1, -1],
            [1, 1, -1],
            [-1, 1, -1],
            [0, 0, 1]
        ],
        'edges' => [
            [0, 1], [1, 2], [2, 3], [3, 0], [0, 4], [1, 4], [2, 4], [3, 4]
        ],
    ];
    /** 绘制金字塔 */
    $canvas = computeCoordinateFor3D($config);

    /** 立方体 */
    $config2 = [
        'width' => $width,
        'height' => $height,
        'angleX' => $angleX,
        'angleY' => $angleY,
        'angleZ' => $angleZ,
        'scale' => $scale,
        'distanceX' => -$distanceX - 1,
        'distanceY' => -$distanceY - 1,
        'vertices' => [
            [-1, -1, -1],
            [1, -1, -1],
            [1, 1, -1],
            [-1, 1, -1],
            [-1, -1, 1],
            [1, -1, 1],
            [1, 1, 1],
            [-1, 1, 1]
        ],
        'edges' => [[0, 1], [1, 2], [2, 3], [3, 0], [4, 5], [5, 6], [6, 7], [7, 4], [0, 4], [1, 5], [2, 6], [3, 7]],
    ];
    /** 将立方体动画叠加到金字塔图层上 */
    $canvas = computeCoordinateFor3D($config2, $canvas);

    /**-------------------渲染3D动画结束------------------------------------------------------------------------------*/

    /**-------------------渲染2D图像开始------------------------------------------------------------------------------*/
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
        if ($star['angle'] >= 360) {
            $star['angle'] = $star['angle'] % 360;
        }
        /** x 坐标 = 圆心x坐标 + 半径 x 角度的余弦 需要校正x方向坐标 */
        $x = $centerX + (int)($star['radius'] * $rateForWithAndHeight * cos($star['angle']));
        /** y 坐标 = 圆心y坐标 + 半径 x 角度的正弦 */
        $y = $centerY + (int)($star['radius'] * sin($star['angle']));

        /** 在坐标系中，分成四个象限 */
        /** 确保星星位置在画布内 */
        if ($x >= 0 && $x < $width && $y >= 0 && $y < $height) {
            // 更新轨迹
            for ($i = 0; $i < $trailLength; $i++) {
                /** 尾巴总是离圆心更近一些，越是后面的尾巴，离圆心越近 */
                /** 尾巴的x坐标 = 圆心点x的坐标 + （头部的半径 - 尾巴的长度） x 圆角的余弦 需要校正x方向坐标 */
                $trailX = $centerX + (int)(($star['radius'] - $i * $star['speed']) * $rateForWithAndHeight * cos($star['angle']));
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
    /**-------------------------------------------渲染2D动画结束----------------------------------------------------------*/


    /** 渲染页面 */
    foreach ($canvas as $line) {
        echo implode('', $line) . PHP_EOL; // 输出画布内容
    }
    /** 实现水平跳动 */
    /** 向右移动 */
    if ($directionX == 1) {
        $distanceX++;
    }
    /** 向左移动 */
    if ($directionX == -1) {
        $distanceX--;
    }
    /** 即将超过右边界，更换方向，向左移动 */
    if ($distanceX >= ($width / 2)) {
        $directionX = -1;
    }
    /** 即将超过左边界，更换方向，向右移动 */
    if ($distanceX <= (-$width / 2)) {
        $directionX = 1;
    }

    /** 实现纵向的跳动 */

    /** 向右移动 */
    if ($directionY == 1) {
        $distanceY += 1;
    }
    /** 向左移动 */
    if ($directionY == -1) {
        $distanceY -= 1;
    }
    /** 即将超过右边界，更换方向，向左移动 */
    if ($distanceY >= ($height / 2)) {
        $directionY = -1;
    }
    /** 即将超过左边界，更换方向，向右移动 */
    if ($distanceY <= (-$height / 2)) {
        $directionY = 1;
    }

    /** 实现立方体的旋转 */
    $angleX += $angleStepX; // 更新X轴角度
    $angleY += $angleStepY; // 更新Y轴角度
    $angleZ += $angleStepZ; // 更新Z轴角度
    if ($angleX >= 2 * M_PI) $angleX -= 2 * M_PI; // 保持X轴角度在0到2π之间
    if ($angleY >= 2 * M_PI) $angleY -= 2 * M_PI; // 保持Y轴角度在0到2π之间
    if ($angleZ >= 2 * M_PI) $angleZ -= 2 * M_PI; // 保持Z轴角度在0到2π之间
    /** 这个时间是看着最流畅的 */
    usleep(10000); // 100ms 延时
}


