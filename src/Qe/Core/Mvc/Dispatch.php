<?php

/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-29
 * Time: 15:10
 */

namespace Qe\Core\Mvc;

use Qe\Core\Cache;
use Qe\Core\ClassCache;
use Qe\Core\Config;
use Qe\Core\Db\Db;
use Qe\Core\Proxy;
use Qe\Core\Convert;
use Qe\Core\Logger;
use Qe\Core\ApiResult;
use Qe\Core\TimeWatcher;
use Qe\Core\Utils;

class Dispatch
{
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
    /**
     * @var \Qe\Core\FileCache
     */
    private $fCache;

    private function __construct()
    {
        $this->uuidCookieKey = Config::get("app.uuidCookieKey") ?? "uuid";
        $this->getUUID();
        set_exception_handler(array($this, 'handleException'));
        set_error_handler(array($this, 'handleError'));
        register_shutdown_function(array($this, 'fatalErrorHandler'));
    }

    /**
     * @return Dispatch
     */
    public static function getDispatch()
    {
        if (static::$dispath === null) {
            static::$dispath = new static();
        }
        return static::$dispath;
    }

    public function run($routes, $filter, $path, $data)
    {
        ob_start();
        $this->routes = $routes;
        preg_match('/([^\.]*)(\.(htm|html|json|csv))?/', $path, $matches);
        $this->path = $matches[1];
        if (count($matches) > 3) {
            $this->type = $matches[3];
        }
        $this->data = $data;
        $this->filter = $filter;
        $this->execute();
        ob_end_flush();
    }

    /**
     * @return \Qe\Core\FileCache
     */
    private function getfCache()
    {
        if (!$this->fCache) {
            $this->fCache = Cache::dependFile(ROOT . "/config/app.php");
        }
        return $this->fCache;
    }

    private function getMachedFilter()
    {
        $filters = $this->getfCache()->get($this->path);
        if (!$filters) {
            foreach ($this->filter as $key => $filter) {
                $key = trim($key);
                if (empty($key) || $key === "*" || $key === $this->path || preg_match($key, $this->path, $matches)) {
                    if (!is_array($filter)) {
                        $filter = array($filter);
                    }
                    foreach ($filter as $f) {
                        $filters[] = $f;
                    }
                }

            }
            $this->getfCache()->set($this->path, $filters);
        }
        return $filters;
    }

    private function execute()
    {
        $filters = $this->getMachedFilter();
        $len = count($filters);
        for ($i = 0; $i < $len; $i++) {
            $filters[$i] = Proxy::handle($filters[$i]);
            if ($filters[$i]->beforeHandle($this) === false) {
                return;
            }
        }
        $view = $this->dealView($this->executeAction());
        for ($i = $len - 1; $i >= 0; $i--) {
            $filters[$i]->afterHandle($this);
        }
        if (!empty($view)) {
            $view->display();
        }
        for ($i = $len - 1; $i >= 0; $i--) {
            $filters[$i]->afterCompletion($this);
        }

    }

