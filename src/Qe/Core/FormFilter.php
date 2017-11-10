<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-6-2
 * Time: 16:48
 */

namespace Qe\Core;


class FormFilter
{
    public $identifys = array();
    public $rules = array();
    public $params = array();

    private static $instanse = array();

    /**
     * @return FormFilter
     */
    public static function getFormFilter($ruleFile = "")
    {
        if (empty($ruleFile)) $ruleFile = \Qe\Core\Mvc\Dispatch::getDispatch()->getModule();
        if (empty($ruleFile)) $ruleFile = \Config::$formRule;
        if (!array_key_exists($ruleFile, static::$instanse)) {
            $_ruleFile =   $ruleFile;
            if (!file_exists(WEBROOT . $_ruleFile . ".xml"))
                $_ruleFile = \Config::$formRule;
            static::$instanse[$ruleFile] = \Qe\Core\SysCache::getCache()->fetch($_ruleFile);
            if (static::$instanse[$ruleFile] == null)
                static::$instanse[$ruleFile] = new static($_ruleFile);
        }
        return static::$instanse[$ruleFile];
    }

    /**
     * @return array
     */
    public function getIdentifys()
    {
        return $this->identifys;
    }

    /**
     * @var array 需要的字段，默认为全部字段
     * @var array 数据来源，默认为dispatch中的数据
     * @return array 校验后的数据
     */
    public function getFormData($params = array(), &$data = null)
    {
        if ($data == null) {
            $dispatch = \Qe\Core\Mvc\Dispatch::getDispatch();
            $data =& $dispatch->data;
        }
        if (count($params) > 0) {
            $_data = array();
            foreach ($params as $i => $p) $_data[$p] = array_key_exists($p, $data) ? $data[$p] : null;
            $data = $_data;
        }
        $rules = $this->rules;
        $params = $this->params;
        foreach ($data as $key => $val) {
            if (array_key_exists($key, $params)) {
                $formRule = $params[$key];
                $val = empty($val) && !empty($formRule['defaultValue']) ? $formRule['defaultValue'] : $val;
                if (array_key_exists("unescape", $formRule) && $formRule["unescape"] == "false")
                    $val = htmlspecialchars($val);
                if (array_key_exists("class", $formRule))
                    $val = (new $formRule["class"])->intercept($key, $data);
                if (array_key_exists("regxp", $formRule)) {
                    $regxp = $formRule['regxp'];
                    if (array_key_exists($regxp, $rules)) $regxp = $rules[$regxp];
                    if (!empty($val) && !preg_match($regxp, $val)) throw new \Exception("参数【" . ($formRule["desc"] || $key) . "】格式不正确");
                }
                if (array_key_exists("isRequire", $formRule) && $formRule["isRequire"] == "true" && empty($val)) {
                    throw new \Exception("参数【" . ($formRule["desc"] || $key) . "】格式不正确");
                }
                $data[$key] = $val;
            }
        }
        return $data;
    }

    private function __construct($ruleFile)
    {
        $xml = simplexml_load_file(WEBROOT . $ruleFile . ".xml");
        $arr = json_decode(json_encode($xml), true);
        $wrap = \Qe\Core\Wrap::getWrap($arr);
        $temp = $wrap("identifys.identify");
        foreach ($temp() as $identify)
            $this->identifys[] = $identify['@attributes']['name'];
        $temp = $wrap("rules.rule");
        foreach ($temp() as $rule)
            $this->rules[$rule['@attributes']['name']] = "#" . $rule['@attributes']['regExp'] . "#";
        $temp = $wrap("params.param");
        foreach ($temp() as $param)
            $this->params[$param['@attributes']['name']] = $param['@attributes'];

        \Qe\Core\SysCache::getCache()->save($ruleFile, $this);
    }


}