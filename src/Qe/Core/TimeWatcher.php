<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-17
 * Time: 9:37
 */

namespace Qe\Core;


class TimeWatcher
{
    private static $labels = array();

    public static function repeat($msg, $fun, $times = 10000)
    {
        Logger::info("TimeWatcher:[$msg]:start");
        $start = microtime(true);
        for ($i = 1; $i <= $times; $i++) {
            call_user_func($fun);
        }
        Logger::info("TimeWatcher:[$msg]:end:" . (microtime(true) - $start));
    }

    public static function label($_label)
    {
        if (array_key_exists($_label, static::$labels)) {
            Logger::info("TimeWatcher:[$_label] 耗时：" . (microtime(true) - static::$labels[$_label]));
        }
        static::$labels[$_label] = microtime(true);
    }
}