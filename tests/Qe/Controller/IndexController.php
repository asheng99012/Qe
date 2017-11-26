<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-26
 * Time: 19:40
 */

namespace Controller;


class IndexController
{
    /**
     * @var string
     * @Config(database.master.dns)
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

}