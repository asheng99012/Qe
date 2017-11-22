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
use Qe\Core\Orm\ModelBase;
use Qe\Core\Orm\SqlAndOrNode;
use Qe\Core\SysCache;
use Qe\Core\Convert;
use Qe\Core\Logger;
use Qe\Core\Orm\Utils;
use Qe\Core\TimeWatcher;

class SqlConfig
{
    public static $RESULTASINT = "resultAsInt";
    public $id;
    public $dbName;
    public $isTran = false;
    public $sql;
    public $sqlIntercepts = array();
    public $relationKey;
    public $fillKey;
    public $refId;
    public $autoPage;
    public $tableName;
    public $funIntercepts = array();
    public $paramIntercepts = array();
    public $andOrNodes = array();
    public $ffList = array();
    public $extend;
    public $group;
    public $returnType;
    public $sqlType;
    public $parentDbName;

    private static $sqlSelectPattern = "/\\s+(?i)([`\\.'a-zA-Z\\d_]+){1}\\s*(!=|>=|=<|=>|<=|<|>|=|\\s+like\\s+|\\s+in\\s+|\\s+not\\s+in\\s+|\\s+by\\s+){1}\\s*([\\(%'`\\.'a-zA-Z\\d_\\+\\-\\s]+){0,1}\\s*(\\{\\s*([a-zA-Z\\d_]+)\\s*\\}){1}(\\s*[\\)%']+){0,1}/";
    private static $sqlAndOrPattern = "/\\s+(?i)([`\\.'a-zA-Z\\d_]+){1}\\s*(&|\\|){1}\\s*(\\{\\s*([a-zA-Z\\d_]+)\\s*\\}){1}\\s*=\\s*(\\{\\s*([a-zA-Z\\d_]+)\\s*\\}){1}/";
    private static $sqlInsertPattern = "/\\{([a-zA-Z\\d_]+)+\\}/";

    private static $isCountPattern = "/^\\s*(?i)select\\s+count\\(.+?\\)\\s+.+/";
    public static $isSelectPattern = "/^\\s*(?i)select\\s+.+/";
    private static $isUpdatePattern = "/^\\s*(?i)update\\s+.+/";
    private static $isDeletePattern = "/^\\s*(?i)delete\\s+.+/";
    private static $isInsertPattern = "/^\\s*(?i)insert\\s+.+/";

    public function parseSql()
    {
        $this->sql = preg_replace(array("/\n/", "/\(/", "/\)/", "/\s+/", "/,/"), array(" ", " ( ", " ) ", " ", " , "),
            $this->sql);
        if (preg_match(static::$isInsertPattern, $this->sql)) {
            $this->parseInsertSql();
        } else {
            $this->parseSelectSql();
        }
        $sql = $this->sql;
        $sqlType = ModelBase::SELECT;
        if (preg_match(static::$isCountPattern, $sql)) {
            $sqlType = ModelBase::COUNT;
        } else {
            if (preg_match(static::$isSelectPattern, $sql)) {
                $sqlType = ModelBase::SELECT;
            } else {
                if (preg_match(static::$isUpdatePattern, $sql)) {
                    $sqlType = ModelBase::UPDATE;
                } else {
                    if (preg_match(static::$isDeletePattern, $sql)) {
                        $sqlType = ModelBase::DELETE;
                    } else {
                        if (preg_match(static::$isInsertPattern, $sql)) {
                            $sqlType = ModelBase::INSERT;
                        }
                    }
                }
            }
        }
        $this->sqlType = $sqlType;
        if (empty($this->dbName)) {
            $dsni = "";
            if (!empty($this->parentDbName)) {
                $dsni = $this->parentDbName;
            }
            if (in_array($this->sqlType, [ModelBase::SELECT, ModelBase::COUNT])) {
                $this->dbName = lcfirst($dsni . "Master");
            } else {
                $this->dbName = lcfirst($dsni . "Slave");
            }
        }
    }

