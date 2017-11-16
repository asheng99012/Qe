<?php
namespace Qe;

/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-11
 * Time: 19:23
 */
use Model\Human;
use Model\User;
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
        echo "===========testClassCache==============";
        $cache = ClassCache::getCache(ClassCache::class);
        $cache->set("zjs1", ["a" => "aaa"]);
        var_dump(ClassCache::getCache(ClassCache::class)->get("zjs1"));

    }

    public function testModel()
    {
        echo "===========testModel==============";
        $user = new User();
        $user->human=new Human();
        $user->human->address="这是地址";
        ClassCache::getCache(User::class)->set("val", $user);
        $user=ClassCache::getCache(User::class)->get("val");
        $user->name = "zjs123";
    }


}