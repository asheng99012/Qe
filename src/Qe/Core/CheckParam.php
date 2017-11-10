<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2016-10-11
 * Time: 15:28
 */

namespace Qe\Core;


class CheckParam {
    /**
     * 中文
     */
    const  CN = '/^[\u4e00-\u9fa5]+$/';
    /**
     * 手机号
     */
    const mobile = '/^1[3-8]{1}\d{9}$/';
    /**
     * 固定电话号
     */
    const phone = '/^(\d{3,4}-)?\d{7,8}$/';
    /**
     * 只有字母数字包括大小写
     */
    const letter_number = '/^[0-9A-Za-z]+$/';
    /**
     * 只有字母数字下划线包括大小写
     */
    const letter_number_ = '/^[0-9A-Za-z_]+$/';
    /**
     * 验证多个id以','分割的类型，例如：'1,2,3,4,5'
     */
    const ids = '/^[0-9]+(\,[0-9]+)*$/';
    /**
     * 验证多个值以','分割的类型，例如：'aa,222,cc,433,511'
     */
    const values = '/^[0-9A-Za-z_]+(\,[0-9A-Za-z_]+)*$/';
    /**
     * 只可以是数字
     */
    const number = '/^[0-9]+$/';
    /**
     * 只可以是小数
     */
    const float = '/^[0-9]*(\\.?)[0-9]*/';
    /**
     * 邮箱
     */
    const email = '/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/';
    /**
     * 日期
     */
    const date = '/^((((19|20)\d{2})-(0?(1|[3-9])|1[012])-(0?[1-9]|[12]\d|30))|(((19|20)\d{2})-(0?[13578]|1[02])-31)|(((19|20)\d{2})-0?2-(0?[1-9]|1\d|2[0-8]))|((((19|20)([13579][26]|[2468][048]|0[48]))|(2000))-0?2-29))$/';

    /**
     * 验证参数格式
     * @param array $params
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public static function checkParams($params = array(), &$data = array()) {
        foreach ($params as $key => $formRule) {
            if (is_string($formRule)) {
                $key = $formRule;
                $formRule = static::rule();
            }
            $formRule["errMsg"] = Utils::isNullOrEmpty($formRule["errMsg"]) ? ("参数" . $key . "格式不正确") : $formRule["errMsg"];
            $val = array_key_exists($key, $data) ? $data[$key] : null;
            $val = Utils::isNullOrEmpty($val) && !Utils::isNullOrEmpty($formRule['defaultValue']) ? $formRule['defaultValue'] : $val;
            if (array_key_exists("encodeHtml", $formRule) && $formRule["encodeHtml"] == true)
                $val = htmlspecialchars($val);
            if (array_key_exists("fun", $formRule) && !Utils::isNullOrEmpty($formRule["fun"]) && is_callable($formRule["fun"]))
                $val = call_user_func($formRule["fun"], $val, $data);
            if (array_key_exists("isRequire", $formRule) && $formRule["isRequire"] == true && ($val === null || $val === "")) {
                throw new \Exception($formRule["errMsg"]);
            }
            if (!Utils::isNullOrEmpty($val) && array_key_exists("regxp", $formRule) && !Utils::isNullOrEmpty($formRule["regxp"])) {
                $regxp = $formRule['regxp'];
                if(is_callable($regxp)){
                    $ret = call_user_func($regxp, $val, $data);
                    if (!$ret) throw new \Exception($formRule["errMsg"]);
                }else{
                    if (!preg_match($regxp, $val)) throw new \Exception($formRule["errMsg"]);
                }
            }
            $data[$key] = $val;
        }
        return $data;
    }

    /**
     * ["isRequire"=>true,"regxp"=>"#\d+#","defaultValue"=>1,"errMsg"=>"错误信息"]
     * @param boolean $isRequire  是否必传
     * @param string|callable $regxp  要满足的正则表达式
     * @param mixed $defaultValue   默认值
     * @param string $errMsg  当验证不通过时的报错信息
     * @param boolean $encodeHtml  是否需要进行html编码，默认不需要
     * @param callable $fun  用此方法对参数进行处理，此方法接受两个参数 curval,data
     * @return array
     */
    public static function rule($isRequire = false, $regxp =null, $defaultValue = null, $errMsg = "", $encodeHtml = false, $fun = null) {
        return [
                "isRequire" => $isRequire,
                "regxp" => $regxp,
                "defaultValue" => $defaultValue,
                "errMsg" => $errMsg,
                "encodeHtml" => $encodeHtml,
                "fun" => $fun
        ];
    }
}