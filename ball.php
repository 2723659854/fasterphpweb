<?php

// 获取控制台的宽度和高度
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
        $height = isset($heightMatch[1]) ? (int)$heightMatch[1] : 25; // 默认值
    } else {
        // Linux 系统
        $size = [];
        if (preg_match('/(\d+)x(\d+)/', shell_exec('stty size'), $size)) {
            $width = $size[1];
            $height = $size[2];
        } else {
            $width = 80; // 默认值
            $height = 25; // 默认值
        }
    }

    return [$width, $height];
}

// 设置半径
$radius = 12;

// 获取控制台的宽度和高度
list($width, $height) = getTerminalSize();
/** 计算固定区域的起始位置以居中显示 */
$startX =  ($width-2*$radius) / 2;
$startY =  ($height-2*$radius) / 2;
// 定义圆形的中心点
$centerX = $radius * 2 + $startX;
$centerY = $radius + $startY;

// 创建一个二维数组来表示图形
$canvas = array_fill(0, $height, str_repeat(' ', $width));

// 绘制圆形
for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        // 计算每个点到圆心的距离
        $distance = sqrt(pow(($x - $centerX) / 2, 2) + pow($y - $centerY, 2));

        // 如果距离接近半径，就绘制字符
        if (abs($distance - $radius) < 0.5) {
            $canvas[$y][$x] = '*';
        }
    }
}

// 输出结果
foreach ($canvas as $line) {
    echo $line . PHP_EOL;
}

?>
