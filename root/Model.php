<?php
namespace Root;

/**
 * 模型层
 */
class Model
{
    /** @var string 表名称 */
    public string $table = '';

    /**
     * 静态化调用模型
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments){
        $class = (new \BaseModel());
        $model = new (get_called_class());
        if ($model->table){
            $class->table = $model->table;
        }
        return ($class)->{$name}(...$arguments);
    }
}