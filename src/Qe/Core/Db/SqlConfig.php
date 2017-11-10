<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-18
 * Time: 9:04
 */

namespace Qe\Core\Db;


use Qe\Core\SysCache;
use Qe\Core\Convert;
use Qe\Core\Logger;
use Qe\Core\Orm\Utils;
use Qe\Core\TimeWatcher;

class SqlConfig {
    public static $RESULTASINT = "resultAsInt";
    public $parentId;
    public $id;
    public $dbName;
    public $paramNode;          //可以去掉
    public $isCache = false;    //可以去掉
    public $primaryKey;      //可以去掉
    public $isTran = false;
    public $sql;
    public $sqlIntercepts = array();
    public $relationKey;
    public $fillKey;
    public $refId;
    public $autoPage;
    public $tableName;
    public $isSelect;  //可以去掉
    public $funIntercepts = array();
    public $extend;
    public $group;
    public $returnType;

    private static $sqlSelectPattern = "/\\s+(?i)([`\\.'a-zA-Z\\d_]+){1}\\s*(!=|>=|=<|=>|<=|<|>|=|\\s+like\\s+|\\s+in\\s+|\\s+not\\s+in\\s+|\\s+by\\s+){1}\\s*([\\(%'`\\.'a-zA-Z\\d_\\+\\-\\s]+){0,1}\\s*(\\{\\s*([a-zA-Z\\d_]+)\\s*\\}){1}(\\s*[\\)%']+){0,1}/";
    private static $sqlInsertPattern = "/\\{([a-zA-Z\\d_]+)+\\}/";

    private static $isCountPattern = "/^\\s*(?i)select\\s+count\\(.+?\\)\\s+.+/";
    public static $isSelectPattern = "/^\\s*(?i)select\\s+.+/";
    private static $isUpdatePattern = "/^\\s*(?i)update\\s+.+/";
    private static $isDeletePattern = "/^\\s*(?i)delete\\s+.+/";
    private static $isInsertPattern = "/^\\s*(?i)insert\\s+.+/";

    public function parseSql() {
        $this->sql = preg_replace(array("/\n/", "/\(/", "/\)/", "/\s+/", "/,/"), array(" ", " ( ", " ) ", " ", " , "), $this->sql);
        if (preg_match(static::$isInsertPattern, $this->sql))
            $this->group = $this->parseInsertSql();
        else
            $this->group = $this->parseSelectSql();
    }

    private function parseSelectSql() {
        preg_match_all(static::$sqlSelectPattern, $this->sql, $match);
        //0:whole  1:field  2:operator  3:prefix  4:paramwho 5:param 6:suffix
        $len = count($match[0]);
        $nodes = array();
        if (count($match) > 0 && $len > 0) {
            for ($i = 0; $i < $len; $i++) {
                $node = new SqlAnalysisNode();
                $node->whole = $match[0][$i];
                $node->field = $match[1][$i];
                $node->operator = trim($match[2][$i]);
                $node->prefix = $match[3][$i];
                $node->paramWhole = $match[4][$i];
                $node->param = trim($match[5][$i]);
                $node->suffix = $match[6][$i];
                $nodes[] = $node;
            }
        }
        return $nodes;
    }

