<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-16
 * Time: 15:23
 */

namespace Qe\Core;

class Convert
{
    private $obj;

    /**
     * @return Convert
     */
    public static function from($_obj)
    {
        return new Convert($_obj);
    }

    private function __construct($_obj)
    {
        $this->obj = $_obj;
    }

    /**
     * @param $className     string className
     * @param $isFilterBlank bool 空值是否需要需要赋值，默认过滤
     * @return $className
     */
    public function to($className, $isFilterBlank = true)
    {
        $data = $this->obj;
        if (is_object($data)) {
            if (get_class($data) == $className) {
                return $data;
            } else {
                $data = get_object_vars($data);
            }
        }
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (!is_array($data)) {
            $data = json_decode(json_encode($data), true);
        }
        $bean = new $className;
        foreach ($data as $key => $val) {
            if (!$isFilterBlank || !Utils::isNullOrEmpty($val)) {
                $bean->$key = $val;
            }
        }
        return $bean;
    }

    public function toList($className)
    {
        $data = $this->obj;
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (!is_array($data)) {
            if (is_object($data)) {
                return array($data);
            } else {
                return array();
            }
        }
        if (count($data) == 0) {
            return array();
        }
        $temp = $data[0];
        if (is_object($temp) && get_class($temp) == $className) {
            return $data;
        }
        $ret = array();
        foreach ($data as $key => $val) {
            $ret[] = static::from($val)->to($className);
        }
        return $ret;

    }
}