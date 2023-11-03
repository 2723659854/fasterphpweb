<?php

namespace Root\Lib;

use Root\Lib\Sqlite;
/**
 * @purpose sqlite基类q
 * @note 请勿改动，除非你真的明白此文件的原理
 */
class SqliteBaseModel
{

    /** 数据存放目录 */
    public string $dir = '';
    /** 表名称 */
    public string $table = '';
    /** 表字段 */
    public string $field = '';
    /** 客户端 */
    protected static array  $client = []  ;

    /** 静态化模型 */
    public static function __callStatic(string $name, array $arguments)
    {
        $class = new (get_called_class());
        $table = $class->table;
        $dir   = $class->dir;
        $field = $class->field;
        /** 依据这三个字段区分是否是同一张表 */
        $key = md5(json_encode(['table'=>$table,'dir'=>$dir,'field'=>$field]));
        if (!isset(self::$client[$key])){
            /** 创建客户端并保存 */
            self::$client[$key]= new Sqlite($dir, $table,$field);
        }
        /** 调用sqlite方法 */
        return (self::$client[$key])->{$name}(...$arguments);
    }

}