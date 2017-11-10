<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-31
 * Time: 16:48
 */

namespace Qe\Core\Web;


use Qe\Core\FormFilter;
use Qe\Core\Mvc\Dispatch;
use Qe\Core\Mvc\HandlerInterceptor;
use Qe\Core\Mvc\View;
use Qe\Core\Xcode;

class EncodeIds implements HandlerInterceptor
{

    /**
     * @return boolean
     */
    function beforeHandle(Dispatch &$dispatch)
    {
        $dispatch->data = FormFilter::getFormData();
        return true;
    }

    /**
     * @return View
     */
    function afterHandle(View &$view)
    {
        $json = json_encode($view->getModel());
        foreach (FormFilter::getIdentifys() as $id)
            $json = preg_replace_callback('/("' . $id . '":)("?(\d+)"?)/', function ($matches) {
                return $matches[1] . '"' . Xcode::encode($matches[3]) . '"';
            }, $json);
        $view->setModel(json_decode($json, true));
        return $view;
    }

    /**
     * @return void
     */
    function afterCompletion()
    {
        // TODO: Implement afterCompletion() method.
    }
}