<?php
    namespace myJSON\Entities;
    use myJSON\Collections\PersistedCollection;
    use myJSON\Collections\RelationshipCollection;
    use myJSON\Relationships\PersistedRelationship;
    use myJSON\Errors\ResponseError;
    use myJSON\Collections\TraitCollection;

    class PersistedEntity extends Entity  {
        protected $_store = null;
        protected $_traits = null;
        protected $relationships = null;
        protected $_new = true;
        public function __construct($key = null) {
            parent::__construct($key);
            $this->relationships = new RelationshipCollection();
        }
        public function setStore($_store) {
            $this->_store = $_store;
            return $this;
        }
        public function getStore() {
            return $this->_store;
        }
        public function getTraits() {
            return $this->_traits;
        }
        public function setTraits($value) {
            $this->_traits = $value;
            return $this;
        }
        public function getNew() {
            return $this->_new;
        }
        public function isNew($value = null) {
            if(isset($value)) self::setNew($value);
            return self::getNew();
        }
        public function setNew($value) {
            $this->_new = $value;
            return $this;
        }
        public function getRelationships() {
            return $this->relationships;
        }
        public function setRelationships($relationships) {
            $this->relationships = $relationships;
            return $this;
        }
        public function setAttributes($attributes = null) {
            parent::setAttributes((isset($attributes) ? $attributes : $this->getStore()->getAdapter()->getResolver()->getAttributes($this)));
            return $this;
        }
        public function updateRelationship($target, $link=null, $accept=[]) {
            $this->getStore()->updateRelationship($this, $target, $link, $accept);
            return $this;
        }
        public function updateInverseRelationship($attribute, $target) {
            $existing = $this->getNormalValue($attribute);
            $this->getStore()->removeInverseRelationship($this, $existing);
            $this->getStore()->addInverserelationship($this, $target);
            return $this;
        }
        public function getRelationship($target, $link = null) {
            $relationship = $this->getRelationships()->findByTarget($target);
            if(!isset($relationship)) {
                $relationship = $this->getStore()->getRelationship($this, $target, $link);
                if(isset($relationship)) $this->getRelationships()->addItem($target, $relationship);
            }
            return $relationship;
        }

        public function save() {
            $this->getStore()->saveRecord($this);
            return $this;
        }
        public function updateAttributes($attributes) {
            $this->getStore()->updateRecordAttributes($this, $attributes);
            return $this;
        }
        public function destroyRecord() {
            $this->getStore()->destroyRecord($this);
            return $this;
        }
        public function __get($attribute) {
            return $this->getNormalValue($attribute);
        }
        public function getNativeValue($attribute) {
            return parent::getValue($attribute);
        }
        public function getNormalValue($attribute) {
            $value = null;
            $trait = $this->getTraits()->filterBy('Field', $attribute)->objectAt(0);
            if(isset($trait)) {
                if($trait->isIdentity() && !$trait->isKey()) {
                    $value = $this->getStore()->findRecord($this->getStore()->getModel($trait->getProperty()), $trait->getNormalValue());
                }
                else {
                    $value = $this->getStore()->getResolver()->encodeValue(parent::__get($attribute), $trait->getType());
                }
            }
            else {
                throw new \Exception("Unable to find trait for {$attribute} attribute.");
            }
            return $value;
        }
        public function setNativeValue($attribute, $value) {
            parent::__set($attribute, $value);
            return $this;
        }
        public function setNormalValue($attribute, $parameter) {
            $value = null;
            $trait = $this->getTraits()->filterBy('Field', $attribute)->objectAt(0);
            if(isset($parameter)) {
                if($parameter instanceof \myJSON\Entities\PersistedEntity) {
                    $value = ($parameter)->getId();
                    if(!$this->isNew()) {
                        $this->updateInverseRelationship($attribute, $parameter);
                    }
                }
                else {
                    $value = $parameter;
                }
            }
            else {
                $value = $trait->getDefault();
            }
            if($trait->isRequired() && !isset($value)) {
                throw new \Exception("Create record expected a value for required field " . $trait->getId() . " as " . $trait->getType() . " type.");
            }
            return $this->setNativeValue($attribute, $trait->getResolver()->decodeValue($value, $trait->getType()));
        }
        public function __set($attribute, $parameter) {
            return $this->setNormalValue($attribute, $parameter);
        }
        public function toArray($includes=true) {
            $data = ["id"=>$this->getId(),
                "class"=>get_class($this),
                "type"=>$this->getType(),
                "name"=>$this->getName()
            ];
            if($includes) {
                $data["attributes"] = $this->getAttributes();
                $data["properties"] = $this->getProperties();
            }
            if($includes && $this->isValid() && $this->_traits->length()) {
                $data["traits"] = [];
                $data["data"] = [];
                foreach($this->_traits->getData() as $trait) {
                    $data["data"][$trait->getId()] = $trait->getNormalValue();
                    $data["traits"][$trait->getId()] = $trait->toArray();
                }
            }
            if($includes) {
                $data["relationships"] = $this->getRelationships()->toArray();
            }
            return $data;
        }
        public function __toString() {
            return \json_encode((object) $this->toArray());
        }
    }
 ?>
