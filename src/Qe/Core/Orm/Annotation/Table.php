<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-18
 * Time: 12:58
 */

namespace Qe\Core\Orm\Annotation;


class Table
{
    public $masterDbName = "";
    public $slaveDbName = "";
    public $primaryKey = "id";
    public $tableName = "";
    public $where = "";
}