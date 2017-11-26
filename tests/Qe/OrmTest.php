<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-12
 * Time: 20:24
 */

namespace Qe;

use Model\Human;
use Model\User;
use PHPUnit\Framework\TestCase;
use Qe\Core\ClassCache;
use Qe\Core\Orm\TableStruct;

class OrmTest extends TestCase
{
    public function testTable()
    {
        TableStruct::getTableStruct(User::class);
//        var_dump(ClassCache::getAllCache());
        var_dump(ClassCache::getCache(Human::class)->all());

    }

    public function testSql()
    {
        $sql = "SELECT * FROM humans WHERE `user_id` IN(:id_0,:id_1,:id_2,:id_3 ) ";
        $params = ["id_0" => "'1'", "id_1" => "'2'", "id_2" => "'2'", "id_3" => "'2'"];
        $users = Core\Db\Db::getDb()->select($sql, $params);
        var_dump($users);
    }

    public function testtt()
    {
        $sql="SELECT * FROM humans WHERE dicint(:cCc) and `user_id` IN(:id_0,:id_1,:id_2,:id_3 )";
        preg_match_all("/:([\w]+)/", $sql, $list);
        echo $sql;
    }

    public function testsqlTest()
    {
        $user = new User();
//        $user->avatar = "ddddd";
//        $user->name = "zhengjiansheng";
//        $user->mobile = "1510112";
        $user->ids = "1,2,3,4";
        $users = $user->select();
        /**
         * @var \Model\Human
         */
        $human = $users[0]->human();
        $uu = $human->UserInfo;
        $uu = $human->UserInfo();
        echo $users;

    }

}
