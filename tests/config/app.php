<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-24
 * Time: 18:09
 */

return [
    "smarty" => [
        "leftDelimiter" => "<{",
        "rightDelimiter" => "}>",
        "templateDir" => ROOT . "/views",
        "compileDir" => ROOT . "/runtime/smarty"
    ],
    "reoutes" => [],
    "filters" => [],
    "errorToMailer" => "zhengjiansheng@dankegongyu.com",
    "logger" => [
        "level" => Monolog\Logger::INFO
    ]
];