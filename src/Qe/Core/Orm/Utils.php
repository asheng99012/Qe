<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-27
 * Time: 17:44
 */

namespace Qe\Core\Orm;


class Utils
{
    public static function fetchAsArray($list = array(), $key)
    {
        $keys = array();
        foreach ($list as $map) {
            $keys[$map[$key]] = $map[$key];
        }
        return array_keys($keys);
    }

    public static function fetchAsSqlIn($list, $key)
    {
        return "'" . implode("','", static::fetchAsArray($list, $key)) . "'";
    }

    public static function extend($list1 = array(), $list2 = array(), $self, $mappedBy, $fillKey)
    {
        $data = array();
        foreach ($list2 as $val) {
            $data[$val[$mappedBy]] = $val;
        }
        $ret = array();
        foreach ($list1 as &$val) {
            $ret[] = array_merge($data[$val[$self]], $val);
        }
        return $ret;
    }

    public static function one2One($list1 = array(), $list2 = array(), $self, $mappedBy, $fillKey)
    {
        $data = array();
        foreach ($list2 as $val) {
            $data[$val[$mappedBy]] = $val;
        }
        $ret = array();
        foreach ($list1 as &$val) {
            if (array_key_exists($self, $val) && array_key_exists($val[$self], $data)) {
                $val[$fillKey] = $data[$val[$self]];
            }
            $ret[] = $val;
        }
        return $ret;
    }

    public static function one2Many(&$list1 = array(), $list2 = array(), $self, $mappedBy, $fillKey)
    {
        $data = array();
        foreach ($list2 as $val) {
            $data[$val[$mappedBy]][] = $val;
        }
        $ret = array();
        foreach ($list1 as &$val) {
            $val[$fillKey] = $data[$val[$self]];
            $ret[] = $val;
        }
        return $ret;
    }

    public static function getRealMappBy($model, $mappedBy)
    {
        if ($model instanceof ModelBase) {
            $fcMap = TableStruct::getTableStruct(get_class($model))->fcMap;
            foreach ($fcMap as $key => $val) {
                if ($val === $mappedBy) {
                    return $key;
                }
            }
        }
        return $mappedBy;
    }

}