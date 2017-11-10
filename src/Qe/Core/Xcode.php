<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-27
 * Time: 22:19
 */

namespace Qe\Core;


use Qe\Core\Mvc\ParameterInterceptor;

class Xcode implements ParameterInterceptor
{
    private static $strBaseNum = "03726915840372691584";
    private static $strBase = "ncxgywpzvarsbdmkhfuqetncxgywpzvarsbdmkhfuqet";
    private static $strBaseN = "d36ch2tf7mvxsruwba8ypnegkq5d36ch2tf7mvxsruwba8ypnegkq5";
    private static $key = 2543.5415412812;
    private static $LEN = 10;

//$type 0 数字，1 字母 ,2 数字字母组合
    public static function encode($_num, $type = 0, $len = 10)
    {
        $sBase = self::$strBaseNum;
        if ($type == 1) $sBase = self::$strBase;
        if ($type == 2) $sBase = self::$strBaseN;
        $other = str_replace(".", "", $_num / static::$key);
        $numLen = strlen($_num);
        $last = substr($_num, strlen($_num) - 1);
        $base = str_split(substr($sBase, $last), 1);
        $last = substr($sBase, $last - 1, 1);
        $begin = substr($sBase, $numLen, 1);
        $value = $begin;
        $nums = str_split($_num, 1);
        foreach ($nums as $s) {
            $value = $value . $base[$s];
        }
        $value = $value . $last;
        $others = str_split(substr($other, strlen($other) - $len + $numLen + 2), 1);
        foreach ($others as $s) {
            $value = $value . $base[$s];
        }
        return $value;
    }

    public static function decode($str, $type = 0, $len = 10)
    {
        if(empty($str))return null;
        $sBase = self::$strBaseNum;
        if ($type == 1) $sBase = self::$strBase;
        if ($type == 2) $sBase = self::$strBaseN;
        $numLen = strpos($sBase, substr($str, 0, 1));
        $value = str_split(substr($str, 1, $numLen), 1);
        $last = substr($str, $numLen + 1, 1);
        $base = substr($sBase, strpos($sBase, $last) + 1);
        $num = "";
        foreach ($value as $k) {
            $num = $num . strpos($base, $k);
        }
        $other = str_replace(".", "", $num / static::$key);
        $others = str_split(substr($other, strlen($other) - $len + $numLen + 2), 1);
        $bases = str_split($base, 1);
        $v = "";
        foreach ($others as $k) {
            $v = $v . $bases[$k];
        }
        if (substr($str, $numLen + 2) == $v)
            return $num;
        return null;
    }

    public function intercept($field, &$map)
    {
        $map[$field] = static::decode($map[$field]);
        return $map[$field];
    }
}