    private function parseSelectSql()
    {
        preg_match_all(static::$sqlSelectPattern, $this->sql, $matchs);
        //0:whole  1:field  2:operator  3:prefix  4:paramwho 5:param 6:suffix
        $len = count($matchs[0]);
        $nodes = array();
        if (count($matchs) > 0 && $len > 0) {
            for ($i = 0; $i < $len; $i++) {
                $match = static::getRealMatcher(static::getMatcher($matchs, $i));
                $node = new SqlAnalysisNode();
                $node->whole = $match[0];
                $node->field = $match[1];
                $node->operator = trim($match[2]);
                $node->prefix = $match[3];
                $node->paramWhole = $match[4];
                $node->param = trim($match[5]);
                $node->suffix = $match[6];
                if (\Qe\Core\Utils::endsWith($node->whole, ")")
                    && (!$node->prefix || !\Qe\Core\Utils::beginsWith($node->prefix, "("))
                ) {
                    $node->whole = preg_replace("/\\)$/", "", $node->whole);
                    $node->suffix = preg_replace("/\\)$/", "", $node->suffix);
                }
                $nodes[] = $node;
            }
        }
        $this->group = $nodes;
        $this->parseAndOrSql();
    }

    private function parseAndOrSql()
    {
        preg_match_all(static::$sqlAndOrPattern, $this->sql, $matchs);
        $len = count($matchs[0]);
        $nodes = array();
        if (count($matchs) > 0 && $len > 0) {
            for ($i = 0; $i < $len; $i++) {
                $match = static::getMatcher($matchs, $i);
                $node = new SqlAndOrNode();
                $node->whole = $match[0];
                $node->field = $match[1];
                $node->operator = trim($match[2]);
                $node->paramWhole1 = $match[3];
                $node->param1 = $match[4];
                $node->paramWhole2 = trim($match[5]);
                $node->param2 = $match[6];
                $nodes[] = $node;
            }
        }
        $this->andOrNodes = $nodes;
    }

    private static function getMatcher($match, $i)
    {
        $arr = [
            $match[0][$i],
            $match[1][$i],
            trim($match[2][$i]),
            $match[3][$i],
            $match[4][$i],
            trim($match[5][$i]),
            $match[6][$i]
        ];
        return $arr;
    }

    private static function getRealMatcher($match)
    {
        $field = $match[0];
        if (empty($field)) {
            return $match;
        }
        $up = strtoupper($field);
        $keys = [" WHERE ", " AND ", " OR "];
        $ismod = false;
        for ($i = 0; $i < count($keys); $i++) {
            $pos = \Qe\Core\Utils::lastIndexOf($up, $keys[$i]);
            if ($pos > -1) {
                $ismod = true;
                $up = substr($up, $pos + strlen($keys[$i]));
                $field = substr($field, $pos + strlen($keys[$i]));
            }
        }
        if ($ismod) {
            preg_match_all(static::$sqlSelectPattern, $field, $matchs);
            $len = count($matchs[0]);
            if (count($matchs) > 0 && $len > 0) {
                return static::getMatcher($matchs, 0);
            }
        }
        return $match;
    }

    private function parseInsertSql()
    {
        $sql = $this->sql;
        $start = strpos($sql, "(") + 1;
        $end = strpos($sql, ")");
        $ffs = explode(",", substr($sql, $start, $end - $start));
        $start = strpos($sql, "(", $start) + 1;
        $end = strpos($sql, ")", $end + 1);
        $vvs = explode(",", substr($sql, $start, $end - $start));
        $list = [];
        $ffList = [];
        for ($i = 0; $i < count($ffs); $i++) {
            $start = strpos($vvs[$i], "{");
            $end = strpos($vvs[$i], "}");
            if ($start !== false && $end !== false) {
                $node = new SqlAnalysisNode();
                $node->whole = substr($vvs[$i], $start, $end - $start + 1);
                $node->paramWhole = $node->whole;
                $node->param = substr($vvs[$i], $start + 1, $end - $start - 1);
                $list[] = $node;
                $ffList[] = trim($ffs[$i]);
            }
        }
        $this->group = $list;
        $this->ffList = $ffList;
    }

    private function parseInsertSql_bak()
    {
        preg_match_all(static::$sqlInsertPattern, $this->sql, $match);
        //0:whole  1:field  2:operator  3:prefix  4:paramwho 5:param 6:suffix
        $len = count($match[0]);
        $nodes = array();
        if (count($match) > 0 && $len > 0) {
            for ($i = 0; $i < $len; $i++) {
                $node = new SqlAnalysisNode();
                $node->whole = $match[0][$i];
                $node->paramWhole = $match[0][$i];
                $node->param = $match[1][$i];
                $nodes[] = $node;
            }
        }
        return $nodes;
    }

    public static function getSqlConfig($sqlId)
    {
        list($className, $action) = static::getKeyPair($sqlId);
        return ClassCache::getCache($className)->get($action);
    }


