<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-18
 * Time: 18:23
 */

namespace Qe\Core\Orm;


use Qe\Core\Db\Db;
use Qe\Core\Db\SqlConfig;
use Qe\Core\Logger;

class ModelBase implements AbstractFunIntercept, \ArrayAccess
{
    /**
     * @Transient
     */
    const DBNULL = "DBNULL";
    /**
     * @Transient
     */
    const INSERT = "insert";
    /**
     * @Transient
     */
    const SELECT = "select";
    /**
     * @Transient
     */
    const COUNT = "count";
    /**
     * @Transient
     */
    const UPDATE = "update";
    /**
     * @Transient
     */
    const DELETE = "delete";

    /**
     * @Transient
     */
    public $pn;
    /**
     * @Transient
     */
    public $ps = 50;
    /**
     * @Transient
     */
    public $isWithRelation = null;

    /**
     * @Transient
     */
    public $__fields = array();

    /**
     * 根据数组实例化 Model
     * @param array $param
     * @return static
     */
    public static function create($param = [])
    {
        return new static($param);
    }

    /**
     * 构造函数
     * ModelBase constructor.
     * @param array $param
     */
    public function __construct($param = [])
    {
        foreach ($param as $key => $value) {
            $this->$key = $value;
        }
    }

    public function __set($name, $value)
    {
        $this->__fields[$name] = $value;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->__fields)) {
            return $this->__fields[$name];
        }
        return "";
    }

    public function insert()
    {
        $o = $this->exec(static::INSERT);
        if (!$o) {
            return 0;
        }
        return $o;
    }

    public function delete()
    {
        $o = $this->exec(static::DELETE);
        if (!$o) {
            return 0;
        }
        return $o;
    }

    public function update()
    {
        $o = $this->exec(static::UPDATE);
        if (!$o) {
            return 0;
        }
        return $o;
    }

    public function save()
    {
        $table = TableStruct::getTableStruct(get_class($this));
        $map = get_object_vars($this);
        if (empty($map[$table->primaryField])) {
            return $this->insert();
        }
        return $this->update();
    }

    public function select()
    {
        $this->isWithRelation = false;
        $o = $this->exec(static::SELECT);
        $this->isWithRelation = null;
        if ($o == null) {
            return array();
        }
        foreach ($o as $key => $model) {
            unset($model->pn);
            unset($model->ps);
            $o[$key] = $model;
        }
        return $o;
    }

    public function selectWithrelation()
    {
        $this->isWithRelation = true;
        $o = $this->exec(static::SELECT);
        $this->isWithRelation = null;
        if ($o == null) {
            return array();
        }
        foreach ($o as $key => $model) {
            unset($model->pn);
            unset($model->ps);
            $o[$key] = $model;
        }
        return $o;
    }


    /**
     * @return static
     */
    public function selectOne()
    {
        $list = $this->select();
        if (null == $list || count($list) == 0) {
            return null;
        }
        return $list[0];
    }

    /**
     * @return static
     */
    public function selectOneWithrelation()
    {
        $list = $this->selectWithrelation();
        if (null == $list || count($list) == 0) {
            return null;
        }
        return $list[0];
    }

    public function count()
    {
        $o = $this->exec(static::COUNT);
        if (!$o) {
            return 0;
        }
        return $o;
    }

    public function getlist()
    {
        return array("list" => $this->select(), "count" => $this->count());
    }

    private function exec($type)
    {
        $sqlConfig = $this->createSqlConfig($type);
        $map = $this->getSelfMap();
        return $sqlConfig->exec($map);
    }

    private function getSelfMap()
    {
        $map = get_object_vars($this);
        return static::dealParams($map);
    }

    public static function dealParams($map = [])
    {
        foreach ($map as $key => $val) {
            if (is_null($val)) {
                unset($map[$key]);
            }
            if ($val === static::DBNULL) {
                $map[$key] = null;
            }
        }
        return $map;
    }

    public function execSql($sql)
    {
        $table = TableStruct::getTableStruct(get_class($this));
        $dbName = $table->masterDbName;
        if (preg_match(SqlConfig::$isSelectPattern, $sql)) {
            $dbName = $table->slaveDbName;
        }
        return Db::execSql($sql, $this->getSelfMap(), $dbName);
    }


    private function sqlIndexId($action, $clazz = "")
    {
        return (empty($clazz) ? get_class($this) : $clazz) . "->" . $action;
    }

    /**
     * @return SqlConfig
     */
    public function createSqlConfig($type)
    {
        $clazz = get_class($this);
        $sqlId = $this->sqlIndexId($type, $clazz);
        $sqlConfig = SqlConfig::getSqlConfig($sqlId);
        if ($sqlConfig == null) {
            $table = TableStruct::getTableStruct($clazz);
            $sqlConfig = new SqlConfig();
            $sqlConfig->id = $sqlId;
            $sqlConfig->tableName = $table->tableName;
            SqlConfig::addSqlConfig($this->_createSqlConfig($sqlConfig, $table, $type));
        }
        return $sqlConfig;
    }

    /**
     * @return SqlConfig
     */
    private function _createSqlConfig(SqlConfig $sqlConfig, TableStruct $table, $type)
    {
        if (static::INSERT == $type) {
            $ff = array();
            $vv = array();
            foreach ($table->tableColumnList as $tc) {
                if ($tc['columName'] == $table->primaryKey) {
                    continue;
                }
                $ff[] = "`" . $tc["columName"] . "`";
                $vv[] = "{" . $tc["filedName"] . "}";
            }
            $sqlConfig->sql = "INSERT INTO `" . $table->tableName . "` (" . implode(",",
                    $ff) . ") VALUES(" . implode(",", $vv) . ")";
            $this->interceptInsert($sqlConfig);
            $sqlConfig->dbName = $table->masterDbName;
            $sqlConfig->primaryKey = $table->primaryKey;
        }

        if (static::SELECT == $type) {
            $sqlConfig->sql = "SELECT * FROM " . $table->tableName . " WHERE " . $table->where;
            $sqlConfig->dbName = $table->slaveDbName;
            $sqlConfig->returnType = $table->class->getName();
            $sqlConfig->funIntercepts[] = array("", TableStruct::class);
            $sqlConfig->funIntercepts[] = array("", $table->class->getName());
            $this->interceptSelect($sqlConfig);
            if (count($table->relationStructList) > 0) {
                foreach ($table->relationStructList as $rs) {
                    $_table = TableStruct::getTableStruct($rs->clazz);
                    $sc = new SqlConfig();
                    $sc->tableName = $_table->tableName;
                    $sc->dbName = $_table->slaveDbName;
                    $sc->returnType = $_table->class->getName();
                    $sc->id = $sqlConfig->id . "-" . $rs->fillKey;
                    $sc->funIntercepts[] = array("", TableStruct::class);
                    $sc->funIntercepts[] = array("", $_table->class->getName());
                    $sc->sql = "SELECT * FROM " . $_table->tableName . " WHERE " . $rs->where;
                    $sc->parseSql();
                    $sc->relationKey = $rs->relationKey;
                    $sc->fillKey = $rs->fillKey;
                    $sc->extend = $rs->extend;
                    $sqlConfig->sqlIntercepts[] = $sc;
                }
            }

        }

        if (static::COUNT == $type) {
            $this->interceptSelect($sqlConfig);
            $sqlConfig->sql = "SELECT count(1) FROM " . $table->tableName . " WHERE " . $table->where;
            $sqlConfig->dbName = $table->slaveDbName;
        }

        if (static::DELETE == $type) {
            $sqlConfig->sql = "DELETE FROM " . $table->tableName . " WHERE " . $table->where;
            $sqlConfig->dbName = $table->masterDbName;
        }

        if (static::UPDATE == $type) {
            $up = array();
            foreach ($table->tableColumnList as $tc) {
                if ($tc["columName"] == $table->primaryKey) {
                    continue;
                }
                $up[] = "`" . $tc["columName"] . "`={" . $tc["filedName"] . "}";
            }
            $sqlConfig->sql = "update " . $table->tableName . " set " . implode(",",
                    $up) . " where `" . $table->primaryKey . "`={" . $table->primaryField . "}";
            $sqlConfig->dbName = $table->masterDbName;
            $this->interceptUpdate($sqlConfig);
        }
        $sqlConfig->parseSql();
        return $sqlConfig;
    }


    /**
     * 对返回的结果集做处理
     * @param $field
     * @param $map
     * @param SqlConfig $sqlConfig
     */
    public function intercept($field, &$map, SqlConfig &$sqlConfig)
    {
        // TODO: Implement intercept() method.
    }

    /**
     * 给 model 添加 参数处理器
     * @param SqlConfig $sqlConfig
     */
    public function interceptInsert(SqlConfig &$sqlConfig)
    {
    }

    public function interceptSelect(SqlConfig &$sqlConfig)
    {
    }

    public function interceptDelete(SqlConfig &$sqlConfig)
    {
    }

    public function interceptUpdate(SqlConfig &$sqlConfig)
    {
    }


    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset)
    {
        return isset($this->$offset) ? $this->$offset : null;
    }


    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }


    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    public function __call($name, $arguments)
    {
        if (!$this->$name) {
            $clazz = get_class($this);
            $relations = TableStruct::getTableStruct($clazz)->relationStructList;
            foreach ($relations as $relation) {
                if ($relation->fillKey === $name) {
                    $sqlId = $this->sqlIndexId(static::SELECT, $clazz);
                    $sqlIntercepts = $this->createSqlConfig(static::SELECT)->sqlIntercepts;
                    foreach ($sqlIntercepts as $intercept) {
                        if ($intercept->id === $sqlId . "-" . $name) {
                            $key = explode("|", $relation->relationKey)[0];
                            $params = [$key => $this->$key];
                            $ret = $intercept->exec($params);
                            if ($relation->extend === "one2One") {
                                if (is_array($ret) && count($ret) > 0) {
                                    $this->$name = $ret[0];
                                }
                            } else {
                                $this->$name = $ret;
                            }
                        }
                    }

                }
            }
        }
        return $this->$name;
    }
}