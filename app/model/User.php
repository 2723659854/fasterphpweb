<?php

namespace App\Model;

use BaseModel;

class User extends BaseModel
{
    /** 私有静态属性，存放该类的实例 */
    private static $instance = null;

    public $sql='';

    /** 公共的静态方法，实例化该类本身，只实例化一次 */
    public static function getInstance() {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }
        return self::$instance;
    }
    /** @var string $table 建议指定表名，否则系统根据模型名推断表名，可能会不准确 */
    public $table = 'users';

    /** 获取表名 */
    public function table_name(){
        return $this->table;
    }
}
