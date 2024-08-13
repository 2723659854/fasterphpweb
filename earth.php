<?php

// 画布尺寸
$width = 80; // 画布宽度
$height = 24; // 画布高度
$delay = 100000; // 延迟（微秒）

// 初始化雨滴
function createRaindrop() {
    return [
        'x' => mt_rand(0, $GLOBALS['width'] - 1),
        'y' => 0,
        'length' => mt_rand(1, 5) // 雨滴长度
    ];
}

// 生成初始雨滴
function generateRaindrops($count) {
    $raindrops = [];
    for ($i = 0; $i < $count; $i++) {
        $raindrops[] = createRaindrop();
    }
    return $raindrops;
}

// 绘制画布
function drawCanvas($raindrops) {
    global $width, $height;
    $canvas = array_fill(0, $height, array_fill(0, $width, ' '));

    // 绘制雨滴
    foreach ($raindrops as $drop) {
        $x = $drop['x'];
        $y = $drop['y'];
        $length = $drop['length'];
        for ($i = 0; $i < $length; $i++) {
            if ($y + $i < $height) {
                $canvas[$y + $i][$x] = '|';
            }
        }
    }

    // 输出画布
    echo "\033[H"; // 将光标移动到屏幕左上角
    foreach ($canvas as $line) {
        echo implode('', $line) . PHP_EOL;
    }
}

// 主循环
$raindrops = generateRaindrops(100); // 生成初始的雨滴
while (true) {
    // 更新雨滴位置
    foreach ($raindrops as &$drop) {
        $drop['y'] += 1;
        if ($drop['y'] >= $height) {
            $drop = createRaindrop(); // 重置超出画布的雨滴
        }
    }

    // 清屏
    echo "\033[H\033[J"; // 清屏

    // 绘制画布
    drawCanvas($raindrops);

    // 等待一段时间
    usleep($delay);
}
