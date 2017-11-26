<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-11
 * Time: 21:02
 */

namespace Qe;

use Model\Human;
use PHPUnit\Framework\TestCase;
use Qe\Core\Db\Db;
use Qe\Core\Db\SqlBuilder;

class DbTest extends TestCase
{
    public function testSql()
    {

        $data = Db::getDb()->select("SELECT * FROM users LIMIT 1");
        var_dump($data);
    }

    public function testSqlBuilder()
    {
        $sql = "SELECT * FROM humans WHERE `user_id` IN(:id_0,:id_1,:id_2,:id_3 ) ";
        $params = ["id_0" => "1", "id_1" => "2", "id_2" => "2", "id_3" => "2"];
        $builder = SqlBuilder::get()->sql($sql)->returnType(Human::class);
        $users = $builder->exec($params);
        $users = $builder->exec($params);
        $str = json_encode($users);
//        $users = Core\Db\Db::getDb()->select($sql, $params);
        var_dump($users);
    }
}