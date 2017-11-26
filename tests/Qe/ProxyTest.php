<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-26
 * Time: 19:47
 */

namespace Qe;

use Controller\IndexController;
use PHPUnit\Framework\TestCase;
use Qe\Core\Proxy;

class ProxyTest extends TestCase
{
    /**
     * @var IndexController
     */
    public $indexController;

    public function init()
    {

    }

    public function testProxy()
    {
        $this->indexController = Proxy::handle(IndexController::class);
        $db = $this->indexController->getDb();
        $dbname = $this->indexController->dbName;
        $dbuser = $this->indexController->userService->dbUser;
        $this->assertEquals("mysql:host=10.0.75.1;dbname=Laputa;port=3306", $dbname);
        $this->assertEquals("root", $dbuser);
        $this->assertEquals($dbname . ":" . $dbuser, $db);
    }
}