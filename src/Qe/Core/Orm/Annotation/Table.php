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
    public $mainDbName = "";
    public $readDbName = "";
    public $primaryKey = "id";
    public $tableName = "";
    public $where = "";
}