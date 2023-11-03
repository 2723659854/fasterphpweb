<?php

namespace App\Model;

use Root\Model;

class Book extends Model
{
    /** @var string $table 建议指定表名，否则系统根据模型名推断表名，可能会不准确 */
    public string $table = "book";

}