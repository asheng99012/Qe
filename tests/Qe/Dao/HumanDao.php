<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-22
 * Time: 18:56
 */

namespace Dao;


use Model\Human;
use Qe\Core\Db\SqlBuilder;

class HumanDao
{
    public function getHumansById($id)
    {
        return SqlBuilder::get()
            ->sql("SELECT * FROM humans WHERE id=:id")
            ->returnType(Human::class)
            ->exec(["id" => $id]);
    }

    public function getHumans($params = [])
    {
        return SqlBuilder::get()
            ->sql("SELECT * FROM humans WHERE id={id} AND user_id={userId} AND mobile LIKE '%{mobile}%'")
            ->returnType(Human::class)
            ->exec($params);
    }
}