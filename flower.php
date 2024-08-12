<?php
// 画布的尺寸
$width = 120; // 矩形区域的宽度
$height = 40; // 矩形区域的高度
$centerX = $width / 2; // 几何中心X
$centerY = $height / 2; // 几何中心Y
$numStars = 5; // 每一帧生成的星星数量
$maxStars = 200; // 最大星星数量
$delay = 0.05; // 延迟（秒）

// 存储星星的数组
$stars = [];

while (true) {
    // 清空屏幕
    system('cls'); // 在Windows系统上使用 'cls'

    // 创建一个二维数组表示画布
    $canvas = array_fill(0, $height, array_fill(0, $width, ' '));

    // 每一帧生成新的星星（只在最大星星数量内）
    if (count($stars) < $maxStars) {
        for ($i = 0; $i < $numStars; $i++) {
            $stars[] = [
                'angle' => mt_rand(0, 360) * M_PI / 180, // 随机初始角度，转换为弧度
                'radius' => 0, // 从中心开始
                'speed' => 0.1 + 0.1 * mt_rand(0, 10), // 随机速度，保证每个星星速度不同
                'angleSpeed' => 0.05 * mt_rand(1, 3) // 随机角速度
            ];
        }
    }

    // 更新每个星星的位置
    foreach ($stars as &$star) {
        $star['radius'] += $star['speed']; // 半径增加，模拟向外运动
        $star['angle'] += $star['angleSpeed']; // 角度增加，模拟旋转

        $x = $centerX + (int)($star['radius'] * cos($star['angle']));
        $y = $centerY + (int)($star['radius'] * sin($star['angle']));

        // 确保星星位置在画布内
        if ($x >= 0 && $x < $width && $y >= 0 && $y < $height) {
            $canvas[$y][$x] = '*';
        }
    }

    // 删除超出画布的星星
    $stars = array_filter($stars, function($star) use ($width, $height,$centerX,$centerY) {
        $x = $centerX + (int)($star['radius'] * cos($star['angle']));
        $y = $centerY + (int)($star['radius'] * sin($star['angle']));
        return $x >= 0 && $x < $width && $y >= 0 && $y < $height;
    });

    // 输出画布
    foreach ($canvas as $line) {
        echo implode('', $line) . PHP_EOL;
    }

    // 等待一段时间
    usleep($delay * 1000000); // usleep的单位是微秒
}
