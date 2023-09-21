<?php
namespace Root;

class Request
{

    public array $value=[];

    public string $_error= '';

    public array $header=[];

    private string $buffer = '';

    public function __construct(string $buffer = ''){
        //todo 解析传输过来的变量
    }

    /**
     * 获取request请求参数
     * @param string $name
     * @return string|string[]|null
     */
    public function param($name='',$default=null){
        if ($name){
            return array_key_exists($name,$this->value)?$this->value[$name]:$default;
        }else{
            return $this->value;
        }
    }

    /**
     * 设置请求参数
     * @param $key
     * @param $value
     */
    public function set($key,$value){
        $this->value[$key]=$value;
    }

    /**
     * 设置获取header信息
     * @param string $name
     * @param null $value
     * @return mixed|null
     */
    public function header($name='',$value=null){
        if ($value){
           return $this->header[$name]=$value;
        }else{
            return array_key_exists($name,$this->header)?$this->header[$name]:null;
        }

    }

    /**
     * 文件作为二维数组被存储，获取的时候
     * 获取文件
     * @param string $name
     * @return mixed|null
     */
    public function file($name=''){
        if ($name){
            return isset($this->value['file'][$name])?$this->value['file'][$name]:null;
        }else{
            return isset($this->value['file'])?$this->value['file']:null;
        }
    }

}
