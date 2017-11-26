<?php
/**
 * Created by IntelliJ IDEA.
 * User: ashen
 * Date: 2017-11-26
 * Time: 19:02
 */

namespace Qe\Core;


use Qe\Core\Orm\AnnotationReader;

class Proxy
{
    public static function handle($className)
    {
        return new static($className);
    }

    private $className;
    private $target;
    private static $key = "qeproxy";

    public function __construct($className)
    {
        $this->className = $className;
    }

    public function __set($name, $value)
    {
        $this->getTarget()->$name = $value;
    }

    public function __get($name)
    {
        return $this->getTarget()->$name;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->getTarget(), $name), $arguments);
    }

    private function getTarget()
    {
        if (!$this->target) {
            $target = ClassCache::getCache($this->className)->get(static::$key);
            if (!$target) {
                $target = $this->initTarget();
                ClassCache::getCache($this->className)->set(static::$key, $target);
            }
            $this->target = $target;
        }
        return $this->target;
    }

    private function initTarget()
    {
        $className = $this->className;
        $target = new $className;
        $refc = new \ReflectionClass($className);
        $fields = $refc->getProperties();
        foreach ($fields as $field) {
            $this->dealProperty($field, $target);
        }
        return $target;
    }

    private function dealProperty(\ReflectionProperty $property, &$target)
    {
        $anns = AnnotationReader::getPropertyAnnotations($property);
        if ($anns && count($anns) > 0) {
            if (array_key_exists(Orm\Annotation\Config::class, $anns)) {
                $config = $anns[Orm\Annotation\Config::class];
                $value = Config::get($config->value);
                $property->setValue($target, $value);
            }
            if (array_key_exists(Orm\Annotation\Resource::class, $anns)) {
                $value = $anns[Orm\Annotation\FieldType::class]->value;
                $property->setValue($target, Proxy::handle($value));
            }
        }
    }
}