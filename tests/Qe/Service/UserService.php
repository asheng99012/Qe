<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-26
 * Time: 19:41
 */

namespace Service;


class UserService
{
    /**
     * @var string
     * @Config(database.database.laputaMaster3.username)
     */
    public $dbUser;

    /**
     * @var \Dao\HumanDao
     * @Resource
     */
    public $humanDao;

    public function getHuman($param)
    {
        return $this->humanDao->getHumans($param);
    }
}