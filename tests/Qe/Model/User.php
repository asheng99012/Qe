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
 * @Table(masterDbName=master,slaveDbName=slave,tableName = users, primaryKey = id, where = id={id} and `mobile`={mobile} and nickname like '%{name}%')
 */
class User extends ModelBase
{
    /**
     * 主键
     * @var integer
     */
    public $id;
    /**
     * 手机号
     * @var string
     */
    public $mobile;
    /**
     * 昵称
     * @var string
     * @Column(nickname)
     */
    public $name;
    /**
     * 头像
     * @var string
     */
    public $avatar;
    /**
     * 性别
     * @var string
     */
    public $gender;
    /**
     * 最后修改时间
     * @var date
     */
    public $updated_at;
    /**
     * 创建时间
     * @var date
     */
    public $created_at;

    /**
     * 数据库里没有对应的字段
     * @var string
     * @Transient
     */
    public $otherProperty;

    /**
     * @var \Model\Human
     * @OneToOne(self=id,mappedBy=userId)
     */
    public $human;

    /**
     * @var integer
     * @Column(create_user_id)
     */
    public $createUserId;

    /**
     * @var \Model\User
     * @OneToOne(self=id,mappedBy=createUserId)
     */
    public $createUserInfo;
}