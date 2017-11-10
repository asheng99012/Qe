<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-29
 * Time: 20:44
 */

namespace Qe\Core;


class ApiResult
{
    public $status = null;
    public $data = null;
    public $msg = null;

    public function __construct($_data, $_status = 0)
    {
        $this->status = $_status;
        $this->data = $_data;
    }

    /**
     * @return ApiResult
     */
    public static function error($_msg, $_status = 1)
    {
        if ($_status === 0) $_status = 1;
        $ret = new static(null, $_status);
        $ret->msg = $_msg;
        return $ret;
    }

    public static function success($data, $_status = 0)
    {
        return new static($data, $_status);
    }
}
