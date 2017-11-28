<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-26
 * Time: 19:47
 */

namespace Qe;

use Qe\Core\Proxy;

class ProxyTest extends \TestCase
{
    /**
     * @var \Controller\IndexController
     * @Resource
     */
    public $indexController;


    public function testProxy()
    {
        $db = $this->indexController->getDb();
        $dbname = $this->indexController->dbName;
        $dbuser = $this->indexController->userService->dbUser;
        $this->assertEquals("mysql:host=10.0.75.1;dbname=Laputa;port=3306", $dbname);
        $this->assertEquals("root", $dbuser);
        $this->assertEquals($dbname . ":" . $dbuser, $db);
    }

    public function testDao()
    {
        $humans = $this->indexController->userService->getHuman(["mobile" => "313"]);
        var_dump($humans);
    }
}