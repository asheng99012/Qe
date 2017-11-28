<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-6-1
 * Time: 10:05
 */

namespace Qe\Core;


class Wrap
{
    private $data;

    /**
     * @return Wrap
     */
    public static function getWrap(&$data)
    {
        return new static($data);
    }

    private function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return Wrap|array
     */
    public function __invoke($key = "", $value = null)
    {
        return Utils::isNullOrEmpty($value) ? $this->getValue($key) : $this->setValue($key, $value);
    }

    private function getValue($key)
    {
        $data = &$this->data;
        if (Utils::isNullOrEmpty($key)) {
            return $data;
        }
        $keys = Utils::isNullOrEmpty($key) ? array() : explode(".", $key);
        while (count($keys) > 0 && is_array($data) && array_key_exists($keys[0], $data)) {
            $data = &$data[array_shift($keys)];
        }
        if (count($keys) > 0) {
            return new static(null);
        }
        return new static($data);
    }

    private function setValue($key, $val)
    {
        $data = &$this->data;
        $keys = Utils::isNullOrEmpty($key) ? array() : explode(".", $key);
        while (count($keys) > 1 && is_array($data) && array_key_exists($keys[0], $data)) {
            $data = &$data[array_shift($keys)];
        }
        while (count($keys) > 1) {
            $data[$keys[0]] = array();
            $data = &$data[array_shift($keys)];
        }
        $data[$keys[0]] = $val;
        return $this;
    }

    public function get()
    {
        return $this->data;
    }

    public function __tostring()
    {
        if (is_array($this->data) || is_object($this->data)) {
            return json_encode($this->data);
        }
        return $this->data;
    }

}