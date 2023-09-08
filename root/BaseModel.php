<?php

use mysqli_sql_exception as MysqlException;

/**
 * @purpose 数据库基类
 * @note 子类继承这一个基类，子类模型必须使用单例模式，一直使用mysql连接，否则每一次查询都会创建连接
 */
 class BaseModel
{

    private $host = null;
    private $username = null;
    private $password = null;
    private $dbname = null;
    private $port = null;
    private $type = 'mysql';

    /** mysql连接公用的关键就是把mysql静态化存储，防止每一次类被实例化的时候重复的创建连接，减少tcp握手开销 */
    private static $mysql;

    protected $sql='';
    public $table = '';
    private $field='*';
    private $order='id asc';
    private $limit=0;
    private $offset=0;
    /** 初始化 */
    public function __construct()
    {
        if (!self::$mysql){
            $this->connect();
        }
    }

     /**
      * 连接数据库
      * @return mixed
      */
    public function connect(){
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $config = config('database');
        $this->type = $config['default'];
        $database_config = $config[$this->type];
        $this->host = $database_config['host'];
        $this->username = $database_config['username'];
        $this->password = $database_config['passwd'];
        $this->dbname = $database_config['dbname'];
        $this->port = $database_config['port'];
        try{
            $mysqli = new mysqli($this->host, $this->username, $this->password, $this->dbname, $this->port);
        }catch (MysqlException $e){
            throw new RuntimeException($e->getMessage(),$e->getCode());
        }
        $mysqli->set_charset('utf8');
        /** @var  mysql */
        self::$mysql=$mysqli;
        $this->sql = '';
    }


    /** 获取表名称 */
    public function table_name(){
        return $this->table;
    }

    /**
     * 单条数据查询
     * @return array|null
     */
    public function first()
    {
        if ($this->limit){
            $limit=' limit ' .$this->offset.' ,'.$this->limit;
        }else{
            $limit='';
        }
        if (!$this->table){
            $this->table=$this->table_name();
        }
        if ($this->sql){
            $sql='select '.$this->field.' from '.$this->table.' where '.$this->sql.' order by '.$this->order.$limit;
        }else{
            $sql='select '.$this->field.' from '.$this->table.' order by '.$this->order.$limit;
        }
        $sql=$sql.';';
        $sqlCopy=$this->sql;
        try{
            $data = self::$mysql->query($sql)->fetch_assoc();
            /** 执行完之后需要清除sql语句，否则会保留上一次的sql语句 */
            $this->sql='';
            return $data;
        }catch (MysqlException $e){
            /** 如果是连接超时，则重连，并重新执行sql语句 */
            if ($e->getCode()==2006){
                /** 重连 */
                $this->connect();
                /** 还原sql语句 */
                $this->sql=$sqlCopy;
                /** 重新执行 */
                return $this->first();
            }
            global $fuck;
            $fuck->_error=no_declear('index',['msg'=>"错误码：".$e->getCode()."<br>文件：".$e->getFile()."<br>行数：".$e->getLine().PHP_EOL."<br>错误详情：".$e->getMessage()]);
            return [];
        }
    }


    /**
     * 多条数据查询
     * @return array
     */
    public function get()
    {
        if ($this->limit){
            $limit=' limit ' .$this->offset.' ,'.$this->limit;
        }else{
            $limit='';
        }
        if (!$this->table){
            $this->table=$this->table_name();
        }
        if ($this->sql){
            $sql='select '.$this->field.' from '.$this->table.' where '.$this->sql.' order by '.$this->order.$limit;
        }else{
            $sql='select '.$this->field.' from '.$this->table.' order by '.$this->order.$limit;
        }
        $sqlCopy = $this->sql;
        try{
            $sql=$sql.';';
            $list = self::$mysql->query($sql);
            $data = [];
            //返回键值对对象
            while($row=$list->fetch_object())
            {
                $array=[];
                foreach ($row as $k=>$v){
                    $array[$k]=$v;
                }
                $data[]=$array;
            }
            $this->sql='';
            return $data;
        }catch (MysqlException $e){
            /** 如果是连接超时，则重连，并重新执行sql语句 */
            if ($e->getCode()==2006){
                /** 重连 */
                $this->connect();
                /** 还原sql语句 */
                $this->sql=$sqlCopy;
                /** 重新执行 */
                return $this->get();
            }
            global $fuck;
            $fuck->_error=no_declear('index',['msg'=>"错误码：".$e->getCode()."<br>文件：".$e->getFile()."<br>行数：".$e->getLine().PHP_EOL."<br>错误详情：".$e->getMessage()]);
            return [];
        }

    }


