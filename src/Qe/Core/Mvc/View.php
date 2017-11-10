<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-30
 * Time: 16:43
 */

namespace Qe\Core\Mvc;

abstract class View
{
    abstract function display();

    abstract function getModel();

    abstract function setModel($model);
}