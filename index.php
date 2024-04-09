<?php

require_once __DIR__.'/vendor/autoload.php';
if (!function_exists('scan_dir')){
    /**
     * 扫描目录的文件
     * @param string $path 目录
     * @param bool $force 首次调用：true返回本目录的文件，false返回包含上一次的数据
     * @return array
     */
    function scan_dir(string $path = '.',bool $force=false)
    {
        /** 这里必须使用静态变量 ，否则递归调用的时候不能正确保存数据 */
        static $filePath    = [];
        if ($force){
            $filePath =[];
        }
        $current_dir = opendir($path);
        while (($file = readdir($current_dir)) !== false) {
            $sub_dir = $path . DIRECTORY_SEPARATOR . $file;
            if ($file == '.' || $file == '..') {
                continue;
            } else if (is_dir($sub_dir)) {
                scan_dir($sub_dir);
            } else {
                $filePath[$path . '/' . $file] = $path . '/' . $file;
            }
        }
        return $filePath;
    }
}

if (!function_exists("webpToJpg")){
    /**
     * webp图像转jpg
     * @param $fileName
     */
    function webpToJpg($fileName){
        /** 获取原来的图片的基本数据 */
        $path = pathinfo($fileName);
        $dirname = $path['dirname'];

        $newName = $path['filename'];
        $extension = $path['extension'];
        /** 如果是jpg格式，这个文件是错误的格式 */
        if ($extension=='jpg'){
            $newFileName = $dirname.'/'.$newName.'.webp';
            if (rename($fileName,$newFileName)){
                echo "修改后缀名称成功,打开新的图片{$fileName}\r\n";
                $fileName = $newFileName;
                /** 加载 WebP 文件 */

                    if($im = @imagecreatefromwebp($fileName)){
                        /** 以 100% 的质量转换成 jpeg 格式 */
                        $newdirname = str_replace('images','copy',$dirname);
                        if (!is_dir($newdirname)){
                            mkdir($newdirname,0777,true);
                        }
                        imagejpeg($im, $newdirname.'/'.$newName.'_1.jpg', 100);
                        /** 删除原来的图片 */
                        imagedestroy($im);
                        unlink($newFileName);
                        echo "转换完成{$fileName}\r\n";
                    }


            }
        }

    }
}

//$path = __DIR__.'/images';
//
///** 读取目录下面的所有文件 */
//$files = scan_dir($path);
//foreach ($files as $key=>$file){
//   webpToJpg($file);
//}

$memcache  = new Memcache();
$memcache ->connect('memcached',11211);
$memcache->set('name','zhangsan');
echo $memcache->get('name');