    private function parseInsertSql() {
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

    public static function getSqlConfig($sqlId) {
        $ret = SysCache::getCache()->fetch($sqlId);
        if ($ret === false) return null;
        return $ret;
    }

    public static function addSqlConfig($sqlId, SqlConfig $config) {
        Logger::info($sqlId . "配置如下", $config);
        SysCache::getCache()->save($sqlId, $config);
    }


    public function exec($params = array()) {
        TimeWatcher::label($this->tableName."生成sql耗时：");
        $data = array();
        if (preg_match(static::$isInsertPattern, $this->sql))
            $sql = $this->createInsertSql($params, $data);
        else
            $sql = $this->createSelectSql($params, $data);
        TimeWatcher::label($this->tableName."生成sql耗时：");
        TimeWatcher::label("执行sql：$sql 耗时：");
        $db = Db::getDb($this->dbName);
        if ($this->isTran) $db->begin();
        $ret = "";
        if (preg_match(static::$isCountPattern, $sql))
            $ret = $db->count($sql, $data);
        else if (preg_match(static::$isSelectPattern, $sql))
            $ret = $db->select($sql, $data);
        else if (preg_match(static::$isUpdatePattern, $sql))
            $ret = $db->update($sql, $data);
        else if (preg_match(static::$isDeletePattern, $sql))
            $ret = $db->delete($sql, $data);
        else if (preg_match(static::$isInsertPattern, $sql))
            $ret = $db->insert($sql, $data);
        else
            $ret = $db->select($sql, $data);
        if (is_array($ret)) {
            $params['pn'] = null;
            $ret = $this->dealFunIntercept($ret);
            $ret = $this->dealSqlIntercepts($ret, $params);
            $ret = $this->dealReturnType($ret);
        }

        if ($this->isTran) $db->commit();
        TimeWatcher::label("执行sql：$sql 耗时：");
        return $ret;
    }

    private function createInsertSql($params = array(), &$data) {
        $sql = $this->sql;
        foreach ($this->group as $node) {
            if (isset($params[$node->param])) {
                $sql = str_replace($node->whole, "?", $sql);
                $data[] = $this->toStr($params[$node->param]);
            } else {
                $sql = str_replace("`" . $node->param . "`", "", $sql);
                $sql = str_replace($node->whole, "", $sql);
            }
        }
        $sql = preg_replace(["/\s\s,/", "/,\s\s/"], ["", ""], $sql);
        return $sql;
    }

    private function toStr($val) {
        if (is_array($val) || is_object($val)) return json_encode($val);
        return $val;
    }

    private function createSelectSql($params = array(), &$data) {
        $sql = $this->sql;
        foreach ($this->group as $node) {
            if (array_key_exists($node->param, $params)) {
                if ($node->isBy()) {
                    $result = str_replace($node->paramWhole, preg_replace("/'/", "\\'", $params[$node->param]), $node->whole);
                } else if ($node->isIn() || $node->isNotIn()) {
                    $val = $params[$node->param];
                    if (is_string($val)) $val = explode(",", $val);
                    $p = implode(",", array_fill(0, count($val), "?"));
                    foreach ($val as $v) {
//                        $data[] = preg_match('/^\'\d{1,10}\'$/', $v) ? str_replace("'", "", $v) : $v;//$v;
                        $data[] = trim($v, "'");
                    }
                    if(empty($node->prefix))$p="(".$p;
                    if(empty($node->suffix))$p=$p.")";
                    $result = str_replace($node->paramWhole, $p, $node->whole);
//                    $params['pn'] = null;
//                    $result = str_replace($node->paramWhole, $params[$node->param], $node->whole);
                } else if ($node->isLike()) {
                    $result = str_replace($node->prefix . $node->paramWhole . $node->suffix, "?", $node->whole);
                    $data[] = $this->toStr(str_replace("'", "", $node->prefix) . $params[$node->param] . str_replace("'", "", $node->suffix));
                } else {
                    $result = str_replace($node->paramWhole, "?", $node->whole);
                    $data[] = $this->toStr($params[$node->param]);
                }
            } else {
                $result = " 1=1 ";
            }
            $sql = str_replace($node->whole, " " . $result . " ", $sql);
        }
        if (array_key_exists("pn", $params) && array_key_exists("ps", $params) && $params["pn"] != null && $params["ps"] != null) {
            $pn = intval($params["pn"]);
            $ps = intval($params["ps"]);
            $start = $ps * ($pn - 1);
            $sql = $sql . " limit $start , $ps";
        }
        $search = array("/(?i)1=1\s*or\s+/", "/\(+\s*1=1\s*\)/", "/(?i)and\s*1=1\s+/", "/\(+\s*1=1\s*\)/", "/(?i)count\s*\([^\)]+\s*\)/", "/,\s*1=1\s+/", "/1=1\s*,\s+/", "/\s+/", "/\s+\(\s+\)/", "/\s+\(\s+/");
        $replace = array(" ", " 1=1 ", " ", " 1=1 ", "  count(1)  ", "  ", " ", " ", "()", "(");
        return preg_replace($search, $replace, $sql);
    }

    private function dealFunIntercept(&$ret) {
        Logger::info("有【" . count($this->funIntercepts) . "】个funIntercept需要处理");
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

    private function dealReturnType($ret) {
        if (count($ret) && $this->returnType != null) {
            return Convert::from($ret)->toList($this->returnType);
        }
        return $ret;
    }

    private function dealSqlIntercepts(&$ret, $params) {
        if (count($this->sqlIntercepts) > 0 && count($ret) > 0) {
            foreach ($this->sqlIntercepts as $sc) {
                $ret = $this->dealSqlIntercept($sc, $ret, $params);
            }
        }
        return $ret;
    }

    private function dealSqlIntercept(SqlConfig $sqlConfig, &$ret, $params) {
        if (!empty($sqlConfig->refId)) $sqlConfig = static::getSqlConfig($sqlConfig->refId);
        if (empty($sqlConfig->extend)) return $ret;

        $rs = explode("|", $sqlConfig->relationKey);
        $relationKey = $rs[0];
        if ($relationKey == static::$RESULTASINT)
            $params[$relationKey] = intval($ret);
        else $params[$relationKey] = Utils::fetchAsSqlIn($ret, $relationKey);
        $ret2 = $sqlConfig->exec($params);
        if (!is_array($ret2) || count($ret2) == 0) return $ret;
        $type = $sqlConfig->extend;
        return Utils::$type($ret, $ret2, $rs[0], $rs[1], $sqlConfig->fillKey);
    }
}