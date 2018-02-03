<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-30
 * Time: 10:51
 */

namespace Qe\Core;


class Bootstrap
{
    public static function run()
    {
        try {

            if ("cli" == PHP_SAPI) {
                list($path, $data) = static::cli();
            } else {
                list($path, $data) = static::web();
            }
            TimeWatcher::label($path . " 耗时：");
            \Qe\Core\Mvc\Dispatch::getDispatch()->run(Config::get("app.reoutes"), Config::get("app.filters"), $path,
                $data);
            TimeWatcher::label($path . " 耗时：");
        } catch (\Exception $e) {
            \Qe\Core\Mvc\Dispatch::getDispatch()->handleException($e);
        }
    }

    private static function cli()
    {
        global $argc, $argv;

        $path = $argc > 1 ? $argv[1] : "";
        $data = array();
        if ($argc > 2) {
            parse_str($argv[1], $data);
        }
        return array($path, $data);
    }

    private static function web()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $data = array_merge($_GET, $_POST);
        if (strpos($_SERVER['CONTENT_TYPE'], "json") === false) {
            parse_str(file_get_contents('php://input'), $JSON);
        } else {
            $JSON = json_decode(file_get_contents('php://input'), true);
        }
        $data = array_merge($data, $JSON);
        return array($path, $data);
    }
}