    /**
     * 查询条件
     * @param string $name 字段
     * @param string $logic 逻辑
     * @param string|array $value 值
     * @return $this
     */
    public function where(string $name, string $logic,  $value)
    {
        if ($this->sql){
            $this->sql=$this->sql.' and ';
        }
        $int=false;
        if (is_array($value)){
            foreach ($value as $v){
                if (is_numeric($v)){
                    $int=true;
                    break;
                }
            }
            $value='('.implode(',',$value).')';
        }else{
            if (is_numeric($value)){
                $int=true;
            }
        }
        if ($int){
            $str= ' ' . $value . ' ';
        }else{
            $str=' "' . $value . '"';
        }
        $this->sql = $this->sql . ' `' . $name . '` ' . $logic .$str;
        return  $this;
    }


    /**
     * 设置表名
     * @param string $name = tableName
     * @return $this
     */
    public function table(string $name){
        $this->table=$name;
        return $this;
    }

    /**
     * 指定查询字段
     * @param array $field =[field1,field2...]
     * @return $this
     */
    public function field(array $field){
        $this->field=implode(',',$field);
        return $this;
    }


    /**
     * 插入
     * @param array $param=[key1=>value1,key1=>value1,]
     * @return bool|\mysqli_result
     */
    public function insert(array $param){
        $key=[];
        $val=[];
        foreach ($param as $k=>$v){
            $key[]=$k;
            if (is_string($v)){
                $v='"'.$v.'"';
            }
            $val[]=$v;
        }
        $sql="insert into "."$this->table  (".implode(',',$key).") values(".implode(',',$val).")";
        try{
            $sql=$sql.';';
            return self::$mysql->query($sql);
        }catch (MysqlException $e){
            /** 如果是连接超时，则重连，并重新执行sql语句 */
            if ($e->getCode()==2006){
                /** 重连 */
                $this->connect();
                /** 重新执行 */
                return $this->insert($param);
            }
            global $fuck;
            $fuck->_error=no_declear('index',['msg'=>"错误码：".$e->getCode()."<br>文件：".$e->getFile()."<br>行数：".$e->getLine().PHP_EOL."<br>错误详情：".$e->getMessage()]);
            return false;
        }

    }


    /**
     * 更新
     * @param array $param =[key1=>value1,key2=>value2]
     * @return bool|\mysqli_result
     */
    public function update(array $param){
        $_param=[];
        foreach ($param as $k=>$v){
            if (is_string($v)){
                $v='"'.$v.'"';
            }
            $_param[]=$k.' = '.$v;
        }
        $sql='update '.$this->table.' SET '.implode(',',$_param);
        if ($this->sql){
            $sql=$sql.' where '.$this->sql;
        }
        $sqlCopy=$this->sql;
        try{
            $this->sql='';
            $sql=$sql.';';
            return self::$mysql->query($sql);
        }catch (MysqlException $e){
            /** 如果是连接超时，则重连，并重新执行sql语句 */
            if ($e->getCode()==2006){
                /** 重连 */
                $this->connect();
                /** 恢复sql语句 */
                $this->sql=$sqlCopy;
                /** 重新执行 */
                return $this->update($param);
            }
            global $fuck;
            $fuck->_error=no_declear('index',['msg'=>"错误码：".$e->getCode()."<br>文件：".$e->getFile()."<br>行数：".$e->getLine().PHP_EOL."<br>错误详情：".$e->getMessage()]);
            return false;
        }
    }


