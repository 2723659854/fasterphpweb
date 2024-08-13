<?php

// ANSI 转义码定义
define('RESET', "\033[0m");

// 函数：获取颜色渐变色
function getColorGradient($baseColor, $level) {
    $start = $baseColor; // 起始颜色代码
    $end = $baseColor - 6; // 结束颜色代码
    $range = $start - $end;
    $step = $range / 10;
    $colorCode = (int)($start - $step * $level);
    return "\033[38;5;{$colorCode}m";
}

// 渐变显示
function displayColorGradient($baseColor, $label) {
    for ($i = 0; $i <= 10; $i++) {
        echo getColorGradient($baseColor, $i) . $label . ($i * 10) . RESET . PHP_EOL;
        usleep(500000); // 0.5秒
    }
}
$color = <<<eof
红色：从 196 到 160
橙色：从 208 到 202
黄色：从 226 到 220
绿色：从 28 到 22
青色：从 51 到 24
蓝色：从 75 到 19
紫色：从 129 到 53
eof;

// 渐变颜色显示
echo "红色渐变:\n";
displayColorGradient(196, "红色 ");

echo "橙色渐变:\n";
displayColorGradient(208, "橙色 ");

echo "黄色渐变:\n";
displayColorGradient(226, "黄色 ");

echo "绿色渐变:\n";
displayColorGradient(28, "绿色 ");

echo "青色渐变:\n";
displayColorGradient(51, "青色 ");

echo "蓝色渐变:\n";
displayColorGradient(75, "蓝色 ");

echo "紫色渐变:\n";
displayColorGradient(129, "紫色 ");
