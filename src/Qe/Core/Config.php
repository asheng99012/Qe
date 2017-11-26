<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-26
 * Time: 18:10
 */

namespace Qe\Core;


class Config
{
    private static $data = [];

    public static function get($path)
    {
        $paths = explode(".", $path);
        $fileName = $paths[0];
        $filePath = ROOT . "/config/" . $fileName . ".php";
        $mtime = filemtime($filePath);
        if (array_key_exists($fileName, static::$data)) {
            $configData = static::$data[$fileName];
        } else {
            $configData = (new \Yac("qe_file_cache"))->get($fileName);
        }
        if (!$configData || $configData['mtime'] < $mtime) {
            $configData = [
                "mtime" => $mtime,
                "data" => [
                    $fileName => require $filePath
                ]
            ];
            static::$data[$fileName] = $configData;
            (new \Yac("qe_file_cache"))->set($fileName, $configData);
        }
        return (Wrap::getWrap($configData['data'])($path))->get();
    }


}