    /**
     * @return View
     */
    private function dealView($view)
    {
        if (is_null($view)) {
            return null;
        }
        if (!$view instanceof View) {
            $view = new HtmlView($view);
        }
        if (($this->type === "json" || (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest")) && $view instanceof HtmlView) {
            $view = new JsonView($view->getModel());
        }
        $this->view = $view;
        return $view;
    }

    private function executeAction()
    {
        list($params, $route, $actionName) = $this->route();
        $temps = explode("@", $actionName);
//        $class = new $temps[0];
        $class = Proxy::handle($temps[0]);
        $ret = $class->beforeExecute();
        if ($ret === false) {
            return;
        }

        if (count($params) === 0) {
            $argType = ClassCache::getCache($temps[0])->get($actionName);
            if (!$argType) {
                $method = new \ReflectionMethod($class->getTarget(), $temps[1]);
                $args = $method->getParameters();
                $argType = [];
                array_map(function (\ReflectionParameter $parameter) use (&$argType) {
                    $argType[$parameter->getName()] = [];
                    if (!empty($parameter->getClass())) {
                        $argType[$parameter->getName()]["class"] = $parameter->getClass()->getName();
                    }
                    if ($parameter->isDefaultValueAvailable()) {
                        $argType[$parameter->getName()]["defaultValue"] = $parameter->getDefaultValue();
                    }
                }, $args);
                ClassCache::getCache($temps[0])->set($actionName, $argType);
            }
            array_map(function ($key) use (&$params, $argType) {
                if (!empty($argType[$key]['class'])) {
                    if (array_key_exists($key, $this->data)) {
                        $params[] = Convert::from($this->data[$key])->to($argType[$key]['class']);
                    } else {
                        $params[] = Convert::from($this->data)->to($argType[$key]['class']);
                    }
                } else {
                    if (array_key_exists($key, $this->data)) {
                        $params[] = $this->data[$key];
                    } else {
                        if (!empty($argType[$key]['defaultValue'])) {
                            $params[] = $argType[$key]['defaultValue'];
                        }
                    }
                }
            }, array_keys($argType));
        }
        TimeWatcher::label($actionName . " 耗时：");

//        $ret = call_user_func_array(array($class, $temps[1]), $params);
        $ret = call_user_func_array(array($class, $temps[1]), $params);
        TimeWatcher::label($actionName . " 耗时：");
        $class->afterExecute();
        return $ret;
    }

    public function forward($path)
    {
        $this->path = $path;
        return $this->executeAction();
    }

    private function route()
    {
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
            if (!$route) {
                continue;
            } // Skip homepage route
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
        $ret = $this->autoRoute();
        return $ret;
    }

    private function autoRoute()
    {
        $path = $this->path;
        $path = trim($path, '/');
        $temp = explode("/", $path);
        if (count($temp) === 1) {
            $path = $path . "/index";
        }
        $paths = explode("/", $path);
        $actionName = "";
        $params = [];
        do {
            if (count($paths) < 2) {
                return [[], $path, $this->routes['404']];
            }
            !empty($actionName) && array_unshift($params, $actionName);
            $actionName = array_pop($paths);
            $className = "\\Controller\\" . implode("\\", array_map("ucfirst", $paths)) . "Controller";
        } while (!(class_exists($className) && method_exists(new $className(), $actionName)));


        return array($params, $path, $className . "@" . $actionName);
    }

    public function handleException(\Throwable $e)
    {

        Db::rollBackGlobalTran();
        Logger::error($e->getMessage(), $e);
        $code = $e->getCode();
        if ($code === 0) {
            $code = 1;
        }
        $temps = explode("@", $this->routes["error"]);
        $view = $this->dealView(call_user_func_array(
            [Proxy::handle($temps[0]), $temps[1]],
            [$e]));
        $view->display();
    }

    public function fatalErrorHandler()
    {
        $e = error_get_last();
        if (!empty($e['type'])) {
            $this->handleException($e);
        }
    }

    public function handleError($code, $message, $file = '', $line = 0, $context = array())
    {
        throw new \ErrorException($message, 1, $code, $file, $line);
    }

    public function redirect($path)
    {
        ob_end_clean();
        header("Location: $path");
        exit;
    }

    public function getUUID()
    {
        if (empty($this->uuid)) {
            if (!empty($this->data[$this->uuidCookieKey])) {
                $this->uuid = $this->data[$this->uuidCookieKey];
                setcookie($this->uuidCookieKey, $this->uuid, time() + 31104000, "/");
            } else {
                if (!empty($_COOKIE[$this->uuidCookieKey])) {
                    $this->uuid = $_COOKIE[$this->uuidCookieKey];
                } else {
                    $this->uuid = Utils::createUUID();
                    setcookie($this->uuidCookieKey, $this->uuid, time() + 31104000, "/");
                }
            }
        }
        return $this->uuid;
    }

}
