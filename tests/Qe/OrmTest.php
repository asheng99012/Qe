<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-12
 * Time: 20:24
 */

namespace Qe;

use PHPUnit\Framework\TestCase;
use Qe\Core\ClassCache;
use Qe\Core\Orm\TableStruct;

class OrmTest extends TestCase
{
    public function testTable()
    {
//        TableStruct::getTableStruct(User::class);
//        var_dump(ClassCache::getAllCache());
//        var_dump(ClassCache::getCache(Human::class)->all());
        $className="Model\\User";
        var_dump(new $className);
    }

}