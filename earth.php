<?php


$width = 40; // 控制台宽度
$height = 20; // 控制台高度
$delay = 0.1; // 动画帧之间的延迟（秒）
$radius = min($width, $height) / 3; // 地球仪半径

function clearScreen()
{
    echo "\033[H\033[J";
}

function drawEarth($angle)
{
    global $width, $height, $radius;

    $centerX = $width / 2;
    $centerY = $height / 2;

    $canvas = array_fill(0, $height, array_fill(0, $width, ' '));

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $dx = $x - $centerX;
            $dy = $y - $centerY;
            $distance = sqrt($dx * $dx + $dy * $dy);

            if ($distance < $radius) {
                $angleOffset = atan2($dy, $dx) - $angle;
                $dist = $radius * cos($angleOffset);
                $projX = (int)($centerX + $dist * cos($angleOffset));
                $projY = (int)($centerY + $dist * sin($angleOffset));

                if ($projX >= 0 && $projX < $width && $projY >= 0 && $projY < $height) {
                    $canvas[$projY][$projX] = '*';
                }
            }
        }
    }

    return $canvas;
}

function printCanvas($canvas)
{
    foreach ($canvas as $line) {
        echo implode('', $line) . PHP_EOL;
    }
}

while (true) {
    for ($angle = 0; $angle < 360; $angle += 10) {
        clearScreen();
        $canvas = drawEarth(deg2rad($angle));
        printCanvas($canvas);
        usleep($delay * 1000000); // usleep 的单位是微秒
    }
}
