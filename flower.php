<?php
// 画布的尺寸
$width = 40; // 画布宽度
$height = 20; // 画布高度
$centerX = $width / 2; // 几何中心X
$centerY = $height / 2; // 几何中心Y
$numStars = 5; // 每一帧生成的星星数量
$maxStars = 100; // 最大星星数量
$delay = 0.05; // 延迟（秒）
$trailLength = 4; // 轨迹长度（星星的单位）

// 获取终端宽度和高度
function getTerminalSize()
{
    if (PHP_OS_FAMILY === 'Windows') {
        // Windows 系统
        $cmd = 'mode con'; // Windows 命令行获取控制台尺寸
        $output = shell_exec($cmd);

        // 解析控制台尺寸
        preg_match('/Columns:\s*(\d+)/', $output, $widthMatch);
        preg_match('/Lines:\s*(\d+)/', $output, $heightMatch);

        $width = isset($widthMatch[1]) ? (int)$widthMatch[1] : 80; // 默认值
        $height = isset($heightMatch[1]) ? (int)$heightMatch[1] : 24; // 默认值
    } else {
        // Linux 系统
        $size = [];
        if (preg_match('/(\d+)x(\d+)/', shell_exec('stty size'), $size)) {
            $width = $size[1];
            $height = $size[2];
        } else {
            $width = 80; // 默认值
            $height = 24; // 默认值
        }
    }

    return ['width' => $width, 'height' => $height];
}

$terminalSize = getTerminalSize();
$termWidth = $terminalSize['width'];
$termHeight = $terminalSize['height'];

// 计算固定区域的起始位置以居中显示
$startX = ($termWidth - $width) / 2;
$startY = ($termHeight - $height) / 2;

// 存储星星的数组
$stars = [];
$canvas = array_fill(0, $height, array_fill(0, $width, ' '));
$trail = array_fill(0, $height, array_fill(0, $width, [])); // 轨迹画布，存储轨迹

// 生成随机颜色（256色模式）
function getRandomColor()
{
    return strval(mt_rand(0, 255)); // 生成0-255之间的随机色
}

// 生成渐变颜色
function getFadedColor($baseColor, $fadeLevel)
{
    $fadeAmount = min($fadeLevel * 10, 255); // 调整颜色渐变幅度
    $fadedColor = max(0, intval($baseColor) - $fadeAmount);
    return strval($fadedColor);
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
            'color' => getRandomColor() // 随机颜色
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

// 保存光标位置
function saveCursorPosition()
{
    echo "\033[s";
}

// 恢复光标位置
function restoreCursorPosition()
{
    echo "\033[u";
}

while (true) {
//    // 清屏并移除历史记录
//    echo "\033[2J\033[H";
//    // 隐藏光标
//    echo "\033[?25l";

    // 清屏并移除历史记录
    echo "\033[H\033[J";
    // 隐藏光标
    echo "\033[?25l";
    // 保存光标位置
    saveCursorPosition();

    // 每一帧生成新的星星（只在最大星星数量内）
    if (count($stars) < $maxStars) {
        $stars = array_merge($stars, generateStars($numStars));
    }

    // 更新每个星星的位置
    $newStars = []; // 临时存储有效的星星
    foreach ($stars as &$star) {
        $star['radius'] += $star['speed']; // 半径增加，模拟向外运动
        $star['angle'] += $star['angleSpeed']; // 角度增加，模拟旋转
        $x = $centerX + (int)($star['radius'] * cos($star['angle']));
        $y = $centerY + (int)($star['radius'] * sin($star['angle']));

        // 确保星星位置在画布内
        if ($x >= 0 && $x < $width && $y >= 0 && $y < $height) {
            // 更新轨迹
            for ($i = 0; $i < $trailLength; $i++) {
                $trailX = $centerX + (int)(($star['radius'] - $i * $star['speed']) * cos($star['angle']));
                $trailY = $centerY + (int)(($star['radius'] - $i * $star['speed']) * sin($star['angle']));
                if ($trailX >= 0 && $trailX < $width && $trailY >= 0 && $trailY < $height) {
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
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            if (!empty($trail[$y][$x])) {
                // 使用最后的颜色显示轨迹
                $lastColor = end($trail[$y][$x]);
                $canvas[$y][$x] = "\033[38;5;{$lastColor}m*\033[0m";
                // 清理过时的颜色
                $trail[$y][$x] = array_slice($trail[$y][$x], 1);
            }
        }
    }

    // 恢复光标位置
    restoreCursorPosition();

    // 输出画布
    for ($y = 0; $y < $height; $y++) {
        echo str_repeat(' ', (int)$startX); // 打印前导空格以居中
        echo implode('', $canvas[$y]) . PHP_EOL;
    }

    // 等待一段时间
    usleep($delay * 200000); // usleep的单位是微秒
}
