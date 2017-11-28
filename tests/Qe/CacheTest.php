<?php

namespace Qe;

/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-11
 * Time: 19:23
 */
use Model\Human;
use Model\Mymodel;
use Model\User;
use PHPUnit\Framework\TestCase;
use Qe\Core\Cache;
use Qe\Core\ClassCache;
use Qe\Core\Config;

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
        $mymodel = new Mymodel();
        $mymodel->setName("zjs");
        Cache::getCache()->set("model", $mymodel);
        $mymodel = null;
        $mymodel = Cache::getCache()->get("model");
        $this->assertTrue($mymodel instanceof Mymodel);
        $this->assertEquals("zjs", $mymodel->getName());

        $rfc = new \ReflectionClass(Mymodel::class);
        $name = $rfc->getProperty("name");
        $name->setValue($mymodel, "sss");
        $this->assertEquals("sss", $mymodel->getName());


    }

    public function testModel()
    {
        echo "===========testModel==============";
        $user = new User();
        $user->human = new Human();
        $user->human->address = "这是地址";
        ClassCache::getCache(User::class)->set("val", $user);
        $user = ClassCache::getCache(User::class)->get("val");
        $this->assertEquals("这是地址", $user->human->address);

        $user = ClassCache::getCache(User::class)->get("val");
    }

    public function testConfig()
    {
        $db = Config::get("database.master.username");

        echo $db;
    }


}