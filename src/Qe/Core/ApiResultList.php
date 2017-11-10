<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-29
 * Time: 20:44
 */

namespace Qe\Core;


class ApiResultList extends ApiResult
{
    /**
     * ApiResultList constructor.
     * @param $_count integer
     * @param $list  array
     */
    public function __construct($_count, $list)
    {
        parent::__construct(array("count" => $_count, "list" => $list));
    }
}