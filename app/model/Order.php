<?php

namespace App\Model;

use Root\Model;
/**
 * @purpose mysql模型
 * @author administrator
 * @time 2023-09-08 05:50:47
 */
class Order extends Model
{
    /** @var string $table 建议指定表名，否则系统根据模型名推断表名，可能会不准确 */
    public string $table = "order";

}