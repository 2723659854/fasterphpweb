<?php


namespace Root\Lib;

/**
 * Class Transaction
 * @package Root\Lib
 * @purpose 数据库事务
 * @author yanglong
 * @time 2024年10月22日15:31:20
 */
class Transaction
{
    /** 保存点 */
    private string $savePoint ;
    /** 客户端 */
    private \mysqli $client ;

    /** 读未提交 */
    const READ_UNCOMMITTED = 'READ UNCOMMITTED';
    /** 读已提交 */
    const READ_COMMITTED = 'READ COMMITTED';
    /** 可重复读 */
    const REPEATABLE_READ = 'REPEATABLE READ';
    /** 串行化 */
    const SERIALIZABLE = 'SERIALIZABLE';

    /**
     * 开启事务
     * @param \mysqli $client mysql客户端
     * @param string $level 事务级别
     * @return $this
     */
    public function __construct(\mysqli $client, string $level = self::READ_COMMITTED)
    {
        $this->client = $client;
        /** 设置事务级别 */
        $setLevelSql = "SET TRANSACTION ISOLATION LEVEL  " . $level;
        mysqli_query($this->client, $setLevelSql);
        mysqli_begin_transaction($this->client);

        /** 设置保存点 */
        $this->savePoint = $this->uuid();
        $savePointSql = "SAVEPOINT " . $this->savePoint;
        mysqli_query($this->client, $savePointSql);
        return $this;
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        mysqli_commit($this->client);
    }

    /**
     * 事务回滚
     */
    public function rollback()
    {
        $rollBackSql = "ROLLBACK TO SAVEPOINT " . $this->savePoint;
        mysqli_query($this->client, $rollBackSql);
    }

    /**
     * 释放保存点
     * @note 保存点在事务提交或者回滚之后，已经失效，所以本操作不是必须的
     */
    private function releaseSavepoint(){
        $releaseSavePointSql = "RELEASE SAVEPOINT ".$this->savePoint;
        mysqli_query($this->client,$releaseSavePointSql);
    }


    /**
     * 生成唯一的随机数
     * @return string
     */
    private function uuid(): string
    {
        return md5(time() . uniqid());
    }
}