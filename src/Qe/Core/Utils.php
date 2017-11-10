<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-9-21
 * Time: 22:10
 */

namespace Qe\Core;

use Qe\Core\Mvc\Dispatch;

class Utils {
    /**
     * 当前请求是否为ajax
     * @return bool
     */
    public static function isAjax() {
        return isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest";
    }

    /**
     * 获取当前url地址，包括域名以及参数
     * @return string
     */
    public static function getCurrentUrl() {
        return "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * 获取当前url地址
     * @return mixed
     */
    public static function getUri() {
        return Dispatch::getDispatch()->path;
    }

    /**
     * @descrpition 判断是否在微信浏览器内
     * @return bool
     */
    public static function isInWechat() {
        if (preg_match('/MicroMessenger/', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }
        return false;
    }

    /**
     * 获取客户端IP地址
     * @return string
     */
    public static function getClientIp() {
        if (getenv('HTTP_CLIENT_IP')) {
            $client_ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $client_ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR')) {
            $client_ip = getenv('REMOTE_ADDR');
        } else {
            $client_ip = $_SERVER['REMOTE_ADDR'];
        }
        return $client_ip;
    }

    /**
     * 获取服务器端IP地址
     * @return string
     */
    public static function getServerIp() {
        if (isset($_SERVER)) {
            if ($_SERVER['SERVER_ADDR']) {
                $server_ip = $_SERVER['SERVER_ADDR'];
            } else {
                $server_ip = $_SERVER['LOCAL_ADDR'];
            }
        } else {
            $server_ip = getenv('SERVER_ADDR');
        }
        return $server_ip;
    }

    /** Json数据格式化
     * @param  Mixed $data 数据
     * @param  String $indent 缩进字符，默认4个空格
     * @return string
     */
    public static function jsonFormat($data, $newline = null) {

        array_walk_recursive($data, array('\Qe\Core\Utils', "jsonFormatProtect"));
        // json encode
        $data = json_encode($data);
        // 将urlencode的内容进行urldecode
        $data = urldecode($data);
        // 缩进处理
        $ret = '';
        $pos = 0;
        $length = strlen($data);
        $indent = '    ';
        $newline = isset($newline) ? $newline : "\n";
        $prevchar = '';
        $outofquotes = true;
        for ($i = 0; $i <= $length; $i++) {
            $char = substr($data, $i, 1);
            if ($char == '"' && $prevchar != '\\') {
                $outofquotes = !$outofquotes;
            } elseif (($char == '}' || $char == ']') && $outofquotes) {
                $ret .= $newline;
                $pos--;
                for ($j = 0; $j < $pos; $j++) {
                    $ret .= $indent;
                }
            }
            $ret .= $char;
            if (($char == ',' || $char == '{' || $char == '[') && $outofquotes) {
                $ret .= $newline;
                if ($char == '{' || $char == '[') {
                    $pos++;
                }
                for ($j = 0; $j < $pos; $j++) {
                    $ret .= $indent;
                }
            }
            $prevchar = $char;
        }
        return $ret;
    }

    /** 将数组元素进行urlencode
     * @param String $val
     */
    public static function jsonFormatProtect(&$val) {
        if (is_object($val)) {
            $arr = get_object_vars($val);
            array_walk_recursive($arr, array('\Qe\Core\Utils', "jsonFormatProtect"));
        } else if ($val !== true && $val !== false && $val !== null) {
            $val = urlencode($val);
        }
    }

    /**
     * 获取UUID
     * @return mixed|string
     */
    public static function createUUID() {
        if (function_exists('com_create_guid')) {
            $uuid = com_create_guid();
            $uuid = str_replace("-", "", $uuid);
        } else {
            mt_srand((double) microtime() * 10000);//optional for php 4.2.0 and up.
            $uuid = strtoupper(md5(uniqid(rand(), true)));
        }
        return $uuid;
    }

    /**
     * 判断是否为空
     * @param $str
     * @return bool
     */
    public static function isNullOrEmpty($str) {
        if (is_null($str))
            return true;
        if (is_string($str) && trim($str) === "")
            return true;
        return false;
    }
    /**
     * 浏览器友好的变量输出
     * @param mixed $var 变量
     * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
     * @param string $label 标签 默认为空
     * @param boolean $strict 是否严谨 默认为true
     * @return void|string
     */
    public static function dump($var, $echo=true, $label=null, $strict=true) {
        $label = ($label === null) ? '' : rtrim($label) . ' ';
        if (!$strict) {
            if (ini_get('html_errors')) {
                $output = print_r($var, true);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            } else {
                $output = $label . print_r($var, true);
            }
        } else {
            ob_start();
            var_dump($var);
            $output = ob_get_clean();
            if (!extension_loaded('xdebug')) {
                $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            }
        }
        if ($echo) {
            echo($output);
            return null;
        }else
            return $output;
    }

    /**
     * 判断是否为手机登录
     * @return bool
     */
    public static function isPhone()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) && preg_match("#Android|WindowsPhone|webOS|iPhone|iPod|BlackBerry#", $_SERVER['HTTP_USER_AGENT']) && !preg_match("#iPad#", $_SERVER['HTTP_USER_AGENT']);
    }
}