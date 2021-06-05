<?php
    namespace myJSON\Services;
    use Symfony\Component\Cache\Adapter\AdapterInterface as Cache;
    use myJSON\Collections\PersistedCollection;
    use myJSON\Collections\Collection;
    use myJSON\Collections\FilterCollection;
    use myJSON\Dictionaries\PersistedDictionary;
    use myJSON\Relationships\PersistedRelationship;
    use myJSON\Adapter\AdapterInterface as Adapter;
    use myJSON\Library\Base;
    use myJSON\Resolvers\ResolverInterface;

    class Store extends Base {
        private $resolver = null;
        private $dictionary = null;
        public function __construct(ResolverInterface $resolver, Cache $cache) {
            $this->setCache($cache);
            $this->resolver = $resolver;
            $this->dictionary = (new PersistedDictionary());
        }
        public function getResolver() {
            return $this->resolver;
        }
        public function setResolver($resolver) {
            $this->resolver = $resolver;
            return $this;
        }
        public function getDictionary() {
            return $this->dictionary;
        }
        public function setDictionary($value) {
            $this->dictionary = $value;
            return $this;
        }
        public function getCollectionKey($type, $id) {
            return "store.records." . strtolower($this->getResolver()->getEntity($type)) . "." . $this->encodeRecordKey($type, $id);
        }
        public function encodeRecordKey($type, $id) {
            return $id;
        }
        public function decodeRecordKey($type, $reference) {
            return $reference;
        }
        public function obfuscateRecord($record) {
            if(!$record->isNew()) {
                $this->toCache($this->getCollectionKey($record->getClass(), $record->getId()), $record->toArray());
            }
            return $this;
        }
        public function clarifyRecord($type, $id) {
            $data = $this->fromCache($this->getCollectionKey($type, $id));
            if(isset($data)) {
                if(isset($data["data"])) {
                    if(isset($data["traits"])) {
                        $keys = array_keys($data["data"]);
                        for($i = 0; $i < count($keys); $i++) {
                            $data["data"][$keys[$i]] = $this->getResolver()->decodeValue($data["data"][$keys[$i]], $data["traits"][$keys[$i]]["type"]);
                        }
                    }
                    else {
                        $data["data"] = $this->getResolver()->decodeValues($type, $data["data"]);
                    }
                }
            }
            return $data;
        }
        public function deleteRecord($type, $id) {
            return $this->deleteCachedItem($this->getCollectionKey($type, $id));
        }
        public function fromDictionary($type) {
            $records = $this->getDictionary()->getItem($type);
            if(!isset($records)) {
                $records = (new PersistedCollection());
                $this->getDictionary()->addItem($type, $records);
            }
            return $records;
        }
        public function getUUID() {
            return $this->getAttribute("UUID_TO_BIN(UUID())");
        }
        public function getNow() {
            return $this->getAttribute("NOW()");
        }
        public function queryRecord($type, $id) {
            return $this->findRecord($type, $this->decodeRecordKey($type, $id));
        }
        public function findRecord($type, $id) {
            $record = null;
            $dictionary = $this->fromDictionary($type);
            $record = $dictionary->getItem($this->getCollectionKey($type, $id));
            if(!isset($record)) {
                $data = $this->clarifyRecord($type, $id);
                if(isset($data)) {
                    $record = $this->createRecord($type, ...array_values($data["data"]))->setNew(false);
                    if($record->isValid()) {
                        $dictionary->addItem($this->getCollectionKey($type, $id), $record);
                    }
                }
                else {
                    $data = $this->getResolver()->findRecord($type, $id);
                    if(count($data ?? [])) {
                        $record = $this->createRecord($type, ...array_values($data))->setNew(false);
                        $dictionary->addItem($this->getCollectionKey($type, $id), $record);
                        $this->obfuscateRecord($record);
                    }
                }
            }
            return $record;
        }
        public function getAttributeFilters($type, $attributes = null, $operators = null) {
            return $this->getResolver()->getAttributeFilters($type, $attributes, $operators);
        }
        public function findAll($type, $attributes = null, $operators = null) {
            return $this->query($type, $this->getResolver()->getAttributeFilters($type, $attributes, $operators));
        }
        public function query($type, FilterCollection $filters) {
            return $this->createCollection($type, $this->getResolver()->findAll($type, $filters));
        }
        public function createCollection($type, $records) {
            $collection = (new PersistedCollection());
            if(count($records ?? [])) {
                $dictionary = $this->fromDictionary($type);
                foreach($records as $item) {
                    $record = $this->createRecord($type, ...array_values($item))->setNew(false);
                    $key = $this->getCollectionKey($type, $record->getId());
                    if($dictionary->hasItem($key)) {
                        $collection->addItem($key, $dictionary->getItem($key));
                    }
                    else {
                        $dictionary->addItem($key, $record);
                        $collection->addItem($key, $record);
                        $this->obfuscateRecord($record);
                    }
                }
            }
            return $collection;
        }
        public function createRecord($type, ...$parameters) {
            $record = (new $type());
            $record->setClass($type);
            $record->setStore($this);
            $record->setType(strtolower($this->getResolver()->getEntity($type)));
            $record->setName($this->getResolver()->getEntity($type));
            $record->setTraits($this->getResolver()->getTraits($type)->setEntity($record));
            $record->setAttributes($this->getResolver()->getAttributes($type));
            $record->setProperties($this->getResolver()->getProperties($type));
            $this->normalizeRecord($record, $parameters);
            return $record;
        }
        private function normalizeRecord($record, $parameters) {
          if(isset($parameters)) {
              if(count($parameters) <= $record->getTraits()->length()) {
                  for($i = 0; $i < $record->getTraits()->length(); $i++) {
                      $trait = $record->getTraits()->objectAt($i);
                      if($trait->isKey()) {
                        $trait->setNativeValue((isset($parameters[$i]) ? $parameters[$i] : null));
                      }
                      else {
                        $trait->setNormalValue((isset($parameters[$i]) ? $parameters[$i] : null));
                      }
                      $record->setValue($trait->getId(), $trait->getNativeValue());
                  }
                  $record->setId($this->encodeRecordKey($record->getClass(), $record->getTraits()->objectAt(0)->getNormalValue()));
              }
              else {
                  throw new \Exception("Too many parameters specified for " . $record->getClass() . ". Check the " . $record->getName() . " for parameters.");
              }
          }
            return $this;
        }
        public function getAttribute($var) {
            return $this->getResolver()->getAttribute($var);
        }
        public function getModel($entity) {
            return $this->getResolver()->getModel($entity);
        }
        public function getRelationship($model, $target, $link=null) {
            $data = [];
            if(!isset($link)) {
                $trait = $model->getTraits()->objectAt(0);
                $data = $this->getResolver()->getRelationship($this->getResolver()->getEntity($target), [$trait->getId()=>$model->getData()[$trait->getId()]]);
            }
            else {
                $trait = $model->getTraits()->objectAt(0);
                $linkData = $this->getResolver()->getRelationship($link, [$trait->getId()=>$trait->getNativeValue()]);
                if(count($linkData ?? [])) {
                    for($i = 0; $i < count($linkData); $i++) {
                        $targetTrait = $this->getResolver()->getTraits($target)->objectAt(0);
                        $targetData = $this->getResolver()->getRelationship($this->getResolver()->getEntity($target), [$targetTrait->getId()=>$linkData[$i][$targetTrait->getId()]]);
                        if(count($targetData ?? [])) {
                            foreach($targetData as $item) {
                                $data[] = $item;
                            }
                        }
                    }
                }
            }
            return $this->createRelationship($model, $target, $link, $data);
        }
        public function createRelationship($model, $target, $link, $data) {
            $result = (new PersistedRelationship())->setEntity($model)->setTarget($target)->setLink($link);
            if(count($data ?? [])) {
                $relationship = [];
                $dictionary = $this->fromDictionary($target);
                foreach($data as $item) {
                    $record = $this->createRecord($target, ...array_values($item))->setNew(false);
                    $relationship[$this->getCollectionKey($target, $record->getId())] = $record;
                    $key = $this->getCollectionKey($target, $record->getId());
                    if(!$dictionary->hasItem($key)) {
                        $dictionary->addItem($key, $record);
                        $this->obfuscateRecord($record);
                    }
                }
                $result->setData($relationship);
            }
            return $result;
        }
        public function updateRelationship($model, $target, $link=null, $accept=[]) {
            $relationship = $model->getRelationship($target, $link);
            $relationship->merge($accept, function($add) use ($model, $target, $link, $relationship) {
                $record = $this->findRecord($target, $this->encodeRecordKey($target, $add));
                if(isset($record)) {
                    $this->addRelationshipRecord($model, $record, $link);
                    if(isset($link)) {
                        $this->addRelationshipRecord($record, $model, $link);
                    }
                }
                else {
                    throw new \Exception("Failed adding " . $this->getResolver()->getEntity($target) . " record to " . $model->getName() . " relationship because " . $add . " could not be found.");
                }
            }, function($remove) use($model, $target, $link, $relationship) {
                $record = $this->findRecord($target, $this->encodeRecordKey($target, $remove));
                if(isset($record)) {
                    $this->removeRelationshipRecord($model, $record, $link);
                    if(isset($link)) {
                        $this->removeRelationshipRecord($record, $model, $link);
                    }
                }
                else {
                    throw new \Exception("Failed removing " . $this->getResolver()->getEntity($target) . " record from " . $model->getName() . " relationship because " . $add . " could not be found.");
                }
            });
            return $this;
        }
        public function getInverseRelationship($model, $type) {
            $relationship = null;
            $trait = $model->getTraits()->findById($this->getResolver()->getTraits($type)->objectAt(0)->getId());
            $record = $this->findRecord($type, $this->getResolver()->encodeValue($model->getData()[$trait->getId()], $trait->getType()));
            if(isset($record)) {
                $relationship = $record->getRelationship($model->getClass());
            }
            else {
                throw new \Exception("Unable to find inverse relationship of {$type} for {$model->getClass()}.");
            }
            return $relationship;
        }
        public function addInverseRelationship($model, $target) {
            $relationship = $this->getInverseRelationship($model, $target->getClass());
            if(isset($relationship)) {
                $relationship->addItem($this->getCollectionKey($model->getClass(), $model->getId()), $model);
            }
            $this->addRelationshipRecord($target, $model);
            return $this;
        }
        public function removeInverseRelationship($model, $target) {
            $relationship = $this->getInverseRelationship($model, $target->getClass());
            if(isset($relationship)) {
                $relationship->removeItem($this->getCollectionKey($model->getClass(), $model->getId()));
            }
            $this->removeRelationshipRecord($target, $model);
            return $this;
        }
        private function addRelationshipRecord($model, $target, $link=null) {
            $relationship = $model->getRelationship($target->getClass(), $link);
            if(isset($relationship)) $relationship->addItem($this->getCollectionKey($target->getClass(), $target->getId()), $target);
            return $this;
        }
        private function removeRelationshipRecord($model, $target, $link=null) {
            $relationship = $model->getRelationship($target->getClass(), $link);
            if(isset($relationship)) $relationship->removeItem($this->getCollectionKey($target->getClass(), $target->getId()));
            return $this;
        }
        public function updateRecordAttributes($record, $attributes) {
            if($record->getTraits()->length()) {
                foreach($record->getTraits()->getData() as $trait) {
                    foreach($attributes as $key=>$value) {
                        if($key == $trait->getAttribute()) {
                            $trait->setNormalValue($value);
                        }
                    }
                }
            }
            return $this;
        }
        public function getFilters() {
            return (new FilterCollection());
        }
        public function isEqual($key, $value) {
            return $this->getFilters()->isEqual($key, $value);
        }
        public function notEqual($key, $value) {
            return $this->getAdapter()->notEqual($key, $value);
        }
        public function isNull($key) {
            return $this->getAdapter()->isNull($key);
        }
        public function notNull($key) {
            return $this->getAdapter()->notNull($key);
        }
        public function isLike($key, $value) {
            return $this->getAdapter()->isLike($key, $value);
        }
        public function findFilters($type, $filters, $operators) {
            $filterCollection = $this->getFilters();
            if(count($filters ?? [])) {
                $attributes = [];
                $operations = [];
                foreach($filters as $key=>$value) {
                    $keys = explode(",", $key);
                    if(count($keys ?? []) > 1) {
                        $orGroup = $this->getFilters()->setGroup("OR");
                        for($i = 0; $i < count($keys); $i++) {
                            $filter = $this->getResolver()->getAttributeFilters($type, [$keys[$i]=>$value], [$keys[$i]=>$operators[$key]]);
                            if($filter->length()) {
                                $orGroup->appendFilters($filter);
                            }
                        }
                        if($orGroup->length()) {
                            $filterCollection->appendFilters($orGroup);
                        }
                    }
                    else {
                        $filter = $this->getResolver()->getAttributeFilters($type, [$key=>$value], [$key=>$operators[$key]]);
                        if($filter->length()) {
                            $filterCollection->appendFilters($filter);
                        }
                    }
                }
            }
            return $filterCollection;
        }
        public function saveRecord($record) {
            $result = false;
            $data = [];
            $keys = [];
            if($record->getTraits()->length()) {
                foreach($record->getTraits()->getData() as $trait) {
                    $data[$trait->getId()] = $trait->getNativeValue();
                }
                if($record->isNew()) {
                    if($this->getResolver()->saveRecord(get_class($record), $data)) {
                        $record->isNew(false);
                        $type = get_class($record);
                        $dictionary = $this->fromDictionary($type);
                        $dictionary->addItem($type, $record);
                    }
                    else {
                        throw new \Exception("Failed saving new record due to adapter error.");
                    }
                }
                else {
                    if(!$this->getResolver()->updateRecord(get_class($record), $record->getTraits()->objectAt(0)->getNativeValue(), $data)) {
                        throw new \Exception("Failed updating record due to adapter error.");
                    }
                }
                $this->saveRelationships($record);
                $this->saveInverserelationships($record);
                $this->obfuscateRecord($record);
            }
            return $result;
        }
        public function saveRelationships($record) {
            if($record->getRelationships()->length()) {
                foreach($record->getRelationships()->getData() as $relationship) {
                    if($relationship->getLink() != null) {
                        $this->getResolver()->saveRelationship($this->getResolver()->getEntity($record->getClass()), $this->getResolver()->getEntity($relationship->getTarget()), $relationship->getLink(), $record->getTraits()->objectAt(0)->getNativeValue(), $relationship->getNativeKeys());
                    }
                }
            }
            return $this;
        }
        public function saveInverseRelationships($record) {
            if($record->getRelationships()->length()) {
                foreach($record->getRelationships()->getData() as $relationship) {
                    foreach($relationship->getData() as $inverse) {
                        $this->saveRelationships($inverse);
                        $this->obfuscateRecord($inverse);
                    }
                }
            }
            return $this;
        }
        public function destroyRecord($model) {
            if($this->getResolver()->destroyRecord($model->getClass(), $model->getTraits()->objectAt(0)->getNativeValue())) {
                $key = $this->getCollectionKey($model->getClass(), $model->getId());
                $this->deleteCachedItem($key);
                $dictionary = $this->fromDictionary($model->getClass());
                $dictionary->removeItem($key);
            }
            return $this;
        }
    }
?>
