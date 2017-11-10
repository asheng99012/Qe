<?php

/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-29
 * Time: 15:10
 */
namespace Qe\Core\Mvc;

use Qe\Core\Db\Db;
use Qe\Core\SysCache;
use Qe\Core\Convert;
use Qe\Core\Logger;
use Qe\Core\ApiResult;
use Qe\Core\TimeWatcher;
use Qe\Core\Utils;

class Dispatch {
    private $uuid;
    private $uuidCookieKey = "JUFANRID";
    public $routes;
    public $path;
    public $type = "html";
    public $module;
    public $view;
    /**
     * @var array
     */
    public $data;
    public $filter;
    private static $dispath;

    private function __construct() {
        set_exception_handler(array($this, 'handleException'));
        set_error_handler(array($this, 'handleError'));
        register_shutdown_function(array($this, 'fatalErrorHandler'));
        //初始化uuid
        $this->getUUID();
    }

    public function getModule() {
        if (empty($this->module) && !empty($this->path)) {
            preg_match('/^\/([^\.]+)\//', $this->path, $matches);
            $this->module = $matches[1];
        }
        return $this->module;
    }

    /**
     * @return Dispatch
     */
    public static function getDispatch() {
        if (static::$dispath == null) {
            static::$dispath = new static();
        }
        return static::$dispath;
    }

    public function run($routes, $filter, $path, $data) {
        ob_start();
        $this->routes = $routes;
        preg_match('/([^\.]*)(\.(htm|html|json|csv))?/', $path, $matches);
        $this->path = $matches[1];
        if (count($matches) > 3) $this->type = $matches[3];
        $this->data = $data;
        $this->filter = $filter;
        $this->execute();
        ob_flush();
        flush();
    }

    private function execute() {

        $filters = array();
        foreach ($this->filter as $key => $filter) {
            $key = trim($key);
            if (empty($key) || $key == "*" || $key == $this->path || preg_match($key, $this->path, $matches)) {
                if (!is_array($filter)) $filter = array($filter);
                foreach ($filter as $f) $filters[] = $f;
            }

        }

        $len = count($filters);
        for ($i = 0; $i < $len; $i++) {
            $filters[$i] = new $filters[$i]();
            if ($filters[$i]->beforeHandle($this) === false) return;
        }
        $view = $this->dealView($this->executeAction());
        for ($i = $len - 1; $i >= 0; $i--) {
            $filters[$i]->afterHandle($this);
        }
        if (!empty($view)) $view->display();
        for ($i = $len - 1; $i >= 0; $i--) {
            $filters[$i]->afterCompletion($this);
        }

    }

