<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-24
 * Time: 18:09
 */

return [
    "default" => "laputa",
    "database" => [
        "laputaMaster" => [
            "dns" => "mysql:host=10.0.75.1;dbname=Laputa;port=3306",
            "username" => "root",
            "password" => "asheng",
            "params" => [
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"
            ]
        ],
        "laputaSlave" => [
            "dns" => "mysql:host=10.0.75.1;dbname=Laputa;port=3306",
            "username" => "root",
            "password" => "asheng",
            "params" => [
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"
            ]
        ],
    ]

];