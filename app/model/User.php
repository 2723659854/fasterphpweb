<?php

namespace App\Model;

use BaseModel;

class User extends BaseModel
{
    //建议指定表名，否则系统根据模型名推断表名，可能会不准确
    public $table = 'users';
}
