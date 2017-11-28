<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-26
 * Time: 11:07
 */

namespace Qe\Core\Db;


use Qe\Core\Config;

class Conn
{
    private static $connMap = array();

    /**
     * @param string $dbName
     * @return \PDO
     */
    public static function getConn($dbName = "")
    {
        $dbConfigs = Config::get("database.database");
        if (empty($dbName) || !array_key_exists($dbName, $dbConfigs)) {
            $dbName = array_keys($dbConfigs)[0];
        }
        if (!array_key_exists($dbName, static::$connMap)) {
            $config = $dbConfigs[$dbName];
            $pdo = new \PDO($config["dns"], $config["username"], $config["password"], $config["params"]);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            static::$connMap[$dbName] = $pdo;
        }
        return static::$connMap[$dbName];
    }
}