    /**
     * @return View
     */
    private function dealView($view) {
        if (is_null($view)) return null;
        if (!$view instanceof View)
            $view = new HtmlView($view);
        if (($this->type == "json" || (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest")) && $view instanceof HtmlView)
            $view = new JsonView($view->getModel());
        $this->view = $view;
        return $view;
    }

    private function executeAction() {
        list($params, $route, $actionName) = $this->route();
        $temps = explode("@", $actionName);
        $class = new $temps[0];

        if ($class instanceof BaseController) {
            $ret = $class->beforeExecute();
            if ($ret != null) return $ret;
        }

        if (count($params) == 0) {
            $argType = SysCache::getCache()->fetch($actionName);
            if ($argType == null) {
                $method = new \ReflectionMethod($class, $temps[1]);
                $args = $method->getParameters();
                $argType = [];
                array_map(function (\ReflectionParameter $parameter) use (&$argType) {
                    $argType[$parameter->getName()] = [];
                    if (!empty($parameter->getClass()))
                        $argType[$parameter->getName()]["class"] = $parameter->getClass()->getName();
                    if ($parameter->isDefaultValueAvailable())
                        $argType[$parameter->getName()]["defaultValue"] = $parameter->getDefaultValue();
                }, $args);
                SysCache::getCache()->save($actionName, $argType);
            }
            array_map(function ($key) use (&$params, $argType) {
                if (!empty($argType[$key]['class'])) {
                    if (array_key_exists($key, $this->data))
                        $params[] = Convert::from($this->data[$key])->to($argType[$key]['class']);
                    else
                        $params[] = Convert::from($this->data)->to($argType[$key]['class']);
                } else if (array_key_exists($key, $this->data))
                    $params[] = $this->data[$key];
                else if (!empty($argType[$key]['defaultValue']))
                    $params[] = $argType[$key]['defaultValue'];
            }, array_keys($argType));
        }
        TimeWatcher::label($actionName." 耗时：");
        $ret = call_user_func_array(array($class, $temps[1]), $params);
        TimeWatcher::label($actionName." 耗时：");
        if ($class instanceof BaseController) {
            $class->afterExecute();
        }
        return $ret;
    }

    public function forward($path) {
        $this->path = $path;
        return $this->executeAction();
    }

    private function route() {
        $path = $this->path;
        $path = trim($path, '/');
        if ($path === '') {
            return array(array(), '', $this->routes['']);
        }
        // If this is not a valid, safe path (more complex params belong in GET/POST)
        if ($path && !preg_match('/^[\w\-~\/\.]{1,400}$/', $path)) {
            $path = '404';
        }
        foreach ($this->routes as $route => $controller) {
            if (!$route) continue; // Skip homepage route
            // Is this a regex?

            if ($route[0] === '/') {
                if (preg_match($route, $path, $matches)) {
                    $complete = array_shift($matches);
                    // The following code tries to solve:
                    // (Regex) "/^path/(\w+)/" + (Path) "path/word/other" = (Params) array(word, other)
                    // Skip the regex match and continue from there
                    $params = explode('/', trim(mb_substr($path, mb_strlen($complete)), '/'));
                    if ($params[0]) {
                        // Add captured group back into params
                        foreach ($matches as $match) {
                            array_unshift($params, $match);
                        }
                    } else {
                        $params = $matches;
                    }
                    //print dump($params, $matches);
                    return array($params, $complete, $controller);
                }
            } else {
                if ($path === $route) {
                    $params = explode('/', trim(mb_substr($path, mb_strlen($route)), '/'));
                    return array($params, $route, $controller);
                }
            }
        }
        $ret = \Qe\Core\SysCache::getCache()->fetch($path);
        if ($ret == null) {
            $ret = $this->autoRoute();
            \Qe\Core\SysCache::getCache()->save($path, $ret);
        }
        return $ret;
    }

    private function autoRoute_bak() {
        $path = $this->path;
        $path = trim($path, '/');
        $temp = explode("/", $path);
        if (count($temp) == 1) {
            $actionName = "index";
            $_className = ucfirst($temp[0]);
        } else {
            $actionName = array_pop($temp);
            $temp = array_map("ucfirst", $temp);
            $_className = implode("\\", $temp);
        }


        $className = "\\Controller\\" . $_className . "Controller";
        if (class_exists($className) && method_exists(new $className(), $actionName))
            return array(array(), $path, $className . "@" . $actionName);
//        $className = "\\Services\\" . $_className;
//        if (class_exists($className) && method_exists(new $className(), $actionName))
//            return array(array(), $path, $className . "@" . $actionName);
//        $className = "\\Model\\" . $_className;
//        if (class_exists($className) && method_exists(new $className(), $actionName))
//            return array(array(), $path, $className . "@" . $actionName);
        // Controller not found
        return array(array(), $path, $this->routes['404']);
    }

    private function autoRoute_allParam() {
        $path = $this->path;
        $path = trim($path, '/');
        $temp = explode("/", $path);
        if (count($temp) == 1)
            $path = $path . "/index";
        $params = [];
        $paths = explode("/", $path);
        $actionName = "";
        do {
            if (count($paths) < 2)
                return array(array(), $path, $this->routes['404']);
            !empty($actionName) && array_unshift($params, $actionName);
            $actionName = array_pop($paths);
            $className = "\\Controller\\" . implode("\\", array_map("ucfirst", $paths)) . "Controller";
        } while (!(class_exists($className) && method_exists(new $className(), $actionName)));


        return array($params, $path, $className . "@" . $actionName);
    }

    private function autoRoute() {
        $path = $this->path;
        $path = trim($path, '/');
        $temp = explode("/", $path);
        if (count($temp) == 1)
            $path = $path . "/index";
        $paths = explode("/", $path);
        $actionName = "";
        do {
            if (count($paths) < 2)
                return array(array(), $path, $this->routes['404']);

            !empty($actionName) && ($this->data[array_pop($paths)] = $actionName);
            $actionName = array_pop($paths);
            $className = "\\Controller\\" . implode("\\", array_map("ucfirst", $paths)) . "Controller";
        } while (!(class_exists($className) && method_exists(new $className(), $actionName)));


        return array([], $path, $className . "@" . $actionName);
    }

    public function handleException($e) {
        $this->handleError($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine(), [$e]);
    }

    function fatalErrorHandler() {
        $e = error_get_last();
        if (!empty($e['type'])) $this->handleError($e['type'], $e['message'], $e['file'], $e['line'], [$e]);
    }

    public function handleError($code, $message, $file = '', $line = 0, $context = array()) {
        Db::rollBackGlobalTran();
        if (count($context) == 0) $context = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,5);
        if (\Config::$debug) {
            echo("$code : $file : $line :$message <br />");
            Utils::dump($context);
            exit;
        }
        if ($code !== 0) {
            Logger::getLogger("error", false)->error("uuid:" . $this->getUUID() . "  " . substr($file, strlen(ROOT)) . ":[" . $line . "] - " . $message, $context);
        } else {
            Logger::getLogger("error", false)->warn("uuid:" . $this->getUUID() . "  " . substr($file, strlen(ROOT)) . ":[" . $line . "] - " . $message, $context);
        }

        if ($code === 0) $code = 1;
        $temps = explode("@", $this->routes["error"]);
        $view = $this->dealView(call_user_func_array(array(new $temps[0], $temps[1]), array(ApiResult::error($message, $code))));
        $view->display();
    }

    public function redirect($path) {
        ob_end_clean();
        header("Location: $path");
        exit;
    }

    public function getUUID() {
        if (empty($this->uuid)) {
            if (!empty($this->data[$this->uuidCookieKey])) {
                $this->uuid = $this->data[$this->uuidCookieKey];
                setcookie($this->uuidCookieKey, $this->uuid, time() + 31104000, "/");
            } else if (!empty($_COOKIE[$this->uuidCookieKey])) {
                $this->uuid = $_COOKIE[$this->uuidCookieKey];
            } else {
                $this->uuid = Utils::createUUID();
                setcookie($this->uuidCookieKey, $this->uuid, time() + 31104000, "/");
            }
        }
        return $this->uuid;
    }

}


