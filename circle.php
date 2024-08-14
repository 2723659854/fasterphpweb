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

        $width = isset($widthMatch[1]) ? (int)$widthMatch[1] : 60; // 默认值
        $height = isset($heightMatch[1]) ? (int)$heightMatch[1] : 40; // 默认值
    } else {
        // Linux 系统
        $size = [];
        if (preg_match('/(\d+)x(\d+)/', shell_exec('stty size'), $size)) {
            $width = $size[1];
            $height = $size[2];
        } else {
            $width = 60; // 默认值
            $height = 40; // 默认值
        }
    }

    return [ $width, $height];
}
// 清屏
function clearScreen()
{
    echo "\033[H\033[J";
}

// 绘制字符
function drawFirework($x, $y, $char, $color)
{
    $color = getRandomColor();

    echo "\033[{$y};{$x}H\033[38;5;{$color}m{$char}\033[0m";
}

// 绘制散开效果
function drawExplosion($x, $y, $width, $height, $char, $color, $maxExplosionRadius)
{
    $numSparks = 20;
    for ($i = 0; $i < $numSparks; $i++) {
        $explosionX = ($x + rand(-$maxExplosionRadius, $maxExplosionRadius));
        $explosionY = $y + rand(-$maxExplosionRadius, $maxExplosionRadius);
        // 确保爆炸点在画布范围内
        if ($explosionX >= 1 && $explosionX <= $width && $explosionY >= 1 && $explosionY <= $height) {
            drawFirework($explosionX, $explosionY, $char, $color);
        }
    }
}

// 随机生成烟花发射信息
function createFireworks($numFireworks, $width, $height, $minExplodeTime, $maxExplodeTime, $minLaunchSpeed, $maxLaunchSpeed)
{
    $fireworks = [];
    foreach (range(0, $numFireworks - 1) as $i) {
        $fireworks[] = [
            'x' => rand(1, $width),
            'y' => $height,
            'color' => getRandomColor(),
            'explodeTime' => rand($minExplodeTime, $maxExplodeTime), // 随机的爆炸时间
            'startTime' => microtime(true), // 发射时间
            'speed' => rand($minLaunchSpeed, $maxLaunchSpeed), // 随机升空速度
            'exploded' => false,
            'explosionTime' => 0 // 时间记录烟花散开的时间
        ];
    }
    return $fireworks;
}

// 获取随机颜色
function getRandomColor()
{
    return rand(16, 231); // ANSI 256 色的范围
}

// 主循环
list($width, $height) = getTerminalSize();
$numFireworks = 5; // 烟花数量
$fireworkChar = '*'; // 烟花的字符
$delay = 50000; // 飞行速度（微秒）
$maxExplosionRadius = 5; // 最大爆炸半径
$explosionStayTime = 1; // 烟花散开后停留的时间（秒）
$minLaunchSpeed = 1; // 最小升空速度
$maxLaunchSpeed = 3; // 最大升空速度
$minExplodeTime = 500000; // 最小爆炸时间（微秒）
$maxExplodeTime = 1000000; // 最大爆炸时间（微秒）

$fireworks = createFireworks($numFireworks, $width, $height, $minExplodeTime, $maxExplodeTime, $minLaunchSpeed, $maxLaunchSpeed);

while (true) {
    /** 清屏并移除历史记录 */
    clearScreen();
    /** 隐藏光标 */
    echo "\033[?25l";

    $currentTime = microtime(true);
    $allExploded = true;

    foreach ($fireworks as &$firework) {
        $elapsedTime = ($currentTime - $firework['startTime']) * 1000000; // 转换为微秒

        if (!$firework['exploded']) {
            if ($elapsedTime < $firework['explodeTime']) {
                // 飞行中
                drawFirework($firework['x'], $firework['y'], $fireworkChar, $firework['color']);
                $firework['y'] -= $firework['speed'];
                // 确保烟花在画布中飞行，不超出边界
                if ($firework['y'] < $height / 2) {
                    // 让烟花在高度的一半以上散开
                    if ($firework['y'] < 0) {
                        $firework['y'] = 0;
                    }
                }
                $allExploded = false; // 还没有全部爆炸
            } else {
                // 爆炸
                drawExplosion($firework['x'], $firework['y'], $width, $height, $fireworkChar, $firework['color'], $maxExplosionRadius);
                $firework['exploded'] = true;
                $firework['explosionTime'] = $currentTime;
            }
        } else {
            // 烟花散开后的停留时间
            if (($currentTime - $firework['explosionTime']) < $explosionStayTime) {
                // 确保烟花继续显示
                drawExplosion($firework['x'], $firework['y'], $width, $height, $fireworkChar, $firework['color'], $maxExplosionRadius);
                $allExploded = false; // 还有烟花在停留
            } else {
                $firework['y'] = -1; // 使烟花不再出现
            }
        }
    }

    // 生成新烟花
    if ($allExploded) {
        $fireworks = createFireworks($numFireworks, $width, $height, $minExplodeTime, $maxExplodeTime, $minLaunchSpeed, $maxLaunchSpeed);
    }

    usleep($delay); // 控制烟花飞行速度
}
?>
