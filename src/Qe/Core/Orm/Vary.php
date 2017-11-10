<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-31
 * Time: 8:43
 */

namespace Qe\Core\Orm;
use Qe\Core\Db\SqlConfig;

/**
* 通用model 父类
* @Table(tableName = vary, primaryKey = id, where = id={id} order by create_time desc)
 */
class Vary extends ModelBase {
    /**
     * 主键
     * @var int
     */
    public $id;

    /**
     * 数据类型
     * @var string
     */
    public $vary_type;

    /**
     * 扩展信息
     * @var string
     */
    public $extension;
    /**
     * 创建人
     *
     * @var int
     */
    public $create_user;

    /**
     * 创建人的人员信息
     *
     * @var \Com\Jufanr\Common\Model\UserModel
     * @OneToOne(self=create_user,mappedBy=id)
     */
    public $create_user_info;

    /**
     * 创建时间
     *
     * @var date
     */
    public $create_time;

    /**
     * 最后修改人
     *
     * @var int
     */
    public $last_modified_user;

    /**
     * 最后修改人的人员信息
     *
     * @var \Com\Jufanr\Common\Model\UserModel
     * @OneToOne(self=last_modified_user,mappedBy=id)
     */
    public $last_modified_user_info;

    /**
     * 最后修改时间
     *
     * @var date
     */
    public $last_modified_time;

    /**
     * 是否删除, 1表示有效，2表是删除
     *
     * @var int
     */
    public $deleted;

    /**
     * 处理插入sql的参数
     * @param $map
     * @return mixed
     */
    public function interceptInsert($map) {
        $map['extension'] = json_encode($this);
        return $this->setVaryType($map);
    }

    /**
     * 处理 更新sql的参数
     * @param $map
     * @return mixed
     */
    public function interceptUpdate($map) {
        return $this->interceptInsert($map);
    }

    public function interceptSelect($map) {
        return $this->setVaryType($map);
    }

    /**
     * 添加通用参数
     * @param $map
     * @return mixed
     */
    public function setVaryType($map) {
        $map['vary_type'] = get_class($this);
        return $map;
    }

    /**
     * 处理查询返回的结果
     * @param $field
     * @param $map
     * @param SqlConfig $sqlConfig
     * @return array
     */
    public function intercept($field, &$map, SqlConfig $sqlConfig) {
        $map = array_merge(json_decode($map['extension'], true), $map);
        unset($map['vary_type']);
        unset($map['extension']);
        return $map;
    }

    /**
     * 给where添加添加前缀
     * @param $where
     * @return string
     */
    public function interceptWhere($where) {
        if (!preg_match("#deleted#", $where)) {
            $where = " deleted={deleted} and " . $where;
        }
        if (!preg_match("#vary_type#", $where)) {
            $where = " vary_type={vary_type} and " . $where;
        }
        return $where;
    }
}