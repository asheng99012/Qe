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
class Cache
{
    private $yac;
    private static $data = [];

    public static function getCache($prefix = "")
    {
        return new Cache($prefix);
    }

    /**
     * @param $file
     * @return FileCache
     */
    public static function dependFile($file)
    {
        return new FileCache($file);
    }

    private function __construct($prefix)
    {
        $this->yac = new \Yac("qecache:" . $prefix);
    }

    public function set($key, $val)
    {
        $this->yac->set($key, $val);
    }

    public function get($key, callable $fun = null)
    {
        $val = $this->yac->get($key);
        if ($val === false && $fun !== null) {
            $val = $fun();
            $this->set($key, $val);
        }
        return $val;
    }


}