<?php
require_once __DIR__ . '/function.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/Request.php';
function handle($url, $param, $_request)
{

    list($file, $class, $method) = explode('@', $url);
    $file = app_path() . $file;
    if (!file_exists($file)) {
        return dispay('index', ['msg' => $file . '文件不存在']);
    }
    require_once $file;
    if (!class_exists($class)) {
        return dispay('index', ['msg' => $class . '类不存在']);
    }
    $class = new $class;
    if (!method_exists($class, $method)) {
        return dispay('index', ['msg' => $method . '方法不存在']);
    }
    global $fuck;
    $fuck = new Root\Request();
    foreach ($_request as $k => $v) {
        $v = trim($v);
        if ($v) {
            $_pos = strripos($v, ": ");
            $key = substr($v, 0, $_pos);
            $value = substr($v, $_pos + 1, strlen($v));
            if ($key) {
                $fuck->header($key, $value);
            }
        }
    }
    foreach ($param as $k => $v) {
        $fuck->set($k, $v);
    }
    /** 这里必须捕获异常 */
    try {
        $response = $class->$method($fuck);
    }catch (Exception|RuntimeException $e){
        $fuck->_error=no_declear('index',['msg'=>"错误码：".$e->getCode()."<br>文件：".$e->getFile()."<br>行数：".$e->getLine().PHP_EOL."<br>错误详情：".$e->getMessage()]);
    }
    if ($fuck->_error) {

        return $fuck->_error;
    } else {
        return $response;
    }

}


function dispay($path, $param)
{
    $content = file_get_contents(app_path() . '/root/error/' . $path . '.html');
    if ($param) {
        $preg = '/{\$[\s\S]*?}/i';
        preg_match_all($preg, $content, $res);
        $array = $res['0'];
        $new_param = [];
        foreach ($param as $k => $v) {
            $key = '{$' . $k . '}';
            $new_param[$key] = $v;
        }
        foreach ($array as $k => $v) {
            if (isset($new_param[$v])) {
                $content = str_replace($v, $new_param[$v], $content);
            } else {
                throw new Exception("未定义的变量" . $v);
            }
        }
    }
    return $content;
}


