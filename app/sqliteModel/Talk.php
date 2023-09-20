<?php

namespace App\SqliteModel;

use Root\SqliteBaseModel;

/**
 * @purpose sqlite数据库
 * @note 示例
 */
class Talk extends SqliteBaseModel
{

    /** 存放目录：请修改为你自己的字段，真实路径为config/sqlite.php里面absolute设置的路径 + $dir ,例如：/usr/src/myapp/fasterphpweb/sqlite/datadir/hello/talk */
    public string $dir = 'hello/talk';

    /** 表名称：请修改为你自己的表名称 */
    public string $table = 'talk';

    /** 表字段：请修改为你自己的字段 */
    public string $field ='id INTEGER PRIMARY KEY,name varhcar(24),created text(12)';

}