    public static function addSqlConfig(SqlConfig $config)
    {
        Logger::debug($config->id . "配置如下", $config);
        list($className, $action) = static::getKeyPair($config->id);
        ClassCache::getCache($className)->set($action, $config);
    }

    private static function getKeyPair($sqlId)
    {
        $temp = explode("->", trim($sqlId, "\\"));
        return [$temp[0], "sqlConfig-" . $temp[1]];
    }

    public function exec($params = array())
    {
        $params = $this->dealParamIntercepts($params);
        TimeWatcher::label($this->tableName . "生成sql耗时：");
        $data = [];
        if (preg_match(static::$isInsertPattern, $this->sql)) {
            $sql = $this->createInsertSql($params, $data);
        } else {
            $sql = $this->createSelectSql($params, $data);
        }
        TimeWatcher::label($this->tableName . "生成sql耗时：");
        TimeWatcher::label("执行sql：$sql 耗时：");
        $db = Db::getDb($this->dbName);
        if ($this->isTran) {
            $db->begin();
        }
        $ret = "";
        if (preg_match(static::$isCountPattern, $sql)) {
            $ret = $db->count($sql, $data);
        } else {
            if (preg_match(static::$isSelectPattern, $sql)) {
                $ret = $db->select($sql, $data);
            } else {
                if (preg_match(static::$isUpdatePattern, $sql)) {
                    $ret = $db->update($sql, $data);
                } else {
                    if (preg_match(static::$isDeletePattern, $sql)) {
                        $ret = $db->delete($sql, $data);
                    } else {
                        if (preg_match(static::$isInsertPattern, $sql)) {
                            $ret = $db->insert($sql, $data);
                        } else {
                            $ret = $db->select($sql, $data);
                        }
                    }
                }
            }
        }
        if (is_array($ret)) {
            $params['pn'] = null;
            $ret = $this->dealFunIntercept($ret);
            $ret = $this->dealSqlIntercepts($ret, $params);
            $ret = $this->dealReturnType($ret);
        }

        if ($this->isTran) {
            $db->commit();
        }
        TimeWatcher::label("执行sql：$sql 耗时：");
        return $ret;
    }

    private function dealParamIntercepts($params = [])
    {
        Logger::debug("有【" . count($this->paramIntercepts) . "】个paramIntercepts需要处理");
        if (count($this->paramIntercepts) > 0) {
            foreach ($this->paramIntercepts as $key => $interceptClass) {
                /**
                 * @var \Qe\Core\Mvc\ParameterInterceptor
                 */
                $intercept = new $interceptClass();
                $intercept->intercept($key, $params);
            }
        }
        return $params;
    }

    private function createInsertSql($params = array(), &$data)
    {
        $sql = $this->sql;
        $ffList = $this->ffList;
        $index = 0;
        foreach ($this->group as $node) {
            if (isset($params[$node->param])) {
                $sql = str_replace($node->whole, ":" . $node->param, $sql);
                $data[$node->param] = $params[$node->param];
            } else {
                $sql = str_replace($node->whole, "", $sql);
                $sql = str_replace($ffList[$index], "", $sql);
            }
            $index++;
        }
        $sql = preg_replace(["/\s\s,/", "/,\s\s/", "/\(,/", "/,\)/"], ["", "", "(", ")"], $sql);
        return $sql;
    }

    private function toStr($val)
    {
        if (is_array($val) || is_object($val)) {
            return json_encode($val);
        }
        return $val;
    }

