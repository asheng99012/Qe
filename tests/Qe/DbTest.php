<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-11
 * Time: 21:02
 */

namespace Qe;

use PHPUnit\Framework\TestCase;
use Qe\Core\Db\Db;

class DbTest extends TestCase
{
    public function testSql()
    {
        $data = Db::getDb()->select("select * from users limit 1");
        var_dump($data);
    }
}