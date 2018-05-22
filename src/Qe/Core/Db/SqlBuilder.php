<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-18
 * Time: 9:04
 */

namespace Qe\Core\Db;


use BaconQrCode\Common\Mode;
use Qe\Core\ClassCache;
use Qe\Core\Mvc\ParameterInterceptor;
use Qe\Core\Orm\AbstractFunIntercept;
use Qe\Core\Orm\ModelBase;
use Qe\Core\Orm\SqlAndOrNode;
use Qe\Core\Orm\TableStruct;
use Qe\Core\SysCache;
use Qe\Core\Convert;
use Qe\Core\Logger;
use Qe\Core\Orm\Utils;
use Qe\Core\TimeWatcher;

class SqlBuilder
{
    /**
     * @var SqlConfig
     */
    private $sqlConfig;
    private $sqlId;

    /**
     * @return SqlBuilder
     */
    public static function get()
    {
        return new static();
    }

    private function __construct()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $this->sqlId = $trace[2]['class'] . "->" . $trace[1]['line'];
        $this->sqlConfig = new SqlConfig();
    }

    /**
     * @return SqlBuilder
     */
    public function dbName($dbName)
    {
        $this->sqlConfig->dbName = $dbName;
        return $this;
    }

    /**
     * @return SqlBuilder
     */
    public function parentDbName($parentDbName)
    {
        $this->sqlConfig->parentDbName = $parentDbName;
        return $this;
    }

    /**
     * @return SqlBuilder
     */
    public function relationKey($relationKey)
    {
        $this->sqlConfig->relationKey = $relationKey;
        return $this;
    }

    /**
     * @return SqlBuilder
     */
    public function fillKey($fillKey)
    {
        $this->sqlConfig->fillKey = $fillKey;
        return $this;
    }

    /**
     * @return SqlBuilder
     */
    public function extend($extend)
    {
        $this->sqlConfig->extend = $extend;
        return $this;
    }

    /**
     * @return SqlBuilder
     */

    public function isTran($isTran)
    {
        $this->sqlConfig->isTran = $isTran;
        return $this;
    }

    /**
     * @return SqlBuilder
     */
    public function sql($sql)
    {
        $this->sqlConfig->sql = $sql;
        return $this;
    }

    /**
     * @return SqlBuilder
     */
    public function sqlIntercepts($sqlIntercepts = [])
    {
        foreach ($sqlIntercepts as $sqlIntercept) {
            $this->sqlConfig->sqlIntercepts[] = $sqlIntercept;
        }
        return $this;
    }

    /**
     * @return SqlBuilder
     */
    public function addSqlIntercept(SqlConfig $sqlIntercept)
    {
        $this->sqlConfig->sqlIntercepts[] = $sqlIntercept;
        return $this;
    }

    /**
     * @return SqlBuilder
     */
    public function funIntercepts($funIntercepts = [])
    {
        foreach ($funIntercepts as $funIntercept) {
            $this->sqlConfig->funIntercepts[] = $funIntercept;
        }
        return $this;
    }

    /**
     * @return SqlBuilder
     */
    public function addFunIntercept(AbstractFunIntercept $funIntercept)
    {
        $this->sqlConfig->funIntercepts[] = $funIntercept;
        return $this;
    }

    /**
     * @return SqlBuilder
     */
    public function paramIntercepts($paramIntercepts = [])
    {
        foreach ($paramIntercepts as $paramIntercept) {
            $this->sqlConfig->paramIntercepts[] = $paramIntercept;
        }
        return $this;
    }

    /**
     * @return SqlBuilder
     */
    public function addParamIntercept(ParameterInterceptor $interceptor)
    {
        $this->sqlConfig->paramIntercepts[] = $interceptor;
        return $this;
    }

    /**
     * @return SqlBuilder
     */
    public function returnType($returnType)
    {
        $this->sqlConfig->funIntercepts[] = array("", TableStruct::class);
        $this->sqlConfig->returnType = $returnType;
        return $this;
    }

    public function exec($params)
    {
        if (is_object($params)) {
            $params = ModelBase::dealParams(get_object_vars($params));
        }
        return $this->getSqlConfig()->exec($params);
    }

    /**
     * @return SqlConfig
     */
    private function getSqlConfig()
    {
        if (!SqlConfig::getSqlConfig($this->sqlId)) {
            $this->sqlConfig->id = $this->sqlId;
            $this->sqlConfig->parseSql();
            SqlConfig::addSqlConfig($this->sqlConfig);
        }
        return SqlConfig::getSqlConfig($this->sqlId);
    }
}