<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-12
 * Time: 20:13
 */

namespace Model;

use Qe\Core\Orm\ModelBase;

/**
 * @Table(masterDbName=master,slaveDbName=slave,tableName = humans, primaryKey = id, where = id={id} and `good_num`={good_num} and title like '%{title}%' and status={status})
 */
class Human extends ModelBase
{
    /**
     * 主键
     * @var integer
     */
    public $id;
    /**
     * 用户id
     * @var string
     * @Column(user_id)
     */
    public $userId;
    /**
     * 地址
     * @var string
     */
    public $address;

    /**
     * @var \Model\User
     * @OneToOne(self=userId,mappedBy=userId)
     */
    public $UserInfo;
}