<?php
/** 命名空间 */
namespace App\Model;
/** 引入需要继承的模型基类 */
use BaseModel;

/** 定义模型名称 并继承模型基类 */
class Book extends BaseModel
{
    /** @var string $table 建议指定表名，否则系统根据模型名推断表名，可能会不准确 */
    public $table = 'messages';
}
