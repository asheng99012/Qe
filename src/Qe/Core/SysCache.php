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

class SysCache implements DocCache {
    /**
     * @var SysCache
     */
    private static $cache;
    private $firstCache;
    public $secondCache;

    /**
     * @return SysCache
     */
    public static function getCache() {
        if (static::$cache == null) {
            static::$cache = new static();
//            TimeWatcher::label("获取firstCache实例");
            static::$cache->firstCache = new \Doctrine\Common\Cache\ArrayCache();
//            TimeWatcher::label("获取firstCache实例");
//            static::$cache->secondCache = \Config::getCache();
        }
        return static::$cache;
    }

    /**
     * @return CacheProvider
     */
    private function getFirstCache() {
        return $this->firstCache;
    }

    /**
     * @return CacheProvider
     */
    public function getSecondCache() {
        if ($this->secondCache == null) {
            $this->secondCache = \Config::getCache();
        }
        return $this->secondCache;
    }

    /**
     * Fetches an entry from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     *
     * @return mixed The cached data or FALSE, if no cache entry exists for the given id.
     */
    public function fetch($id, $appendPrefix = true) {
        if (\Config::$debug) return null;
        $id = $this->getCacheKey($id, $appendPrefix);
        TimeWatcher::label("获取缓存：【" . $id . "】");
        $ret = $this->getFirstCache()->fetch($id);
        if ($ret === false) {
            $ret = $this->getSecondCache()->fetch($id);
            if ($ret === false) return null;
            $this->getFirstCache()->save($id, $ret);
        }
        TimeWatcher::label("获取缓存：【" . $id . "】");
//        Logger::info("缓存：【" . $id . "】的内容为：", [$ret]);
        return $ret;
    }

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $id The cache id of the entry to check for.
     *
     * @return bool TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    public function contains($id, $appendPrefix = true) {
        $id = $this->getCacheKey($id, $appendPrefix);
        return $this->getFirstCache()->contains($id) || $this->getSecondCache()->contains($id);
    }

    /**
     * Puts data into the cache.
     *
     * If a cache entry with the given id already exists, its data will be replaced.
     *
     * @param string $id The cache id.
     * @param mixed $data The cache entry/data.
     * @param int $lifeTime The lifetime in number of seconds for this cache entry.
     *                         If zero (the default), the entry never expires (although it may be deleted from the cache
     *                         to make place for other entries).
     *
     * @return bool TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    public function save($id, $data, $lifeTime = 0, $appendPrefix = true) {
        if (\Config::$debug) return;
        $id = $this->getCacheKey($id, $appendPrefix);
        $this->getFirstCache()->save($id, $data, $lifeTime);
        $this->getSecondCache()->save($id, $data, $lifeTime);
    }

    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     *
     * @return bool TRUE if the cache entry was successfully deleted, FALSE otherwise.
     *              Deleting a non-existing entry is considered successful.
     */
    public function delete($id, $appendPrefix = true) {
        $id = $this->getCacheKey($id, $appendPrefix);
        $this->getFirstCache()->delete($id);
        if ($this->getSecondCache()->contains($id))
            $this->getSecondCache()->delete($id);
    }

    /**
     * Retrieves cached information from the data store.
     *
     * The server's statistics array has the following values:
     *
     * - <b>hits</b>
     * Number of keys that have been requested and found present.
     *
     * - <b>misses</b>
     * Number of items that have been requested and not found.
     *
     * - <b>uptime</b>
     * Time that the server is running.
     *
     * - <b>memory_usage</b>
     * Memory used by this server to store items.
     *
     * - <b>memory_available</b>
     * Memory allowed to use for storage.
     *
     * @since 2.2
     *
     * @return array|null An associative array with server's statistics if available, NULL otherwise.
     */
    public function getStats() {
        return [$this->getFirstCache()->getStats(), $this->getSecondCache()->getStats(),];
    }

    private function getCacheKey($id, $appendPrefix = true) {
        if (!$appendPrefix) {
            return $id;
        }
        $trace = debug_backtrace();
        for ($i = 0; $i < sizeof($trace); $i++) {
            if (!(isset($trace[$i]['file']) && $trace[$i]['file'] == __FILE__)) {
                isset($trace[$i + 1]) && array_key_exists("class", $trace[$i + 1]) && ($id = $trace[$i + 1]['class'] . "\\" . $id);
                break;
            }
        }
        return $id;
    }
}