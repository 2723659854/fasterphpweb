<?php
namespace Root;

class Request
{

    public $value=[];

    public $_error=null;

    public $header=[];

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
     * 获取文件
     * @param string $name
     * @return mixed|null
     */
    public function file($name='file'){

        return isset($this->value[$name])?$this->value[$name]:(isset($this->value['file'])?$this->value['file']:null);
    }

}