    private function createSelectSql($params = array(), &$data)
    {
        $sql = $this->sql;
        foreach ($this->group as $node) {
            if (array_key_exists($node->param, $params)) {
                if ($node->isBy()) {
                    if ($this->sqlType === ModelBase::SELECT) {
                        $result = str_replace($node->paramWhole,
                            preg_replace("/'/", "\\'", $params[$node->param]),
                            $node->whole);
                    } else {
                        $result = " ";
                    }
                } else {
                    if ($node->isIn() || $node->isNotIn()) {
                        $val = $params[$node->param];
                        if (is_string($val)) {
                            $val = explode(",", $val);
                        }
                        if (!is_null($val) && count($val) > 0) {
                            for ($i = 0; $i < count($val); $i++) {
                                $_key = $node->param . "_" . $i;
                                $p[] = ":" . $_key;
                                $data[$_key] = $val[$i];
                            }
                            $p = implode(",", $p);
                            if (empty($node->prefix)) {
                                $p = "(" . $p;
                            }
                            if (empty($node->suffix)) {
                                $p = $p . ")";
                            }
                            $result = str_replace($node->paramWhole, $p, $node->whole);
                        } else {
                            $result = " 1=1 ";
                        }
                    } else {
                        if ($node->isLike()) {
                            $result = str_replace($node->prefix . $node->paramWhole . $node->suffix,
                                ":" . $node->param, $node->whole);
                            $data[$node->param] = $this->toStr(str_replace("'", "",
                                    $node->prefix) . $params[$node->param] . str_replace("'", "", $node->suffix));
                        } else {
                            $result = str_replace($node->paramWhole, ":" . $node->param, $node->whole);
                            $data[$node->param] = $this->toStr($params[$node->param]);
                        }
                    }
                }
            } else {
                $result = " 1=1 ";
            }
            $sql = str_replace($node->whole, " " . $result . " ", $sql);
        }

        foreach ($this->andOrNodes as $node) {
            $temp = " 1=1 ";
            if (array_key_exists($node->param1, $params) && array_key_exists($node->param2, $params)) {
                $temp = str_replace($node->paramWhole1, ":" . $node->param1, $node->whole);
                $temp = str_replace($node->paramWhole2, ":" . $node->param2, $temp);
            }
            $sql = str_replace($node->whole, " " . $temp . " ", $sql);
        }


        if ($this->sqlType === ModelBase::SELECT
            && array_key_exists("pn", $params) && array_key_exists("ps", $params)
            && $params["pn"] != null && $params["ps"] != null
        ) {
            $pn = intval($params["pn"]);
            $ps = intval($params["ps"]);
            $start = $ps * ($pn - 1);
            $sql = $sql . " limit $start , $ps";
        }
        $search = array(
            "/(?i)1=1\s*or\s+/",
            "/\(+\s*1=1\s*\)/",
            "/(?i)and\s*1=1\s+/",
            "/(?i)or\s*1=1\s+/",
            "/\(+\s*1=1\s*\)/",
            "/(?i)count\s*\([^\)]+\s*\)/",
            "/,\s*1=1\s+/",
            "/1=1\s*,\s+/",
            "/\s+/",
            "/\s+\(\s+\)/",
            "/\s+\(\s+/"
        );
        $replace = array(" ", " 1=1 ", " ", " ", " 1=1 ", "  count(1)  ", "  ", " ", " ", "()", "(");
        return preg_replace($search, $replace, $sql);
    }

    private function dealFunIntercept(&$ret)
    {
        Logger::debug("有【" . count($this->funIntercepts) . "】个funIntercept需要处理");
        if (count($this->funIntercepts) > 0 && count($ret) > 0) {
            foreach ($ret as $key => &$data) {
                foreach ($this->funIntercepts as $fun) {
                    $intercept = new $fun[1]();
                    $intercept->intercept($fun[0], $data, $this);
                }
            }
        }
        return $ret;
    }

    private function dealReturnType($ret)
    {
        if (count($ret) && $this->returnType != null) {
            return Convert::from($ret)->toList($this->returnType);
        }
        return $ret;
    }

    private function dealSqlIntercepts(&$ret, $params)
    {
        if ($params['isWithRelation'] && count($this->sqlIntercepts) > 0 && count($ret) > 0) {
            foreach ($this->sqlIntercepts as $sc) {
                $ret = $this->dealSqlIntercept($sc, $ret, $params);
            }
        }
        return $ret;
    }

    private function dealSqlIntercept(SqlConfig $sqlConfig, &$ret, $params)
    {
        if (!empty($sqlConfig->refId)) {
            $sqlConfig = static::getSqlConfig($sqlConfig->refId);
        }
        if (empty($sqlConfig->extend)) {
            return $ret;
        }

        $rs = explode("|", $sqlConfig->relationKey);
        $relationKey = $rs[0];
        if ($relationKey == static::$RESULTASINT) {
            $params[$relationKey] = intval($ret);
        } else {
            $params[$relationKey] = Utils::fetchAsArray($ret, $relationKey);
        }
        $ret2 = $sqlConfig->exec($params);
        if (!is_array($ret2) || count($ret2) == 0) {
            return $ret;
        }
        $type = $sqlConfig->extend;
        $mappedBy = Utils::getRealMappBy($ret2[0], $rs[1]);
        return Utils::$type($ret, $ret2, $rs[0], $mappedBy, $sqlConfig->fillKey);
    }
}