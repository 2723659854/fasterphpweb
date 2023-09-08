<?php

namespace Root;

use Xiaosongshu\Colorword\Transfer;
use Xiaosongshu\Progress\ProgressBar;
use Xiaosongshu\Table\Table;

abstract class BaseCommand
{
    /** @var string $command 命令触发字段 必填 */
    public $command = 'check:wrong';

    protected $_bar;

    protected $_colorWord;

    protected $_table;

    public $input = [
        'argument'=>[],
        'option'=>[],
    ];

    public function __construct()
    {
        $this->_bar       = new ProgressBar();
        $this->_colorWord = new Transfer();
        $this->_table     = new Table();
    }

    /**
     * 配置参数
     * @return void
     */
    public function configure(){

    }

    /**
     * 添加参数
     * @param string $name
     * @return void
     */
    public function addArgument(string $name){
        $this->input['argument'][$name]=null;
    }

    /**
     * 获取参数
     * @param string $name
     * @return mixed|null
     */
    public function getArgument(string $name){
        return $this->input['argument'][$name]??null;
    }



    /**
     * 设置可选参数
     * @param string $name
     * @return void
     */
    public function addOption(string $name){
        $this->input['option'][$name]=null;
    }

    /**
     * 获取选填参数
     * @param string $name
     * @return mixed|null
     */
    public function getOption(string $name){
        return $this->input['option'][$name]??null;
    }

    /**
     * 业务逻辑 必填
     * @return void
     */
    public function handle()
    {

    }

    /**
     * 输出提示信息
     * @param $string
     * @return void
     */
    public function info($string)
    {
        echo $this->_colorWord->info((string)$string)."\r\n";
    }

    /**
     * 输出错误信息
     * @param $string
     * @return void
     */
    public function error($string){
        echo $this->_colorWord->error((string)$string)."\r\n";
    }

    /**
     * 输出普通信息
     * @param $string
     * @return void
     */
    public function line($string){
        echo $this->_colorWord->line((string)$string)."\r\n";
    }

    /**
     * 输出表格
     * @param array $header
     * @param array $rows
     * @return void
     */
    public function table(array $header,array $rows){
        try {
            $this->_table->table($header,$rows);
        }catch (\Exception $exception){
            $this->error($exception->getMessage());
        }
    }

    /**
     * 创建进度条
     * @param int $count
     * @return object
     */
    public function createProgressBar(int $count=1){
        $bar = new ProgressBar();
        $bar->createBar($count);
        return $bar;
    }

}