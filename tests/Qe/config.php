<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-11
 * Time: 20:58
 */
$loader = require __DIR__ . "/../../vendor/autoload.php";
define("ROOT", dirname(__DIR__));
//$loader->setPsr4("", ROOT . '/tests/Qe/');
//define("ROOT", __DIR__);
$loader->setPsr4("", ROOT . '/Qe/');