<?php
namespace Root;
class Container
{
    /** @var array */
    protected  $providers = [];

    /**
     * 初始化配置
     *
     */
    public function __construct()
    {

    }

    /**
     * 获取对象
     * @param string $name
     * @return object
     */
    public function get(string $name)
    {
        if (!isset($this->providers[$name])) {
            if (class_exists($name)){
                $class = new $name();
                $this->providers[$name]=$class;
            }else{
                throw new \RuntimeException("[$name]类不存在！");
            }
        }
        return $this->providers[$name];
    }


}