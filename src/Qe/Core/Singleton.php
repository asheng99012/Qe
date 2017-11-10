<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2016-11-10
 * Time: 15:42
 */

namespace Qe\Core;


/**
 * 单例模式
 * Class Singleton
 * @package Qe\Core
 */
class Singleton {
    private static $singleton;

    public function __construct($param = []) {
        foreach ($param as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * 获取单例
     * @param array $param
     * @return static
     */
    public static function getSingleton($param = []) {
        if (empty(static::$singleton))
            static::$singleton = new static($param);
        return static::$singleton;
    }
}