<?php

namespace Xiaosongshu\Progress;

/**
 * @purpose 进度条
 * @author yanglong
 * @time 2024年12月25日15:50:02
 */
class ProgressBar
{
    /** 当前数据长度 */
    protected $length = 0;
    /** 是否已满100 */
    protected $flag = false;
    /** 数据总长度 */
    protected $all = 0;
    /** 默认字体颜色 30:黑 31:红 32:绿 33:黄 34:蓝色 35:紫色 36:深绿 37:白色 */
    protected $color = 'white';
    /** 字体颜色库 */
    protected $style = [
        'black' => 30,
        'red' => 31,
        'green' => 32,
        'yellow' => 33,
        'blue' => 34,
        'purple' => 35,
        'cyan' => 36,
        'white' => 37,
    ];

    /**
     * 设置数据总长度
     * @param int $number
     * @return void
     */
    public function createBar(int $number)
    {
        $this->all = $number;
    }


    /**
     * 设置字体颜色
     * @param string $color
     * @return void
     */
    public function setColor(string $color)
    {
        $this->color = $color;
    }


    /**
     * 进度条前进
     * @param int $number
     * @return void
     */
    public function advance(int $number = 1)
    {
        $this->length += $number;
        $progress = ($this->length / $this->all) * 100;
        /** 刷新进度条 */
        $this->showProgressBar((int)$progress);
    }

    /**
     * 输出进度条
     * @param int $percent 步长
     * @return void
     */
    protected function showProgressBar(int $percent)
    {
        /** 如果进度已满100，则不要再刷新进度条 */
        if ($this->flag) {
            return;
        }
        if ($percent >= 100) {
            $percent = 100;
        }
        echo "\033[?25l";//隐藏光标
        $process = "";
        for ($i = 1; $i <= $percent; $i++) {
            $process .= "=";
        }
        /** 输出进度条，加上>符号，然后显示百分比 */
        $color = $this->style[$this->color] ?? $this->style['white'];
        echo "\033[" . $color . "m" . "\033[" . $color . "m" . $process . ">".$percent."%";
        /** 默认进度条的长度是100 */
        echo "\033[100D";

        if ($percent >= 100) {
            /** 换行 */
            echo "\n\33[?25h";
            /** 还原cli窗口设置 */
            //echo "\033[0m";
            /** 光标下移1行 */
            //echo "\033[1B";
            /** 进图条已满 */
            $this->flag = true;
        }
        /** 还原cli窗口设置，防止中途退出进度条后，影响cli窗口 */
        echo "\033[0m";
        /** 显示光标 */
        echo "\033[?25h";
    }

    /**
     * 进度条结束
     * @return void
     */
    public function finished()
    {
        $this->length = $this->all;
        $this->showProgressBar(100);
    }
}