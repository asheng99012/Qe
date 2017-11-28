<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-30
 * Time: 14:49
 */

namespace Qe\Core\Mvc;


use Qe\Core\ApiResult;
use Qe\Core\Config;


class HtmlView extends View
{
    public $viewName;
    private $model;

    public function __construct($model, $viewName = "")
    {
        $this->viewName = empty($viewName) ? Dispatch::getDispatch()->path : $viewName;
        $this->setModel($model);
    }

    function getModel()
    {
        return $this->model;
    }

    function setModel($model)
    {
        $this->model = $model;
    }

    public function fetch()
    {
        $smarty = $this->getSmarty();
        $smarty->assign('model', $this->model);
        return $smarty->fetch(trim($this->viewName . ".tpl", "/"), null, null, null, false);
    }

    private function getSmarty()
    {
        $config = Config::get("app.smarty");
        $smarty = new \Smarty();
        $smarty->setLeftDelimiter($config['leftDelimiter']);
        $smarty->setRightDelimiter($config['rightDelimiter']);
        $smarty->setTemplateDir($config['templateDir']);
        $smarty->setCompileDir($config['compileDir']);
        return $smarty;
    }

    function display()
    {
        header("Content-Type: text/html;charset=utf-8");
        echo $this->fetch();
    }
}


