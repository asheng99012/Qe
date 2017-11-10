<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-30
 * Time: 16:43
 */

namespace Qe\Core\Mvc;


use Qe\Core\ApiResult;

class JsonView extends View
{
    public $model;

    public function __construct($model)
    {
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

    function display()
    {
        $this->model = $this->model instanceof ApiResult ? $this->model : new ApiResult($this->model);
        header('Content-Type:application/json;charset=utf8');
        echo json_encode($this->model);
    }
}
