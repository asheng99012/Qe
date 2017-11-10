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

class Cache extends SysCache
{
    /**
     * @var Cache
     */
    public static $cache;

    /**
     * @return CacheProvider
     */
    public function getSecondCache()
    {
        if ($this->secondCache == null) {
            $this->secondCache = \Config::getCache();
        }

        return $this->secondCache;
    }

}