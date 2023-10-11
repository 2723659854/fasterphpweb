<?php
namespace Xiaosongshu\Progress;

class ProgressBar{
    /** 当前数据长度 */
    protected $length =0;

    /** 是否已满100 */
    protected $flag =false;

    /** 数据总长度 */
    protected $all = 0;
    /** 数据放大倍数 */
    protected $bei=1;
    /** 默认字体颜色 30:黑 31:红 32:绿 33:黄 34:蓝色 35:紫色 36:深绿 37:白色 */
    protected $color='white';
    /** 字体颜色库 */
    protected $style =[
        'black'=>30,
        'red'=>31,
        'green'=>32,
        'yellow'=>33,
        'blue'=>34,
        'purple'=>35,
        'cyan'=>36,
        'white'=>37,
    ];

    /**
     * 设置数据总长度
     * @param int $number
     * @return void
     */
    public function createBar(int $number){
        $this->all =$number;
    }


    /**
     * 设置字体颜色
     * @param string $color
     * @return void
     */
    public function setColor(string $color){
        $this->color=$color;
    }


    /**
     * 增加进度条长度
     * @param int $number
     * @return void
     */
    public function advance(int $number = 1){
        /** 首次调用，总数大于100，则需要缩小 */
        if ($this->all<100&&$this->bei==1){
            /** 计算倍数 */
            $this->bei = ceil(100/$this->all);
            /** 当前总数=100 */
            $this->all=$this->all*$this->bei;
        }
        /** 首次调用，总数小于100，则需要放大 */
        if ($this->all>100&&$this->bei==1){
            /** 计算倍数 */
            $this->bei = -ceil($this->all/100);
            /** 总数=100 */
            $this->all=$this->all/abs($this->bei);
        }
        /** 需要放大步长 */
        if ($this->bei>0){
            $number = $number*$this->bei;
        }else{
            /** 缩小步长 */
            $number = $number/abs($this->bei);
        }
        /** 当前进度条长度 */
        $this->length +=$number;
        /** 刷新进度条 */
        $this->showProgressBar((int)$this->length);
    }

    /**
     * 输出进度条
     * @param int $percent 步长
     * @return void
     */
    protected function showProgressBar( int $percent  )
    {
        /** 如果进度已满100，则不要再刷新进度条 */
        if ($this->flag){
            return;
        }
        if ($percent>=100){
            $percent=100;
        }
        echo "\033[?25l";//隐藏光标
        $process = "";
        for ( $i = 1; $i <= $percent; $i++ ) {
            $process .= "=";
        }
        /** 输出进度条，加上>符号，然后显示百分比 */
        $color = $this->style[$this->color]??$this->style['white'];
        echo "\033[".$color."m$percent%"."\033[".$color."m" . $process . ">";
        /** 默认进度条的长度是100 */
        echo "\033[100D";

        if (  $percent>=100 ) {
            /** 换行 */
            echo "\n\33[?25h";
            /** 还原cli窗口设置 */
            //echo "\033[0m";
            /** 光标下移1行 */
            //echo "\033[1B";
            /** 进图条已满 */
            $this->flag=true;
        }
        /** 还原cli窗口设置，防止中途退出进度条后，影响cli窗口 */
        echo "\033[0m";

    }
}