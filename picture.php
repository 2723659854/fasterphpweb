<?php

$image = "data:image/png;base64,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";
$imageName = "25220_" . date("His", time()) . "_" . rand(1111, 9999) . '.png';
if (strstr($image, ",")) {
    $image = explode(',', $image);
    $image = $image[1];

}
$path = "tmp/signImage/" . date("Ymd", time());
if (!is_dir($path)) { //判断目录是否存在 不存在就创建

    mkdir($path, 0777, true);

}
$imageSrc = $path . "/" . $imageName;  //图片名字


$r = file_put_contents(ROOT_PATH . "public/" . $imageSrc, base64_decode($image));//返回的是字节数

if (!$r) {
    return json(['data' => null, "code" => 1, "msg" => "图片生成失败"]);

} else {
    return json(['data' => 1, "code" => 0, "msg" => "图片生成成功"]);

}
