<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-11
 * Time: 20:58
 */
define("ROOT", dirname(__DIR__));
$loader = require __DIR__ . "/../vendor/autoload.php";
$loader->setPsr4("", ROOT . '/tests/Qe/');

define("dbConfigs", [
    "master" => array(
        "dns" => "mysql:host=10.0.75.1;dbname=Laputa;port=3306",
        "username" => "root",
        "password" => "asheng",
        "params" => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"
        )
    ),
    "slave" => array(
        "dns" => "mysql:host=10.0.75.1;dbname=Laputa;port=3306",
        "username" => "root",
        "password" => "asheng",
        "params" => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"
        )
    ),
]);

define("errorToMailer", "zhengjiansheng@dankegongyu.com");

define("logger", [
    "level" => Monolog\Logger::INFO
]);

define("reoutes", []);
define('filters', []);