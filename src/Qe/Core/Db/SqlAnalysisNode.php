<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-18
 * Time: 9:05
 */

namespace Qe\Core\Db;


class SqlAnalysisNode
{
    public $whole;
    public $field;
    public $operator;
    public $prefix;
    public $paramWhole;
    public $param;
    public $suffix;

    public function isLike()
    {
        return $this->operator == "like";
    }

    public function isIn()
    {
        return $this->operator == "in";
    }

    public function isNotIn()
    {
        return $this->operator == "not in";
    }

    public function isBy()
    {
        return $this->operator == "by";
    }
}