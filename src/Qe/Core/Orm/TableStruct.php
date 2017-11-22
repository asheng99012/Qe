<?php
/**
 * Created by IntelliJ IDEA.
 * User: asheng
 * Date: 2016-5-18
 * Time: 18:42
 */

namespace Qe\Core\Orm;


use Qe\Core\ClassCache;
use Qe\Core\SysCache;
use Qe\Core\Db\SqlConfig;

class TableStruct implements AbstractFunIntercept
{
    public $masterDbName;
    public $slaveDbName;
    public $primaryKey;
    public $primaryField;
    public $tableName;
    public $where;
    public $tableColumnList = array();
    public $relationStructList = array();
    public $isMapped = false;
    /**
     * @var \ReflectionClass
     */
    public $class;
    public $fcMap = array();
    private $_relation = array();

    private $first;
    private static $classKey = "TableStruct";

    /**
     * @return TableStruct
     */
    public static function getTableStruct($className, $first = true)
    {
        $cache = ClassCache::getCache($className);
        if (!($table = $cache->get(static::$classKey))) {
            $table = new static();
            $table->init($className, $first);
            $cache->set(static::$classKey, $table);
        }
        return $table;
    }

    private function init($className, $first)
    {
        $this->first = $first;
        if (empty($className)) {
            return;
        }
        $this->class = new \ReflectionClass($className);
        $table = AnnotationReader::getClassAnnotation($this->class, Annotation\Table::class);
        if (!$table) {
            $table = AnnotationReader::getClassAnnotation($this->class->getParentClass(), Annotation\Table::class);
        }
        $this->setTableInfo($table, $className);
        $fields = $this->class->getProperties();
        foreach ($fields as $field) {
            $this->dealProperty($field);
        }
        ClassCache::getCache($className)->set(static::$classKey, $this);
        $this->dealRelation($className);
    }

    private function setTableInfo(Annotation\Table $table, $className)
    {
        $this->masterDbName = $table->masterDbName;
        $this->slaveDbName = $table->slaveDbName;
        $this->primaryKey = $table->primaryKey;
        $this->tableName = empty($table->tableName) ? $this->class->getShortName() : $table->tableName;
        /**
         * @var ModelBase
         */
        $class = new $className();
        $this->where = $table->where;
    }

    private function dealProperty(\ReflectionProperty $property)
    {
        $anns = AnnotationReader::getPropertyAnnotations($property);
        if ($anns == null) {
            $anns = array();
        }
        if (array_key_exists(Annotation\Transient::class, $anns)) {
            return;
        }
        if (array_key_exists(Annotation\OneToOne::class, $anns) || array_key_exists(Annotation\OneToMany::class,
                $anns)
        ) {
            $this->_relation[$property->getName()] = $anns;
            return;
        }
        $columnName = $property->getName();
        if (array_key_exists(Annotation\Column::class, $anns)) {
            $column = $anns[Annotation\Column::class];
            $columnName = empty($column->value) ? $columnName : $column->value;
        }
        if ($this->primaryKey == $columnName) {
            $this->primaryField = $property->getName();
        }
        $this->tableColumnList[] = array("columName" => $columnName, "filedName" => $property->getName());
        $this->fcMap[$property->getName()] = $columnName;
        if ($columnName != $property->getName()) {
            $this->isMapped = true;
        }

    }

    private function dealRelation($className)
    {
        foreach ($this->_relation as $fieldName => $anns) {
            $type = $anns[Annotation\FieldType::class]->value;
            $isSame = $type === $className;
            if (!$this->first && $isSame) {
                continue;
            }
            $tableStruct = TableStruct::getTableStruct($type, !$isSame);
            if (array_key_exists(Annotation\OneToOne::class, $anns)) {
                $ones = $anns[Annotation\OneToOne::class];
                $relationStruct = new RelationStruct();
                $relationStruct->relationKey = $this->fcMap[$ones->self] . "|" . $tableStruct->fcMap[$ones->mappedBy];
                $relationStruct->fillKey = $fieldName;
                $relationStruct->extend = "one2One";
                $relationStruct->clazz = $type;
                $relationStruct->where = " `" . $tableStruct->fcMap[$ones->mappedBy] . "` in ({" . $this->fcMap[$ones->self] . "})";
                $this->relationStructList[] = $relationStruct;
            }
            if (array_key_exists(Annotation\OneToMany::class, $anns)) {
                $oneToMany = $anns[Annotation\OneToMany::class];
                $relationStruct = new RelationStruct();
                $relationStruct->relationKey = $this->fcMap[$oneToMany->self] . "|" . $tableStruct->fcMap[$oneToMany->mappedBy];
                $relationStruct->fillKey = $fieldName;
                $relationStruct->extend = "one2Many";
                $relationStruct->clazz = $type;
                $relationStruct->where = " `" . $tableStruct->fcMap[$oneToMany->mappedBy] . "` in ({" . $this->fcMap[$oneToMany->self] . "})";
                $this->relationStructList[] = $relationStruct;
                return;
            }
        }
    }


    public function intercept($field, &$map, SqlConfig &$sqlConfig)
    {
        $table = static::getTableStruct($sqlConfig->returnType);
        if ($table != null && $table->isMapped) {
            foreach ($table->tableColumnList as $tc) {
                $map[$tc["filedName"]] = $map[$tc["columName"]] ?? null;
            }
        }
    }
}
