<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-19
 * Time: 15:07
 */

namespace Qe\Core\Orm;


use Qe\Core\Db\SqlConfig;

interface AbstractFunIntercept
{
    public function intercept($field, &$map, SqlConfig &$sqlConfig);
}