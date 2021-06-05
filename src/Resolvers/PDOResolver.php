<?php
namespace myJSON\Resolvers;

use Symfony\Component\Cache\Adapter\AdapterInterface as Cache;
use myJSON\Errors\ResponseError;
use myJSON\Collections\Collection;
use myJSON\Collections\RelationshipCollection;
use myJSON\Relationships\PersistedRelationship;
use myJSON\Collections\TraitCollection;
use myJSON\Collections\PersistedCollection;
use myJSON\Collections\FilterCollection;
use myJSON\Traits\PDOTrait;
use myJSON\Adapter\AdapterInterface as AdapterInterface;

class PDOResolver extends \myJSON\Library\Base implements ResolverInterface {
    private $adapter = null;
    private $tables = null;
    private $columns = null;
    private $attributes = null;
    private $properties = null;
    public function __construct(AdapterInterface $adapter, Cache $cache) {
        $this->setType("resolver");
        $this->setCache($cache);
        $this->adapter = $adapter;
        $columnsKey = $this->getCacheKey("columns");
        $tablesKey = $this->getCacheKey("tables");
        $attributesKey = $this->getCacheKey("attributes");
        $propertiesKey = $this->getCacheKey("properties");
        $this->tables = (new Collection())->setType("resolver")->setId("tables")->setCache($cache)->loadCache([]);
        $this->columns = (new Collection())->setType("resolver")->setId("columns")->setCache($cache)->loadCache([]);
        $this->attributes = (new Collection())->setType("resolver")->setId("attributes")->setCache($cache)->loadCache([]);
        $this->properties = (new Collection())->setType("resolver")->setId("properties")->setCache($cache)->loadCache([]);
    }
    public function getAdapter() {
        return $this->adapter;
    }
    public function getEntity($type) {
        $name = $this->tables->getItem($type);
        if(!isset($name)) {
            $name = $type;
            $name = (strpos($name, "\\") !== false ? substr($name, strrpos($name, "\\") + 1) : $name);
            $name = str_replace("Model", "", $name);
            $this->tables->addItem($type, $name);
            $this->tables->saveCache();
        }
        return $name;
    }
    public function findRecord($type, $id) {
        $trait = $this->getTraits($type)->objectAt(0);
        $value = $this->decodeValue($id, $trait->getType());
        return $this->getAdapter()->findRecord($this->getEntity($type), [$trait->getId()=>$value]);
    }
    public function saveRecord($type, $data) {
        return $this->getAdapter()->saveRecord($this->getEntity($type), $data);
    }
    public function updateRecord($type, $id, $data) {
        return $this->getAdapter()->updateRecord($this->getEntity($type), [$this->getTraits($type)->objectAt(0)->getId()=>$id], $data);
    }
    public function destroyRecord($type, $id) {
        return $this->getAdapter()->destroyRecord($this->getEntity($type), [$this->getTraits($type)->objectAt(0)->getId()=>$id]);
    }
    public function getTraits($type) {
        return $this->getEntityTraits($this->getEntity($type));
    }
    public function getEntityTraits($table) {
        $columns = $this->getColumns($table);
        $traits = [];
        if(isset($columns)) {
            foreach($columns as $column) {
                $trait = (new PDOTrait($column));
                $this->initTrait($table, $trait);
                $traits[] = $trait;
            }
        }
        else {
            throw new \Exception("Unable to load columns for {$table}.");
        }
        return (new TraitCollection($traits));
    }
    public function initTrait($entity, $trait) {
        $trait->setKey(($trait->getData()->Key == "PRI"));
        $trait->setIdentity(($trait->getData()->Key == "MUL"));
        $trait->setRequired(($trait->getData()->Null == "NO"));
        $trait->setDefault($trait->getData()->Default);
        $trait->setClass(get_class($trait));
        $trait->setAttribute($this->getTraitAttribute($entity, $trait));
        $trait->setProperty($this->getTraitProperty($entity, $trait));
        $trait->setResolver($this);
        $typeParts = explode(" ", str_replace(["(",")"], " ", $trait->getData()->Type));
        $trait->setType($typeParts[0]);
        $trait->setId($trait->getData()->Field);
        if(isset($typeParts[1])) $trait->setSize($typeParts[1]);
        if(!$trait->isKey() && $trait->isIdentity()) {
            $trait->setClass($this->getTraitClass($entity, $trait));
        }
        return $this;
    }
    public function getTraitClass($entity, $trait) {
        $pieces = \explode("_", $trait->getData()->Field);
        $name = \ucfirst($pieces[0]);
        return $this->getModel($name);
    }
    public function getModel($entity) {
        return "App\\Model\\{$entity}Model";
    }
    public function getColumns($entity) {
        $columns = $this->columns->getItem($entity);
        if(!isset($columns)) {
            $columns = $this->getAdapter()->getColumns($entity);
            $this->columns->addItem($entity, $columns);
            $this->columns->saveCache();
        }
        return $columns;
    }
    public function findAll($type, $filters) {
        return $this->getAdapter()->findAll($this->getEntity($type), $filters);
    }
    public function getRelationship($target, $keyvalue) {
        $data = null;
        $filters = (new FilterCollection());
        foreach($keyvalue as $key=>$value) {
            $filters->attribute($key, $value, "equal");
        }
        $data = $this->getAdapter()->findAll($target, $filters);
        return $data;
    }
    public function saveRelationship($for, $target, $link, $id, $data) {
        $orderedData = [];
        if(count($data ?? [])) {
            $key = $this->getEntityTraits($target)->objectAt(0)->getId();
            foreach($data as $k=>$item) {
                $orderedData[] = [$key=>$item];
            }
        }
        return $this->getAdapter()->saveRelationship($link, [$this->getEntityTraits($for)->objectAt(0)->getId()=>$id], $orderedData);
    }
    public function getProperties($type) {
        $properties = $this->properties->getItem($type);
        if(!isset($properties)) {
            $properties = [];
            $traits = $this->getTraits($type);
            foreach($traits->getData() as $trait) {
                $index = $this->getTraitProperty($this->getEntity($type), $trait);
                $properties[$index] = $trait->getData()->Field;
            }
            if(count($properties)) {
                $this->properties->addItem($type, $properties);
                $this->properties->saveCache();
            }
        }
        return $properties;
    }
    public function getTraitProperty($entity, $trait) {
        $property = "";
        $pieces = explode("_", $trait->getData()->Field);
        foreach($pieces as $piece) {
            if($piece != "id" || $property == $entity) {
                $property .= ucfirst($piece);
            }
        }
        $property = \str_replace($entity, "", $property);
        return $property;
    }
    public function getAttributes($type) {
        $attributes = $this->attributes->getItem($type);
        if(!isset($attributes)) {
            $attributes = [];
            $traits = $this->getTraits($type);
            foreach($traits->getData() as $trait) {
                $index = $this->getTraitAttribute($this->getEntity($type), $trait);
                if(isset($index)) $attributes[$index] = $trait->getData()->Field;
            }
            if(count($attributes)) {
                $this->attributes->addItem($type, $attributes);
                $this->attributes->saveCache();
            }
        }
        return $attributes;
    }
    /*public function updateAttributes($record, $attributes) {
        if($record->isValid()) {
            foreach($attributes as $key=>$value) {
                foreach($record->getTraits()->getData() as $trait) {
                    if($trait->getAttribute() == $key) {
                        $this->setTraitValue($trait, $value);
                    }
                }
            }
        }
        return $record;
    }*/
    public function getAttributeFilters($type, $attributes, $operators) {
        $traits = $this->getTraits($type);
        $filters = new FilterCollection();
        if(count($attributes ?? [])) {
            $keys = array_keys($attributes);
            $values = array_values($attributes);
            for($i = 0; $i < count($keys); $i++) {
                $trait = $traits->findByAttribute($keys[$i]);
                if(isset($trait)) {
                    $filters->attribute($trait->getId(), $this->decodeValue($values[$i], $trait->getType()), (isset($operators[$keys[$i]]) ? $operators[$keys[$i]] : null));
                }
            }
        }
        return $filters;
    }
    public function getTraitAttribute($table, $trait) {
        $index = null;
        $column = $trait->getData();
        if($column->Key == "" || $column->Key == "UNI") {
            $index = strstr($column->Field, "_");
            $index = str_replace("_","", $index);
        }
        return $index;
    }
    public function getAttribute($value) {
        return $this->getAdapter()->getAttribute($value);
    }
    public function decodeValues($type, $data) {
        $traits = $this->getTraits($type);
        foreach($traits->getData() as $trait) {
            if(isset($data[$trait->getId()])) {
                $data[$trait->getId()] = $this->decodEvalue($data[$trait->getId()], $trait->getType());
            }
        }
        return $data;
    }
    public function encodeValue($value, $type = null) {
        switch($type) {
            case "binary":
                $value = bin2hex($value);
            break;
        }
        return $value;
    }
    public function decodeValue($value, $type=null) {
        switch($type) {
            case "int":
                $value = intval($value);
            break;
            case "datetime":
                $value = (!isset($value) ? null : self::fromISODateTime($value));
            break;
            case "tinyint":
                $value = (($value === true || $value === false) ? ($value ? 1 : 0) : $value);
            break;
            case "binary":
                try {
                    $value = hex2bin($value);
                }
                catch(\Exception $ex) {
                    $a = $ex;
                }
            break;
        }
        return $value;
    }
}
 ?>
