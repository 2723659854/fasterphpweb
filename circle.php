<?php

// 设置半径
$radius = 10;

// 定义圆形的中心点
$centerX = $radius * 2;
$centerY = $radius;

// 创建一个二维数组来表示图形
$width = $height = $radius * 4 + 1; // 这里将宽度调整为更大，以适应调整后的圆
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
