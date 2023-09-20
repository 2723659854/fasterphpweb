<?php

namespace Root;

class Sqlite
{
    /** 连接id */
    protected static $uuid;
    /** 连接池 */
    protected static $POOL;
    /** 配置 */
    private static $CONFIG;
    /** 数据存放路径 */
    private static $SQLITE;

    /**
     * 初始化DB文件
     * @param string $dir 存放数据目录
     * @param string $table 表名
     * @param string $field 字段
     * @throws \Exception
     * @note 统一放在database文件里面，防止用户传输的参数过多而导致出错
     */
    public function __construct(string $dir, string $table, string $field)
    {
        /** 读取配置 并创建存放数据所需的目录和文件 */
        if (!isset(self::$CONFIG)) {
            $config       = config('sqlite');
            self::$CONFIG = $config;
            self::$SQLITE = $dir;
            $this->directory();
        }
        @['absolute' => $absolute, 'format' => $format] = self::$CONFIG;
        // 检查目录
        $trim = trim($dir, './:');
        if ($field == null) throw new \Exception('目录错误或未定义数据表');
        /** 实例化sqlite操作类 */
        $db = new \SQLite3("$absolute/$trim/database.$format");
        /** 创建表 */
        ($table && $field) && $db->exec("create table if not exists $table($field);");
        // 分配UUID + 初始参数
        $uuid              = session_create_id();
        self::$uuid        = $uuid;
        /** 保存连接 */
        self::$POOL[$uuid] = [
            'DB' => $db,
            'absolute' => "$absolute/$trim/database.$format",
            'table' => $table,
            'select' => '*',
            'order' => '',
            'set' => [],
            'where' => '',
            'limit' => 'LIMIT 10',
            'offset' => 'OFFSET 0',
        ];

    }

    // 映射 + 创建 文件目录

    /**
     * 创建目录
     * @return void
     */
    private function directory()
    {
        $sqlite = self::$SQLITE;
        @['absolute' => $absolute] = self::$CONFIG;
        $list   = [];
        $list[] = rtrim($sqlite, '/');
        foreach ($list as $relative) {
            is_dir("$absolute/$relative") || mkdir("$absolute/$relative", 0777, true);
        }
    }

    // PDO 句柄

    /**
     * 获取操作客户端
     * @return mixed
     */
    public function PDO()
    {
        @['DB' => $db] = self::$POOL[self::$uuid];
        return $db;
    }

    // version

    /**
     * 获取sqlite版本号
     * @return mixed
     */
    public function version()
    {
        @['DB' => $db] = self::$POOL[self::$uuid];
        return $db->version();
    }

    // table

    /**
     * 设置表名
     * @param string $string 表名
     * @return $this
     */
    public function table(string $string)
    {
        self::$POOL[self::$uuid]['table'] = $string;
        return $this;
    }

    // select

    /**
     * 设置查询的字段
     * @param array $params
     * @return $this
     */
    public function select(array $params = ['*'])
    {
        self::$POOL[self::$uuid]['select'] = implode(',', $params);
        return $this;
    }

    // where

    /**
     * 设置查询条件
     * @param array $params
     * @return $this
     * @note  可以是一维数组|二维数组
     */
    public function where(array $params)
    {
        $default = [];
        foreach ($params as $key => $val) {
            if (is_array($val) && (count($val) == 3)) {
                $default[] = sprintf("`%s` %s %s", $val[0], $val[1], is_string($val[2]) ? "'$val[2]'" : $val[2]);
            } else {
                $default[] = sprintf("`%s` = %s", $key, is_string($val) ? "'$val'" : $val);
            }
        }
        self::$POOL[self::$uuid]['where'] = sprintf("WHERE %s", implode(' AND ', $default));
        return $this;
    }

    // set

    /**
     * 设置属性
     * @param $data
     * @return $this
     */
    public function set($data)
    {
        if (array_is_list($data)) {
            $vals = [];
            foreach ($data as $item) {
                @[$keys, $val] = $this->ToData($item);
                $vals[] = $val;
            }
            $vals = implode(",", $vals);
        } else {
            @[$keys, $vals] = $this->ToData($data);
            $sets = $this->ToWhere($data);
        }
        self::$POOL[self::$uuid]['set'] = [$keys, $vals, $sets ?? ''];
        return $this;
    }

    // limit

    /**
     * 设置查询条数
     * @param int $int
     * @return $this
     */
    public function limit(int $int)
    {
        self::$POOL[self::$uuid]['limit'] = "LIMIT $int";
        return $this;
    }

    // offset

    /**
     * 设置偏移
     * @param int $int
     * @return $this
     */
    public function offset(int $int)
    {
        self::$POOL[self::$uuid]['offset'] = "OFFSET $int";
        return $this;
    }

    /**
     * 分页
     * @param int $page
     * @param int $size
     * @return $this
     */
    public function page(int $page=1,int $size = 15){
        $size=$size??15;
        $page = $page??1;
        $offset = ($page-1)*$size;
        self::$POOL[self::$uuid]['limit'] = "LIMIT $size";
        self::$POOL[self::$uuid]['offset'] = "OFFSET $offset";
        return $this;
    }

    // row_array

