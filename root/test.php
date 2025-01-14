<?php

// 定义常见的静态文件扩展名数组
$static_extensions = [
    'html',
    'css',
    'js',
    'jpg',
    'png',
    'gif'
];

// 之前定义的相关变量和函数保持不变
$_key2 = [
    "REQUEST_URI",
    "SCRIPT_NAME",
    "SCRIPT_FILENAME",
    "PHP_SELF"
];

function clearServerScriptName()
{
    foreach ($_SERVER as $key => $value) {
        foreach (['index.php', 'admin.php','super.php'] as $name) {
            $value = str_replace($name, '', $value);
            $_SERVER[$key] = $value;
        }
    }
}

// 获取请求的文件路径（这里以REQUEST_URI为例，也可根据实际情况选用其他合适的变量）
$request_uri = $_SERVER['REQUEST_URI']?? '';

// 提取文件扩展名
$file_extension = pathinfo($request_uri, PATHINFO_EXTENSION);

//var_dump($_SERVER);

if ($_SERVER['SCRIPT_NAME'] == "/index.php") {
    clearServerScriptName();
    return include __DIR__. "/index.php";
} elseif ($_SERVER['SCRIPT_NAME'] == "/admin.php") {
    clearServerScriptName();
    return include __DIR__. "/admin.php";
} elseif ($_SERVER['SCRIPT_NAME'] == "/super.php") {
    clearServerScriptName();
    return include __DIR__. "/super.php";
} elseif (in_array($file_extension, $static_extensions)) {
    // 构建文件的完整路径，这里假设静态文件和PHP文件在同一目录下，可根据实际情况调整
    $file_path = __DIR__. $request_uri;

    if (file_exists($file_path)) {
        // 根据不同的文件扩展名设置相应的 Content-Type 头信息
        switch ($file_extension) {
            case 'html':
                header('Content-Type: text/html');
                break;
            case 'css':
                header('Content-Type: text/css');
                break;
            case 'js':
                header('Content-Type: application/javascript');
                break;
            case 'jpg':
                header('Content-Type: image/jpeg');
                break;
            case 'png':
                header('Content-Type: image/png');
                break;
            case 'gif':
                header('Content-Type: image/gif');
                break;
            default:
                header('Content-Type: application/octet-stream');
        }
        // 读取并输出文件内容
        readfile($file_path);
    } else {
        // 如果文件不存在，返回404错误
        http_response_code(404);
        echo "静态文件未找到";
    }
} else {
    header('Content-Type: text/html');
    $file_path = __DIR__. $request_uri.'/index.html';
    readfile($file_path);
}
/** 在实际网络通信中，需要考虑网络协议（如 TCP、UDP）的特点，如 TCP 的粘包和拆包问题 */

/**
 * 发送数据的时候打包
 * @param string $data
 * @param int $type
 * @return string
 */
function myPack(string $data, int $type = 0)
{
    return pack('NN', strlen($data), $type) . $data;
}

/**
 * 接受数据的时候解包
 * @param string $data
 * @return string
 */
function myUnpack(string $data)
{
    $header = unpack('Nlength/Ntype', substr($data, 0, 8));
    if ($header['type'] == 0){
        return substr($data, 8, $header['length']);
    }else{
        return '';
    }
}
/** 测试 */
$string = "hello world";
$encode = myPack($string, 0);
var_dump($encode);
$decode = myUnpack($encode);
var_dump($decode);

?>