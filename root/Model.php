<?php
namespace Root;
use Root\Lib\BaseModel;
use Root\Lib\Transaction;

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
        $class = (new BaseModel());
        $model = new (get_called_class());
        if ($model->table){
            $class->table = $model->table;
        }
        return ($class)->{$name}(...$arguments);
    }

    /**
     * 开启事务
     * @param string $level 事无级别
     * @return Transaction
     */
    final public static function startTransaction(string $level = Transaction::READ_COMMITTED){

        $class = (new BaseModel());
        $model = new (get_called_class());
        if ($model->table){
            $class->table = $model->table;
        }
        return ($class)->startTransaction($level);
    }
}