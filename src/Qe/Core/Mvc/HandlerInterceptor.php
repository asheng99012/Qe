<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-29
 * Time: 21:51
 */

namespace Qe\Core\Mvc;

interface HandlerInterceptor
{
    /**
     * @return boolean
     */
    function beforeHandle(Dispatch &$dispatch);

    /**
     * @return void
     */
    function afterHandle(Dispatch &$dispatch);

    /**
     * @return void
     */
    function afterCompletion(Dispatch &$dispatch);
}