    /**
     * 单条查询
     * @param bool $flag 是否返回sql
     * @return mixed|string
     */
    public function first(bool $flag = false)
    {
        @['DB' => $db, 'table' => $table, 'where' => $where, 'offset' => $offset, 'select' => $select, 'order' => $order] = self::$POOL[self::$uuid];
        $string = sprintf("SELECT %s FROM %s %s %s %s %s", $select, $table, $where, $order ? "ORDER BY $order" : "", "LIMIT 1", $offset);
        return $flag ? $string : $db->querySingle($string, true);
    }

    // result_array

    /**
     * 多条查询
     * @param bool $flag 是否返回sql
     * @return array|string
     */
    public function get(bool $flag = false)
    {
        @['DB' => $db, 'table' => $table, 'where' => $where, 'limit' => $limit, 'offset' => $offset, 'select' => $select, 'order' => $order] = self::$POOL[self::$uuid];
        $string = sprintf("SELECT %s FROM %s %s %s %s %s", $select, $table, $where, $order ? "ORDER BY $order" : "", $limit, $offset);
        if ($flag) return $string;
        $query = $db->query($string);
        while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
            $result[] = $row;
        }
        return $result ?? [];
    }

    /**
     * 排序
     * @param array $orderBy
     * @return $this
     */
    public function orderBy(array $orderBy=['id'=>'desc']){
        $string = '';
        foreach ($orderBy as $field=>$direction){
            $string = $string.', '.$field.' '.$direction;
        }
        self::$POOL[self::$uuid]['order']=trim($string,',');
        return $this;
    }

    // count_array

    /**
     * 统计
     * @param bool $flag 是否返回sql
     * @return array|string
     */
    public function count(bool $flag = false)
    {
        @['DB' => $db, 'table' => $table, 'where' => $where, 'limit' => $limit, 'offset' => $offset, 'select' => $select, 'order' => $order] = self::$POOL[self::$uuid];
        $string = sprintf("SELECT %s FROM %s %s %s %s %s", $select, $table, $where, $order ? "ORDER BY $order" : "", $limit, $offset);
        if ($flag) return $string;
        $query = $db->query($string);
        while ($row = $query->fetchArray(SQLITE3_ASSOC)) {
            $list[] = $row;
        }
        @['count' => $count] = $db->querySingle(sprintf("SELECT COUNT(*) AS count FROM %s %s", $table, $where), true);
        return ['list' => $list ?? [], 'count' => $count ?? 0];
    }

    // insert

    /**
     * 插入数据
     * @param array $data
     * @param bool $flag 是否返回sql
     * @return false|mixed|string
     */
    public function insert(array $data ,$flag = false)
    {
        if (array_is_list($data)) {
            $vals = [];
            foreach ($data as $item) {
                @[$keys, $val] = $this->ToData($item);
                $vals[] = $val;
            }
            $vals = implode(",", $vals);
        } else {
            @[$keys, $vals] = $this->ToData($data);
            $sets = $this->ToWhere($data);
        }
        self::$POOL[self::$uuid]['set'] = [$keys, $vals, $sets ?? ''];

        $uuid = self::$uuid;
        @['DB' => $db, 'table' => $table, 'set' => [$keys, $vals]] = self::$POOL[$uuid];
        $string = sprintf("INSERT INTO %s %s VALUES %s ", $table, $keys, $vals);
        return $flag ? $string : (@$db->exec($string) ? $db->lastInsertRowID() : false);
    }

    // update

    /**
     * 更新数据
     * @param array $data 需要更新的数据
     * @param bool $flag 是否返回sql
     * @return false|mixed|string
     */
    public function update(array $data,$flag = false)
    {
        if (array_is_list($data)) {
            $vals = [];
            foreach ($data as $item) {
                @[$keys, $val] = $this->ToData($item);
                $vals[] = $val;
            }
            $vals = implode(",", $vals);
        } else {
            @[$keys, $vals] = $this->ToData($data);
            $sets = $this->ToWhere($data);
        }
        self::$POOL[self::$uuid]['set'] = [$keys, $vals, $sets ?? ''];

        $uuid = self::$uuid;
        @['DB' => $db, 'table' => $table, 'where' => $where, 'set' => [$keys, $vals, $sets]] = self::$POOL[$uuid];
        $string = sprintf("UPDATE %s SET %s %s", $table, $sets, $where);
        return $flag ? $string : (@$db->exec($string) ? $db->changes() : false);
    }


    // replace
    // delete
    /**
     * 删除数据
     * @param bool $flag 是否返回sql
     * @return false|mixed|string
     */
    public function delete(bool $flag = false)
    {
        @['DB' => $db, 'table' => $table, 'where' => $where] = self::$POOL[self::$uuid];
        $string = sprintf("DELETE FROM %s %s", $table, $where);
        return $flag ? $string : (@$db->exec($string) ? $db->changes() : false);
    }

    // ToData
    private function ToData($array)
    {
        $keys = trim(json_encode(array_keys($array), 320), '[]');
        $vals = trim(json_encode(array_values($array), 320), '[]');
        return ["($keys)", "($vals)"];
    }

    // ToOrder
    private function ToOrder($array)
    {
        return implode(',', $array);
    }

    // ToWhere
    private function ToWhere($array, $separator = 'AND')
    {
        foreach ($array as $k => $v) {
            $list[] = sprintf("`%s` = %s", $k, is_string($v) ? "'$v'" : $v);
        }
        return implode(" $separator ", $list);
    }
}