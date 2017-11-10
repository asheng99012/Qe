<?php

namespace Qe\Core\Orm;

use Com\Jufanr\Common\Constants\ServiceConstants;
use Qe\Core\Db\Db;
use Qe\Core\Logger;
use Com\Jufanr\Common\Model\VaryModel;

/**
 * 通用表服务对象
 *
 * @author lixin
 */
class RoleService {

    public function getList(VaryModel $varyModel) {
        $data = $varyModel->select();
        return $data;
    }

    public function getCount(VaryModel $varyModel) {
        $data = $varyModel->count();
        return $data;
    }

    public function getById($className, $id, $deleted = null) {
        $model = new  $className();
        if ($model instanceof VaryModel) {
            $model->id = $id;
            $model->deleted = $deleted;
            return $model->selectOne();
        } else {
            throw  new \Exception("$className 不能使用此方法");
        }
    }

    public function insert(VaryModel $varyModel) {
        if (empty ($varyModel->create_user)) {
            throw new \Exception ("创建人不能为空");
        }
        $varyModel->create_time = date("Y-m-d H:i:s", time());
        $data = $varyModel->insert();
        return $data;
    }

    public function update(VaryModel $varyModel) {
        if (empty ($varyModel->id)) {
            throw new \Exception ("主键不能为空");
        }
        if (empty ($varyModel->last_modified_user)) {
            throw new \Exception ("最后更新人不能为空");
        }
        $varyModel->last_modified_time = date("Y-m-d H:i:s", time());
        $count = $varyModel->update();
        if ($count === 0) {
            throw new \Exception ("此记录不存在！");
        }
        return $count;

    }

    public function remove($className, $id, $last_modified_user) {
        if (empty ($id)) {
            throw new \Exception ("主键不能为空");
        }
        if (empty ($last_modified_user)) {
            throw new \Exception ("最后更新人不能为空");
        }
        $varyModel = new $className();
        if ($varyModel instanceof VaryModel) {
            $sql = "update `vary` set deleted=? ,last_modified_user=? ,last_modified_time=? where id=? and vary_type=?";
            $count = Db::getDb()->update($sql, [
                    ServiceConstants::STATUS_DELETED,
                    $last_modified_user,
                    date("Y-m-d H:i:s", time()),
                    $id,
                    $className
            ]);
            if (empty ($count)) {
                Logger::warn("该数据不存在！");
            }
            return $count;
        } else {
            throw  new \Exception("$className 不能使用此方法");
        }
    }
}