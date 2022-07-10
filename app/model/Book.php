<?php

namespace App\Model;

use Root\BaseModel;

//必须继承Root\BaseModel
class Book extends BaseModel
{
    //建议指定表名，否则系统根据模型名推断表名，可能会不准确
    public $table = 'book';
}
