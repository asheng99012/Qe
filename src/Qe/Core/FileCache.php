<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-27
 * Time: 16:39
 */

namespace Qe\Core;


class FileCache
{
    private $yac;
    private $file;
    private $data;
    private $key;


    public function __construct($file)
    {
        $this->file = $file;
        $this->yac = new \Yac("qe_file_cache");
        $this->key=md5($file);
        $this->init();
    }

    private function init()
    {
        $mtime = filemtime($this->file);
        $this->data = $this->yac->get($this->key);
        if ($this->data === false || $this->data['mtime'] < $mtime) {
            $this->data = [
                "mtime" => $mtime,
                "data" => []
            ];
            $this->saveData();
        }
    }

    private function saveData()
    {
        if ($this->data) {
            $this->yac->set($this->key, $this->data);
        }
    }

    public function set($key, $val)
    {
        $this->data['data'][$key] = $val;
        $this->saveData();
    }

    public function get($key, callable $fun = null)
    {
        $val = null;
        if ((!array_key_exists($key, $this->data['data']) || !($val = $this->data['data'][$key])) && $fun !== null) {
            $val = $fun();
            $this->set($key, $val);
        }
        return $val;
    }

    public function isEmpty()
    {
        return count($this->data['data']) == 0;
    }

    public function all()
    {
        return $this->data;
    }

    public function info()
    {
        return $this->yac->info();
    }
}