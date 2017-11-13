<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-13
 * Time: 17:47
 */

namespace Qe\Core\Orm;


class SqlAndOrNode
{
    public $whole;
    public $field;
    public $operator = "";
    public $paramWhole1;
    public $param1;
    public $paramWhole2;
    public $param2;
}