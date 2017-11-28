<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-26
 * Time: 19:40
 */

namespace Controller;


use PHPUnit\Runner\Exception;
use Qe\Core\ApiResult;
use Qe\Core\Mvc\BaseController;
use Qe\Core\Mvc\Dispatch;

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

    public function notFound()
    {
        echo "not found " . Dispatch::getDispatch()->path;
        exit;
    }

    public function error(\Throwable $e)
    {
        return $this->toJson([
            "msg" => $e->getMessage(),
            "file" => $e->getFile() . ":" . $e->getLine(),
            "trace" => $e->getTrace()
        ]);
    }

    public function getDb()
    {
        return $this->dbName . ":" . $this->userService->dbUser;
    }

    public function throwe()
    {
        throw  new Exception("这是测试异常");
    }

    public function zjst($id, $str)
    {
        return $this->toJson([
            $id,
            $str,
            1,
            2,
            3,
            $this->getDb(),
            $this->userService->getHuman(["mobile" => "313"])
        ]);
    }

}