<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-29
 * Time: 21:52
 */

namespace Qe\Core\Mvc;

use Qe\Core\CheckParam;

class BaseController {

    private $_model=[];

    /**
     * 页面参数魔术方法
     * @param $key
     * @param $value
     */
    public function __set($key,$value){
        $this->assign($key,$value);
    }

    /**
     * 页面传参
     * @param $key
     * @param $value
     */
    public function assign($key,$value){
        $this->_model[$key]=$value;
    }

    /**
     * 执行action前执行
     */
    public function beforeExecute() {
    }

    /**
     * 执行action完毕后执行
     */
    public function afterExecute() {
    }

    /**
     * 获取参数
     * @param string|array $param
     * @param null $def 取不到时返回的默认值
     * @return array|string
     */
    public function getParameter($param = "",$def=null) {
        if (empty($param)) return Dispatch::getDispatch()->data;
        $data = Dispatch::getDispatch()->data;
        if (is_array($param)) {
            return CheckParam::checkParams($param, $data);
        }
        if (isset($data[$param])) return $data[$param];
        else return $def;
    }

    public function toHtmlString($model, $viewName) {
        return (new HtmlView($model, $viewName))->fetch();
    }

    /**
     * 显示视图
     * @param string $model 1.值为string是，表示视图，为array时，表示页面参数
     * @param string $viewName
     * @return HtmlView
     */
    public function toView($model="", $viewName = "") {

        if(is_string($model)){
            $viewName=$model;
            $model=$this->_model;
        }else if(is_array($model)){
            $model=array_merge($this->_model,$model);
        }
        return new HtmlView($model, $viewName);
    }

    public function toJson($model) {
        return new JsonView($model);
    }

    public function toCsv($model, $colums = array()) {
        return new CsvView($model, $colums);
    }

    public function redirect($path) {
        ob_end_clean();
        header("Location: $path");
        exit;
    }

    public function forward($url) {
        return Dispatch::getDispatch()->forward($url);
    }


}
