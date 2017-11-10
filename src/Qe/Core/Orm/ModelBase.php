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
    public $__fields = array();

    /**
     * 根据数组实例化 Model
     * @param array $param
     * @return static
     */
    public static function create($param=[]){
        return new static($param);
    }

    /**
     * 构造函数
     * ModelBase constructor.
     * @param array $param
     */
    public function __construct($param=[]){
        foreach($param as  $key=>$value){
            $this->$key=$value;
        }
    }

    public function __set($name, $value)
    {
        $this->__fields[$name] = $value;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->__fields)) return $this->__fields[$name];
        return "";
    }

    private function sqlIndexId($action, $clazz = "")
    {
        return (empty($clazz) ? get_class($this) : $clazz) . "->" . $action;
    }

    /**
     * @return SqlConfig
     */
    public function createSqlConfig($type, $clazz = "", $where = "")
    {
        if (empty($clazz)) $clazz = get_class($this);
        $sqlId = $this->sqlIndexId($type, $clazz);
        $sqlConfig = SqlConfig::getSqlConfig($sqlId);
        if ($sqlConfig == null) {
            $table = TableStruct::getTableStruct($clazz);
            $sqlConfig = new SqlConfig();
            $sqlConfig->id = $sqlId;
            SqlConfig::addSqlConfig($sqlId, $this->_createSqlConfig($sqlConfig, $table, $type, $where));
        }
        return $sqlConfig;
    }

    /**
     * @return SqlConfig
     */
    private function _createSqlConfig(SqlConfig $sqlConfig, TableStruct $table, $type, $where = "")
    {
        $sqlConfig->tableName = $table->tableName;
        if (static::INSERT == $type) {
            $ff = array();
            $vv = array();
            foreach ($table->tableColumnList as $tc) {
                if ($tc['columName'] == $table->primaryKey) continue;
                $ff[] = "`" . $tc["columName"] . "`";
                $vv[] = "{" . $tc["filedName"] . "}";
            }
            $sqlConfig->sql = "insert into `" . $table->tableName . "` (" . implode(",", $ff) . ") values(" . implode(",", $vv) . ")";
            $sqlConfig->dbName = $table->mainDbName;
            $sqlConfig->primaryKey = $table->primaryKey;
        }

        if (static::SELECT == $type) {
            $sqlConfig->sql = "select * from " . $table->tableName . " where " . (empty($where) ? $table->where : $where);
            $sqlConfig->dbName = $table->readDbName;
            $sqlConfig->returnType = $table->class->getName();
            $sqlConfig->funIntercepts[] = array("", TableStruct::class);
            $sqlConfig->funIntercepts[] = array("", $table->class->getName());
            if (count($table->relationStructList) > 0) {
                foreach ($table->relationStructList as $rs) {
                    //$sc = $this->createSqlConfig(static::SELECT, $rs->clazz,"");
                    $_table = TableStruct::getTableStruct($rs->clazz);
                    $sc = new SqlConfig();
                    $sc->tableName = $_table->tableName;
                    $sc->sql = "select * from " . $_table->tableName . " where " . $rs->where;
                    $sc->dbName = $_table->readDbName;
                    $sc->returnType = $_table->class->getName();
                    $sc->funIntercepts[] = array("", TableStruct::class);
                    $sc->funIntercepts[] = array("", $_table->class->getName());
                    $sc->parseSql();
                    $sc->relationKey = $rs->relationKey;
                    $sc->fillKey = $rs->fillKey;
                    $sc->extend = $rs->extend;
                    $sqlConfig->sqlIntercepts[] = $sc;
                }
            }

        }

        if (static::COUNT == $type) {
            $sqlConfig->sql = "select count(1) from " . $table->tableName . " where " . $table->where;
            $sqlConfig->dbName = $table->readDbName;
        }

        if (static::DELETE == $type) {
            //$sqlConfig->sql = "delete from " . $table->tableName . " where " . $table->primaryKey . "={" . $table->primaryField . "}";
            $sqlConfig->sql = "delete from " . $table->tableName . " where " . (empty($where) ? $table->where : $where);;
            $sqlConfig->dbName = $table->mainDbName;
        }

        if (static::UPDATE == $type) {
            $up = array();
            foreach ($table->tableColumnList as $tc) {
                if ($tc["columName"] == $table->primaryKey) continue;
                $up[] = "`" . $tc["columName"] . "`={" . $tc["filedName"] . "}";
            }
            $sqlConfig->sql = "update " . $table->tableName . " set " . implode(",", $up) . " where `" . $table->primaryKey . "`={" . $table->primaryField . "}";
            $sqlConfig->dbName = $table->mainDbName;
        }
        $sqlConfig->parseSql();
        return $sqlConfig;
    }


    public function intercept($field, &$map, SqlConfig $sqlConfig)
    {
        // TODO: Implement intercept() method.
    }

    public function insert()
    {
        $o = $this->exec(static::INSERT);
        if ($o == null) return 0;
        return $o;
    }

    public function select()
    {
        $o = $this->exec(static::SELECT);
        if ($o == null) return array();
        foreach ($o as $key => $model) {
            unset($model->pn);
            unset($model->ps);
            $o[$key] = $model;
        }
        return $o;
    }

    public function save()
    {
        $table = TableStruct::getTableStruct(get_class($this));
        $key = $table->primaryField;
        $map = get_object_vars($this);
        if (empty($map[$table->primaryField])) return $this->insert();
        return $this->update();
    }

    public function count()
    {
        $o = $this->exec(static::COUNT);
        if ($o == null) return 0;
        return $o;
    }

    /**
     * @return static
     */
    public function selectOne()
    {
        $list = $this->select();
        if (null == $list || count($list) == 0) return null;
        return $list[0];
    }

    public function update()
    {
        $o = $this->exec(static::UPDATE);
        if ($o == null) return 0;
        return $o;
    }

    public function delete()
    {
        $o = $this->exec(static::DELETE);
        if ($o == null) return 0;
        return $o;
    }

    public function getlist()
    {
        return array("list" => $this->select(), "count" => $this->count());
//    return new ApiResultListData(this.select(), this.count());
    }

    private function exec($type)
    {
        $sqlConfig = $this->createSqlConfig($type);
        $map = $this->getSelfMap();
        if ($type == static::COUNT)
            $map['pn'] = null;
        if ($type == static::INSERT)
            $map = $this->interceptInsert($map,$sqlConfig);
        if ($type == static::UPDATE)
            $map = $this->interceptUpdate($map,$sqlConfig);
        if ($type == static::SELECT || $type == static::COUNT)
            $map = $this->interceptSelect($map,$sqlConfig);
        if ($type == static::DELETE)
            $map = $this->interceptDelete($map,$sqlConfig);
        $sqlResult = null;
        return $sqlConfig->exec($map);
    }

    private function getSelfMap()
    {
        $map = get_object_vars($this);
        foreach ($map as $key => $val) {
            if (is_null($val)) unset($map[$key]);
            if ($val === static::DBNULL) $map[$key] = null;
        }
        return $map;
    }

    public function execSql($sql)
    {
        $table = TableStruct::getTableStruct(get_class($this));
        $dbName = $table->mainDbName;
        if (preg_match(SqlConfig::$isSelectPattern, $sql)) $dbName = $table->readDbName;
        return Db::execSql($sql, $this, $dbName);
    }

    public function interceptInsert($map,SqlConfig &$sqlConfig)
    {
        return $map;
    }

    public function interceptSelect($map,SqlConfig &$sqlConfig)
    {
        return $map;
    }
    public function interceptDelete($map,SqlConfig &$sqlConfig)
    {
        return $map;
    }
    public function interceptUpdate($map,SqlConfig &$sqlConfig)
    {
        return $map;
    }

    public function interceptWhere($where) {
        return $where;
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
}