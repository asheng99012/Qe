<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-12
 * Time: 20:24
 */

namespace Qe;

use Model\User;
use PHPUnit\Framework\TestCase;
use Qe\Core\ClassCache;
use Qe\Core\Orm\TableStruct;

class OrmTest extends TestCase
{
    public function testTable()
    {
        $table = TableStruct::getTableStruct(User::class);
//        var_dump($table);
        var_dump(ClassCache::getCache(User::class)->all());
    }

}