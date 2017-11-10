<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-16
 * Time: 16:10
 */

namespace Qe\Core;

use Qe\Core\Mvc\Dispatch;


class Logger {

    /**
     * @return \Monolog\Logger
     */
    public static function getLogger($className = "", $needOtherMsg = true) {
//        if (empty($className)) {
//            $trace = debug_backtrace();
//            for ($i = 0; $i < sizeof($trace); $i++) {
//                if (!(isset($trace[$i]['file']) && $trace[$i]['file'] == __FILE__)) {
//                    isset($trace[$i + 1]) && array_key_exists("class", $trace[$i + 1]) && ($className = $trace[$i + 1]['class']);
//                    break;
//                }
//            }
//        }
        $log = new \Monolog\Logger($className);
        $handle = \Config::getLoggerHandler($className);
        if ($handle != null) $log->pushHandler($handle);

        $log->pushProcessor(function ($record) use ($className, $needOtherMsg) {
            if ($needOtherMsg)
                $msg = static::getCommonMsg($record['message'], $record['context'], empty($className) ? 5 : 4);
            else
                $msg = $record['message'];
            if ($record['level_name'] == 'ERROR'){
                Mail::sendMail(\Config::$errorToMailer, $record['message'], $msg . "<br />" . static::detailMsg() . "<br /><pre>" . Utils::jsonFormat($record['context']) . "</pre>", 'HTML');
            }
            $record['message'] = $msg;
            return $record;
        });
        return $log;
    }

    public static function detailMsg() {
        $msg = ["url：" . Utils::getCurrentUrl()];
        $msg[] = "http_method：" . array_key_exists('REQUEST_METHOD',$_SERVER) && !empty($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:"";
        $msg[] = "referrer：" . array_key_exists('HTTP_REFERER',$_SERVER) && !empty($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:"";
        $msg[] = "userAgent：" . array_key_exists('HTTP_USER_AGENT',$_SERVER) && !empty($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:"";
        $msg[] = "post：" . Utils::dump($_POST,false);
        $msg[] = "cookie：" . Utils::dump($_COOKIE,false);
        $msg[] = "clientIp：" . Utils::getClientIp();
        $msg[] = "serverIp：" . Utils::getServerIp();
        return implode("<br />", $msg);
    }

    public static function info($msg, $obj = array()) {
        !is_array($obj) && ($obj = [$obj]);
        static::getLogger()->info($msg, $obj);
    }

    public static function debug($msg, $obj = array()) {
        !is_array($obj) && ($obj = [$obj]);
        static::getLogger()->debug($msg, $obj);
    }

    public static function warn($msg, $obj = array()) {
        !is_array($obj) && ($obj = [$obj]);
        static::getLogger()->warn($msg, $obj);
    }

    public static function error($msg, $obj = array()) {
        !is_array($obj) && ($obj = [$obj]);
        static::getLogger("error")->error($msg, $obj);
    }


    private static function getCommonMsg($msg, &$obj = array(), $index = 4) {
        $file = "";
        $line = "";
        if ($obj instanceof \Exception) {
            $file = $obj->getFile();
            $line = $obj->getLine();
        }
        if (is_object($obj)) {
            $class = get_class($obj);
            $ref = new \ReflectionClass($class);
            $_ref['class'] = $class;
            $_ref['file'] = $ref->getFileName();
            $obj->__ref__ = $_ref;
        }
        if (empty($file)) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,9);
            $file = $trace[$index]['file'];
            while(preg_match ( "#SysCache|TimeWatcher|Mail|Logger#", $file)) {
                $index = $index + 1;
                isset($trace[$index]['file']) && ($file = $trace[$index]['file']);
            }
            $line = $trace[$index]['line'];

//            print_r($trace);
//            for ($i = 0; $i < sizeof($trace); $i++) {
//                // if (!(isset($trace[$i]['class']) && isset($trace[$i]['file']) && $trace[$i]['file'] == __FILE__))
//                echo $i;
//                if (isset($trace[$i]['class']) && $trace[$i]['class'] != "Monolog\\Logger" && isset($trace[$i]['file']) && $trace[$i]['file'] != __FILE__) {
//                    $file = $trace[$i]['file'];
//                    $line = $trace[$i]['line'];
//                    break;
//                }
//            }
        }
        return "uuid:[" . Dispatch::getDispatch()->getUUID() . "]  " . substr($file, strlen(ROOT)) . ":[" . $line . "] - " . $msg;
    }


}
