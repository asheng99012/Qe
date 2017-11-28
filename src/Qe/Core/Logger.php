<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-16
 * Time: 16:10
 */

namespace Qe\Core;

class Logger
{
    static $LOG;

    /**
     * @return \Monolog\Logger
     */
    public static function getLogger($className = "log")
    {
        $index = 4;
        if (is_bool($className)) {
            $index = 5;
            $className = "log";
        }
        if (static::$LOG) {
            return static::$LOG;
        }
        $log = new \Monolog\Logger($className);
        $level = Config::get("app.logger.level");
        if ($level) {
            $path = ROOT . "/runtime/logs/$className-" . date("Y-m-d", time()) . ".log";
            $stream = new \Monolog\Handler\StreamHandler($path, $level);
            $log->pushHandler(new \Monolog\Handler\BufferHandler($stream, 10, $level, true, true));
        }
        $handlers = Config::get("app.logger.handlers");
        if ($handlers && count($handlers) > 0) {
            foreach ($handlers as $_handler) {
                $handler = Proxy::handle($_handler);
                $handler->setLevel($level);
                $log->pushHandler($handler->getTarget());
            }
        }
        $log->pushProcessor(function ($record) use ($index) {
            $record['message'] = static::getTraceMsg($record['message'], $record['context'], $index);
            return $record;
        });
        $processors = Config::get("app.logger.processors");
        if ($processors && count($processors) > 0) {
            foreach ($processors as $processor) {
                $processor = Proxy::handle($processor);
                $log->pushProcessor($processor->getTarget());
            }
        }
        static::$LOG = $log;
        return $log;
    }

    public static function detailMsg()
    {
        $msg = ["url：" . Utils::getCurrentUrl()];
        $msg[] = "http_method：" . array_key_exists('REQUEST_METHOD',
            $_SERVER) && !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : "";
        $msg[] = "referrer：" . array_key_exists('HTTP_REFERER',
            $_SERVER) && !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";
        $msg[] = "userAgent：" . array_key_exists('HTTP_USER_AGENT',
            $_SERVER) && !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
        $msg[] = "post：" . Utils::dump($_POST, false);
        $msg[] = "cookie：" . Utils::dump($_COOKIE, false);
        $msg[] = "clientIp：" . Utils::getClientIp();
        $msg[] = "serverIp：" . Utils::getServerIp();
        return implode("<br />", $msg);
    }

    public static function info($msg, $obj = array())
    {
        !is_array($obj) && ($obj = [$obj]);
        static::getLogger(true)->info($msg, $obj);
    }

    public static function debug($msg, $obj = array())
    {
        !is_array($obj) && ($obj = [$obj]);
        static::getLogger(true)->debug($msg, $obj);
    }

    public static function warn($msg, $obj = array())
    {
        !is_array($obj) && ($obj = [$obj]);
        static::getLogger(true)->warn($msg, $obj);
    }

    public static function error($msg, $obj = array())
    {
        if ($obj instanceof \Throwable) {
            $obj = $obj->getTrace();
        } else {
            if (!is_array($obj)) {
                $obj = [$obj];
            }
        }
        static::getLogger(true)->error($msg, $obj);
    }


    private static function getTraceMsg($msg, $obj = array(), $index = 4)
    {
        $file = "";
        $line = "";
        if (is_array($obj) && count($obj) > 0 && array_key_exists(0, $obj)) {
            $obj = $obj[0];
        }
        if ($obj instanceof \Exception) {
            $file = $obj->getFile();
            $line = $obj->getLine();
        }

        if (empty($file)) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $index + 1)[$index];
            $file = $trace['file'];
            $line = $trace['line'];
        }
        return substr($file, strlen(ROOT)) . ":[" . $line . "] - " . $msg;
    }


}
