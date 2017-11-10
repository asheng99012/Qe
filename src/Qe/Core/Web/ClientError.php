<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-31
 * Time: 16:48
 */

namespace Qe\Core\Web;


use Qe\Core\Mvc\Dispatch;
use Qe\Core\Mvc\HandlerInterceptor;
use Qe\Core\Logger;
use Qe\Core\Mvc\View;

class ClientError implements HandlerInterceptor {

    /**
     * @return boolean
     */
    function beforeHandle(Dispatch &$dispatch) {
        if ($dispatch->path == "/clientError") {
            Logger::getLogger("clientError",false)->error("客户端js报错", $dispatch->data);
            exit;
        }
    }

    /**
     * @return void
     */
    function afterHandle(Dispatch &$dispatch) {
        // TODO: Implement afterHandle() method.
    }

    /**
     * @return void
     */
    function afterCompletion(Dispatch &$dispatch) {
        // TODO: Implement afterCompletion() method.
    }
}