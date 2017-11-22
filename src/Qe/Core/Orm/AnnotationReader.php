<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-18
 * Time: 12:37
 */

namespace Qe\Core\Orm;


use Qe\Core\Convert;

class AnnotationReader
{
    private static $pattern = "/@([\w\d_]+)(\s*(\(){0,1}\s*(.+)\s*(\)){0,1})?/";

    public static function getAnnotation($docComment)
    {
        if (strpos($docComment, "@") === false) return null;
        $_docs = explode("\n", $docComment);
        $docs = [];
        foreach ($_docs as $line) {
            if (strpos($docComment, "@") !== false) {
                $temp = explode("@", $line);
                foreach($temp as $index=>$item){
                    if($index>0)$docs[]=" @".$item;
                }
            }
        }
        $annotations = array();
        foreach ($docs as $line) {
            if (strpos($line, "@") === false) continue;
            preg_match_all(static::$pattern, $line, $list);
            $map = array();
            $shortName = $list[1][0];
            if (strtolower($shortName) == "var") $shortName = "FieldType";
            $annotationName = "Qe\\Core\\Orm\\Annotation\\" . $shortName;
            if (!empty($list[4][0])) {
                $temp=trim($list[2][0]);
                if(\Qe\Core\Utils::beginsWith($temp,"(") && \Qe\Core\Utils::endsWith($temp,")")){
                    $temp=substr($temp,1,strlen($temp)-2);
                }
                $arrs = explode(",", $temp);
                foreach ($arrs as $arr) {
                    $ep = strpos($arr, "=");
                    if ($ep !== false)
                        $map[trim(substr($arr, 0, $ep))] = trim(substr($arr, $ep + 1));
                    else $map["value"] = $map["value"] = trim($arr);
                }
            }
            if (class_exists($annotationName))
                $annotations[$annotationName] = Convert::from($map)->to($annotationName);
        }
        return $annotations;
    }

    public static function getClassAnnotations(\ReflectionClass $class, $annotationName = "")
    {
        $annotations = static::getAnnotation($class->getDocComment());
        if (empty($annotationName)) return $annotations;
        if (array_key_exists($annotationName, $annotations))
            return $annotations[$annotationName];
        return null;
    }

    public static function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        return static::getClassAnnotations($class, $annotationName);
    }

    public static function getPropertyAnnotations(\ReflectionProperty $property, $annotationName = "")
    {
        $annotations = static::getAnnotation($property->getDocComment());
        if (empty($annotationName)) return $annotations;
        if (array_key_exists($annotationName, $annotations))
            return $annotations[$annotationName];
        return null;
    }

    public static function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        return static::getPropertyAnnotations($property, $annotationName);
    }
}
