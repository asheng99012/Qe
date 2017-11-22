<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-27
 * Time: 9:26
 */

namespace Qe\Core;


use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache as DocCache;
use Doctrine\Common\Cache\CacheProvider as CacheProvider;

/**
 * 能跨进程的缓存
 * Class Cache
 * @package Qe\Core
 */
class ClassCache
{
    private $yac;
    private $className;
    private $data;
    private static $instance = [];

    public static function getAllCache()
    {
        return static::$instance;
    }

    /**
     * @param string $className
     * @return ClassCache
     */
    public static function getCache($className = "")
    {
        $className = trim($className, "\\");
        return new ClassCache($className);
//        if (!array_key_exists($className, static::$instance)) {
//            static::$instance[$className] = new ClassCache($className);
//        }
//        return static::$instance[$className];
    }

    private function __construct($className)
    {
        $this->className = $className;
        $this->yac = new \Yac("qe_class_cache");
        $this->init();
    }

    private function init()
    {
        $fl = new \ReflectionClass($this->className);
        $this->data = $this->yac->get($this->className);
        $file = $fl->getFileName();
        $mtime = filemtime($file);
        if ($this->data === false || $this->data['mtime'] < $mtime) {
            $this->data = [
                "file" => $file,
                "mtime" => $mtime,
                "data" => []
            ];
            $this->saveData();
        }
    }

    private function saveData()
    {
        if ($this->data) {
            $this->yac->set($this->className, $this->data);
        }
    }

    public function set($key, $val)
    {
        $this->data['data'][$key] = $val;
        $this->saveData();
    }

    public function get($key, callable $fun = null)
    {
        $val = null;
        if ((!array_key_exists($key, $this->data['data']) || !($val = $this->data['data'][$key])) && $fun !== null) {
            $val = $fun();
            $this->set($key, $val);
        }
        return $val;
    }

    public function isEmpty()
    {
        return count($this->data['data']) == 0;
    }

    public function all()
    {
        return $this->data;
    }

    public function info()
    {
        return $this->yac->info();
    }
}