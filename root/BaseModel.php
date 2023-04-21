<?php

use mysqli;
use mysqli_sql_exception as MysqlException;

class BaseModel
{

    private $host = null;
    private $username = null;
    private $password = null;
    private $dbname = null;
    private $port = null;
    private $type = 'mysql';

    private $mysql;
    private $sql='';
    public $table = '';
    private $field='*';
    private $order='id asc';
    private $limit=0;
    private $offset=0;

    public function __construct()
    {
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
            echo $e->getMessage();
            die("致命错误：数据库连接失败！".$e->getMessage());
        }
        $mysqli->set_charset('utf8');
        $this->mysql = $mysqli;
        $this->sql = '';
        var_dump("初始化数据库完成");
    }

    private static function table_name(){
       return array_reverse(explode('\\',strtolower(get_called_class())))[0];
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


        var_dump($sql);
        //todo 某一个中文搜索不出来 username = 牛魔王 ，linux 没有mysql 原生的函数
        try{
            return $this->mysql->query($sql)->fetch_assoc();
        }catch (MysqlException $e){
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
        try{
            $list = $this->mysql->query($sql);
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
            return $data;
        }catch (MysqlException $e){

            echo $e->getMessage();
            die("数据库操作失败！");
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
            return $this->mysql->query($sql);
        }catch (MysqlException $e){
            echo $e->getMessage();
            die('数据库操作失败');
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
        $sql='update '.$this->table.' SET '.implode(',',$_param).$this->sql;
        try{
            return $this->mysql->query($sql);
        }catch (MysqlException $e){

            echo $e->getMessage();
            die('数据库操作失败');
        }
    }


    /**
     * 删除
     * @return bool|\mysqli_result
     */
    public function delete(){
        $sql='delete from '.$this->table.' '.$this->sql;
        try{
            return $this->mysql->query($sql);
        }catch (MysqlException $e){

            echo $e->getMessage();
            die('数据库操作失败');
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
        $res=$this->mysql->query($sql);
        if (is_object($res)){
            return $res->fetch_all();
        }else{
            return $res;
        }

    }


    /**
     * 批量写入数据库
     * @param array $array
     * @return bool|\mysqli_result
     */
    public function insertAll($array=[]){
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
        return $this->mysql->query($sql);
    }

}
