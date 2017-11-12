<?php
namespace Qe;

/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-11
 * Time: 19:23
 */
use PHPUnit\Framework\TestCase;
use Qe\Core\Cache;
use Qe\Core\ClassCache;

class CacheTest extends TestCase
{
    public function testEmpty()
    {
        echo "=====testEmpty======";
        $stack = [];
        $this->assertEmpty($stack);

        return $stack;
    }

    /**
     * @depends testEmpty
     */
    public function ddtestTime()
    {
        $cache = Cache::getCache();
        $cache->get("zjs", function () {
            return ["q", "v"];
        });
        $cache->get("zjs");
    }

    public function testClassCache()
    {
        $cache = ClassCache::getCache(ClassCache::class);
        var_dump($cache->isEmpty());
//
//        $cache->set("zjs1", ["a" => "aaa"]);
//
        var_dump(ClassCache::getCache(ClassCache::class)->set("zjs2", ["b" => "bbb"]));
        var_dump($cache->get("zjs2"));
        var_dump($cache->isEmpty());

    }


}