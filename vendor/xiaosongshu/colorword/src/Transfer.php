<?php

namespace Xiaosongshu\Colorword;

/**
 * @purpose php在cli模式下输出彩色字体
 */
class Transfer{

    /** 字体颜色 */
    private $foreground_colors = [];

    /** 背景色 */
    private $background_colors = [];

    /**
     * 初始化
     */
    public function __construct()
    {
        /** 字体颜色 30-39*/
        /** 30:黑 31:红 32:绿 33:黄 34:蓝色 35:紫色 36:深绿 37:白色 */
        $this->foreground_colors['black'] = '0;30';# 黑色
        $this->foreground_colors['dark_gray'] = '1;30';# 深黑
        $this->foreground_colors['blue'] = '0;34';# 深蓝色
        $this->foreground_colors['light_blue'] = '1;34';# 亮深蓝色
        $this->foreground_colors['green'] = '0;32';#绿色
        $this->foreground_colors['light_green'] = '1;32';#亮绿色
        $this->foreground_colors['cyan'] = '0;36';# 蓝色
        $this->foreground_colors['light_cyan'] = '1;36';# 亮蓝色
        $this->foreground_colors['red'] = '0;31';# 红色
        $this->foreground_colors['light_red'] = '1;31';# 亮红色
        $this->foreground_colors['purple'] = '0;35';# 紫色
        $this->foreground_colors['light_purple'] = '1;35';#亮紫色
        $this->foreground_colors['brown'] = '0;33';# 暗黄色
        $this->foreground_colors['yellow'] = '1;33';# 亮黄色
        $this->foreground_colors['light_gray'] = '0;37';#亮灰色
        $this->foreground_colors['white'] = '1;37';#白色

        /** 背景色 40-49 其中48,49看不到颜色 */
        /** 40:黑 41:深红 42:绿 43:黄色 44:蓝色  45:紫色 46:深绿 47:白色 */
        $this->background_colors['black'] = '40';# 黑色
        $this->background_colors['red'] = '41';# 红色
        $this->background_colors['green'] = '42';# 绿色
        $this->background_colors['yellow'] = '43';# 黄色
        $this->background_colors['blue'] = '44';# 蓝色
        $this->background_colors['magenta'] = '45'; #品红
        $this->background_colors['cyan'] = '46'; # 青色
        $this->background_colors['light_gray'] = '47'; # 浅灰
    }

    /**
     * 设置字体颜色，背景颜色
     * @param string $string 需要输出的文字
     * @param string $foreground_color 字体颜色
     * @param string $background_color 背景颜色
     * @return string
     * @note 引号内\033用于引导非常规字符序列，在这里的作用就是引导设置输出属性，后边的[32m就是将前景色设置为绿色，字母m表示设置的属性类别，数字代表属性值。0m设置项用于恢复默认值
     * @note 系统不同，输出的颜色也不同
     */
    public function getColorString(string $string, string $foreground_color = null, string $background_color = null) {

        $colored_string = "";
        if (isset($this->foreground_colors[$foreground_color])) {
            //\033[ 表示设置属性 32表示绿色 m表示设置前景色
            $colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
        }

        if (isset($this->background_colors[$background_color])) {
            $colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
        }

        $colored_string .= $string . "\033[0m";//0m设置项用于恢复默认值
        return $colored_string;
    }

    /**
     * 提示信息
     * @param string $string 文字内容
     * @return string 返回值
     */
    public function info(string $string){
        return $this->getColorString($string,'green');
    }

    /**
     * 错误提示
     * @param string $string
     * @return string
     */
    public function error(string $string){
        return $this->getColorString($string,'red');
    }

    /**
     * 普通消息
     * @param string $string
     * @return string
     */
    public function line(string $string){
        return $this->getColorString($string,'white');
    }

}