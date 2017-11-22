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

    public function testSql(){
        $sql="select * from humans where `user_id` in(:id_0,:id_1,:id_2,:id_3 ) ";
        $params=["id_0"=>"'1'","id_1"=>"'2'","id_2"=>"'2'","id_3"=>"'2'"];
        $users=Core\Db\Db::getDb()->select($sql,$params);
        var_dump( $users);
    }

    public function testtt()
    {
        $line = "@Table(masterDbName=master,slaveDbName=slave,tableName = users, primaryKey = id, where = id={id} and `mobile`={mobile} and nickname like '%{name}%' and id in ({ids}) order by id desc)";
        preg_match_all("/@([\w\d_]+)(([\s\(]+)?(.+)([\s\)]+)?)?/", $line, $list);
        echo $line;
    }

    public function testsqlTest()
    {
        $user = new User();
//        $user->avatar = "ddddd";
//        $user->name = "zhengjiansheng";
//        $user->mobile = "1510112";
        $user->ids = "1,2,3,4";
        $users= $user->select();
        echo $users;

    }

}
