<?php
// 画布的尺寸
$size = 40; // 正方形区域的边长
$centerX = $size / 2; // 几何中心X
$centerY = $size / 2; // 几何中心Y
$numStars = 5; // 每一帧生成的星星数量
$maxStars = 100; // 最大星星数量
$delay = 0.05; // 延迟（秒）
$trailLength = 2; // 轨迹长度（星星的单位）

// 获取终端宽度和高度
function getTerminalSize()
{
    $size = [];
    if (preg_match('/(\d+)x(\d+)/', shell_exec('stty size'), $size)) {
        return ['width' => $size[1], 'height' => $size[2]];
    }
    return ['width' => 80, 'height' => 24]; // 默认值
}

$terminalSize = getTerminalSize();
/** 修正顯示區域的寬度 */
$termWidth = $terminalSize['width'] + 50;
$termHeight = $terminalSize['height'];

// 计算画布的起始位置以居中显示
$startX = ($termWidth - $size) / 2;
$startY = ($termHeight - $size) / 2;

// 存储星星的数组
$stars = [];
$canvas = array_fill(0, $size, array_fill(0, $size, ' '));
$trail = array_fill(0, $size, array_fill(0, $size, [])); // 轨迹画布，存储轨迹

// 生成随机高亮颜色
function getRandomColor()
{
    $colors = [
        '91', // 红色高亮
        '92', // 绿色高亮
        '93', // 黄色高亮
        '94', // 蓝色高亮
        '95', // 紫色高亮
        '96', // 青色高亮
        '97'  // 白色高亮
    ];
    return $colors[array_rand($colors)];
}

// 生成渐变颜色
function getFadedColor($baseColor, $fadeLevel)
{
    $colors = [
        '91', // 红色高亮
        '92', // 绿色高亮
        '93', // 黄色高亮
        '94', // 蓝色高亮
        '95', // 紫色高亮
        '96', // 青色高亮
        '97'  // 白色高亮
    ];
    $baseIndex = array_search($baseColor, $colors);
    if ($baseIndex === false) {
        $baseIndex = 0; // 默认为红色高亮
    }
    $fadedColorIndex = min($baseIndex + $fadeLevel, count($colors) - 1);
    return $colors[$fadedColorIndex];
}


// 生成新的星星
function generateStars($numStars)
{
    $stars = [];
    for ($i = 0; $i < $numStars; $i++) {
        $stars[] = [
            'angle' => mt_rand(0, 360) * M_PI / 180, // 随机初始角度，转换为弧度
            'radius' => 0, // 从中心开始
            'speed' => 0.1 + 0.1 * mt_rand(0, 5), // 调整星星速度
            'angleSpeed' => 0.05 * mt_rand(1, 3), // 调整角速度
            'color' => getRandomColor() // 随机高亮颜色
        ];
    }
    return $stars;
}

// 清除画布中的内容
function clearCanvas(&$canvas)
{
    foreach ($canvas as $y => &$line) {
        foreach ($line as $x => &$pixel) {
            $pixel = ' ';
        }
    }
}

while (true) {
    /** 清屏并移除历史记录 */
    echo "\033[2J\033[H";
    /** 隐藏光标 */
    echo "\033[?25l";

    // 每一帧生成新的星星（只在最大星星数量内）
    if (count($stars) < $maxStars) {
        $stars = array_merge($stars, generateStars($numStars));
    }

    // 更新每个星星的位置
    $newStars = []; // 临时存储有效的星星
    foreach ($stars as &$star) {
        $star['radius'] += $star['speed']; // 半径增加，模拟向外运动
        $star['angle'] += $star['angleSpeed']; // 角度增加，模拟旋转
        /** 使用三角函數正弦和余弦函数计算坐标 */
        $x = $centerX + (int)($star['radius'] * cos($star['angle']));
        $y = $centerY + (int)($star['radius'] * sin($star['angle']));

        // 确保星星位置在画布内
        if ($x >= 0 && $x < $size && $y >= 0 && $y < $size) {
            // 更新轨迹
            for ($i = 0; $i < $trailLength; $i++) {
                $trailX = $centerX + (int)(($star['radius'] - $i * $star['speed']) * cos($star['angle']));
                $trailY = $centerY + (int)(($star['radius'] - $i * $star['speed']) * sin($star['angle']));
                if ($trailX >= 0 && $trailX < $size && $trailY >= 0 && $trailY < $size) {
                    // 添加颜色到轨迹，并保证第二个星星颜色较暗
                    $trail[$trailY][$trailX][] = getFadedColor($star['color'], $i);
                }
            }
            // 记录有效的星星
            $newStars[] = $star;
        }
    }

    // 更新星星数组
    $stars = $newStars;

    // 清除画布并绘制新的内容
    clearCanvas($canvas);

    // 绘制轨迹
    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            if (!empty($trail[$y][$x])) {
                // 使用最后的颜色显示轨迹
                $lastColor = end($trail[$y][$x]);
                $canvas[$y][$x] = "\033[{$lastColor}m*\033[0m";
                // 清理过时的颜色
                $trail[$y][$x] = array_slice($trail[$y][$x], 1);
            }
        }
    }

    // 输出画布
    for ($y = 0; $y < $size; $y++) {
        echo str_repeat(' ', $startX); // 打印前导空格以居中
        echo implode('', $canvas[$y]) . PHP_EOL;
    }

    // 等待一段时间
    usleep($delay * 100000); // usleep的单位是微秒
}
