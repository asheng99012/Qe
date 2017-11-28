<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-26
 * Time: 19:40
 */

namespace Controller;


use Qe\Core\Mvc\BaseController;

class IndexController extends BaseController
{
    /**
     * @var string
     * @Config(database.database.laputaMaster.dns)
     */
    public $dbName;

    /**
     * @var \Service\UserService
     * @Resource
     */
    public $userService;

    public function getDb()
    {
        return $this->dbName . ":" . $this->userService->dbUser;
    }

    public function zjst($id, $str)
    {
        return $this->toJson([$id, $str, 1, 2, 3, $this->getDb(),
            $this->userService->getHuman(["mobile"=>"313"])]);
    }

}