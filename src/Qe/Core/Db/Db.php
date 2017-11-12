<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-26
 * Time: 10:52
 */

namespace Qe\Core\Db;


use Qe\Core\Logger;

class Db
{

    /**
     * @var \PDO
     */
    private $pdo;
    /**
     * @var \PDOStatement
     */
    private $stmt;
    private $isTran = false;

    /**
     * @var bool 是否全局使用事物
     */
    private static $globalTran = false;

    private static $globalTranCount = 0;

    private static $globalDbs;

    /**
     * @return Db
     */
    public static function getDb($dbName = "")
    {
        if (static::$globalTran) {
            $key = empty($dbName) ? "main" : $dbName;
            if (!array_key_exists($key, static::$globalDbs)) {
                $db = new Db();
                $db->pdo = Conn::getConn($dbName);
                $db->begin();
                static::$globalDbs[$key] = $db;
            }
            return static::$globalDbs[$key];
        } else {
            $db = new Db();
            $db->pdo = Conn::getConn($dbName);
            return $db;
        }
    }

    /**
     * 启动全局事务
     */
    public static function beginGlobalTran()
    {
        static::$globalTranCount++;
        if (static::$globalTran) {
            return;
        }
        static::$globalTran = true;
        static::$globalDbs = [];
    }

    /**
     * 提交全局事务
     */
    public static function commitGlobalTran()
    {
        if (!static::$globalTran) {
            return;
        }
        static::$globalTranCount--;
        if (static::$globalTranCount > 0) {
            return;
        }
        array_map(function (Db $db) {
            $db->commit();
        }, static::$globalDbs);
        static::$globalDbs = [];
        static::$globalTran = false;
    }

    /**
     * 回滚全局事务
     */
    public static function rollBackGlobalTran()
    {
        if (!static::$globalTran) {
            return;
        }
        array_map(function (Db $db) {
            $db->rollBack();
        }, static::$globalDbs);
        static::$globalDbs = [];
        static::$globalTran = false;
        static::$globalTranCount = 0;
    }

    /**
     * 开启事务
     */
    public function begin()
    {
        $this->isTran = true;
        $this->pdo->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        if ($this->isTran) {
            $this->pdo->commit();
        }
        $this->isTran = false;
    }

    /**
     * 回滚事务
     */
    public function rollBack()
    {
        if ($this->isTran) {
            $this->pdo->rollBack();
        }
        $this->isTran = false;
    }

    public function exec($sql, $params = array())
    {
        Logger::info($sql, $params);
        $this->stmt = $this->pdo->prepare($sql);
        if ($this->stmt->execute($params)) {
        } else {
            $err = $this->stmt->errorInfo();
            throw new \PDOException($err[2], $err[1]);
        }
    }

    public function select($sql, $params = array())
    {
        $this->exec($sql, $params);
        $this->stmt->setFetchMode(\PDO::FETCH_ASSOC);
        return $this->stmt->fetchAll();
    }

    public function count($sql, $params = array())
    {
        $this->exec($sql, $params);
        return $this->stmt->fetchColumn();
    }

    public function insert($sql, $params = array())
    {
        $this->exec($sql, $params);
        return $this->pdo->lastInsertId();
    }

    public function update($sql, $params = array())
    {
        $this->exec($sql, $params);
        return $this->stmt->rowCount();
    }

    public function delete($sql, $params = array())
    {
        $this->exec($sql, $params);
        return $this->stmt->rowCount();
    }

    public static function execSqlConfigById($sqlConfigId, $params = array())
    {
        return static::execSqlConfig(SqlConfig::getSqlConfig($sqlConfigId));
    }

    public static function execSqlConfig(SqlConfig $sqlConfig, $params = array())
    {
        return $sqlConfig->exec($params);
    }

    public static function execSql($sql, $params = array(), $dbName = "")
    {
        $sqlId = md5($sql);
        $sqlConfig = SqlConfig::getSqlConfig($sqlId);
        if ($sqlConfig == null) {
            $sqlConfig = new SqlConfig();
            $sqlConfig->sql = $sql;
            $sqlConfig->tableName = $dbName;
            $sqlConfig->parseSql();
            SqlConfig::addSqlConfig($sqlId, $sqlConfig);
        }
        return static::execSqlConfig($sqlConfig, $params);
    }

}