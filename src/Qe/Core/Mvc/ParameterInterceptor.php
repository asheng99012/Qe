<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-29
 * Time: 21:51
 */

namespace Qe\Core\Mvc;
interface ParameterInterceptor
{
    public function intercept($field, &$map);
}
