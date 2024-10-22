<?php

namespace App\Model;

use Root\Model;
/**
 * @purpose mysql模型
 * @author administrator
 * @time 2024-10-22 07:34:11
 */
class Admin extends Model
{
    /** @var string $table 建议指定表名，否则系统根据模型名推断表名，可能会不准确 */
    public string $table = "admin";

}