    /**
     * 删除
     * @return bool|\mysqli_result
     */
    public function delete(){
        $sql='delete from '.$this->table;
        if ($this->sql){
            $sql=$sql.' where '.$this->sql;
        }
        $sqlCopy=$this->sql;
        try{
            $this->sql='';
            $sql=$sql.';';
            return self::$mysql->query($sql);
        }catch (MysqlException $e){
            /** 如果是连接超时，则重连，并重新执行sql语句 */
            if ($e->getCode()==2006){
                /** 重连 */
                $this->connect();
                $this->sql=$sqlCopy;
                /** 重新执行 */
                return $this->delete();
            }
            global $fuck;
            $fuck->_error=no_declear('index',['msg'=>"错误码：".$e->getCode()."<br>文件：".$e->getFile()."<br>行数：".$e->getLine().PHP_EOL."<br>错误详情：".$e->getMessage()]);
            return false;
        }
    }

    /**
     * 排序
     * @param string $field 排序字段
     * @param string $order 排序类型 asc 升序 desc 降序
     * @return $this
     */
    public function order($field='id',$order='asc'){
        $this->order=$field.' '.$order;
        return $this;
    }

    /**
     * 分页
     * @param int $limit 查询条数
     * @param int $offset 偏移量
     * @return $this
     */
    public function limit($limit=0,$offset=0){

        $this->offset=$offset;
        $this->limit=$limit;
        return $this;
    }

    /**
     * count()
     * @return $this
     */
    public function count(){
        $this->field='count(*)';
        return $this;
    }

    /**
     * max()
     * @param string $field
     * @return $this
     */
    public function max($field='id'){
        $this->field='max('.$field.')';
        return $this;
    }

    /**
     * min()
     * @param string $field
     * @return $this
     */
    public function min($field='id'){
        $this->field='min('.$field.')';
        return $this;
    }

    /**
     * ave()
     * @param string $field
     * @return $this
     */
    public function ave($field='id'){
        $this->field='ave('.$field.')';
        return $this;
    }

    /**
     * query()
     * @param string $sql
     * @return bool|\mysqli_result
     */
    public function query($sql=''){
        try {
            $sql=$sql.';';
            $res=self::$mysql->query($sql);
            if (is_object($res)){
                return $res->fetch_all();
            }else{
                return $res;
            }
        }catch (MysqlException $e){
            /** 如果是连接超时，则重连，并重新执行sql语句 */
            if ($e->getCode()==2006){
                /** 重连 */
                $this->connect();
                /** 重新执行 */
                return $this->query($sql);
            }
            global $fuck;
            $fuck->_error=no_declear('index',['msg'=>"错误码：".$e->getCode()."<br>文件：".$e->getFile()."<br>行数：".$e->getLine().PHP_EOL."<br>错误详情：".$e->getMessage()]);
            return false;
        }


    }


    /**
     * 批量写入数据库
     * @param array $array
     * @return bool|\mysqli_result
     */
    public function insertAll($array=[]){

        try {
            if (empty($array)){
                return false;
            }
            $value=[];
            $key=array_keys($array[0]);
            foreach ($array as $k=>$v){
                $str='';
                foreach ($v as $a=>$b){
                    if (!is_numeric($b)){
                        $b='"'.$b.'"';
                    }
                    if ($str){
                        $str=$str.','.$b;
                    }else{
                        $str=$b;
                    }
                }
                $value[]='('.$str.')';
            }
            if (!$this->table){
                $this->table=$this->table_name();
            }
            $field='('.implode(',',$key).')';
            $values=implode(',',$value);
            $sql='insert into '.$this->table.'  '.$field.'  values '.$values;
            $sql=$sql.';';
            return self::$mysql->query($sql);
        }catch (MysqlException $e){
            /** 如果是连接超时，则重连，并重新执行sql语句 */
            if ($e->getCode()==2006){
                /** 重连 */
                $this->connect();
                /** 重新执行 */
                return $this->insertAll($array);
            }
            global $fuck;
            $fuck->_error=no_declear('index',['msg'=>"错误码：".$e->getCode()."<br>文件：".$e->getFile()."<br>行数：".$e->getLine().PHP_EOL."<br>错误详情：".$e->getMessage()]);
            return false;
        }

    }

 }
