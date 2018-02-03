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
    public abstract function display();

    public abstract function getModel();

    public abstract function setModel($model);
}