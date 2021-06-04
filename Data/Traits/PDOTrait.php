<?php
    namespace myJSON\Data\Traits;
    use myJSON\Data\Library\Base;
    class PDOTrait extends Base {
        private $resolver = null;
        private $size = null;
        private $key = false;
        private $required = false;
        private $default = null;
        private $attribute = null;
        private $property = null;
        private $identity = false;
        private $entity = null;
        public function __construct($data = null) {
            parent::__construct("trait");
            $this->setData($data);
        }
        public function getResolver() {
            return $this->resolver;
        }
        public function setResolver($resolver) {
            $this->resolver = $resolver;
            return $this;
        }
        public function getKey() {
            return $this->key;
        }
        public function isKey($value = null) {
            if(isset($value)) self::setKey($value);
            return self::getKey();
        }
        public function setKey($value) {
            $this->key = $value;
            return $this;
        }
        public function getAttribute() {
            return $this->attribute;
        }
        public function setAttribute($value) {
            $this->attribute = $value;
            return $this;
        }
        public function getProperty() {
            return $this->property;
        }
        public function setProperty($value) {
            $this->property = $value;
            return $this;
        }
        public function getIdentity() {
            return $this->identity;
        }
        public function setIdentity($value) {
            $this->identity = $value;
            return $this;
        }
        public function isIdentity($value = null) {
            if(isset($value)) self::setIdentity($value);
            return self::getIdentity();
        }
        public function getRequired() {
            return $this->required;
        }
        public function isRequired($value = null) {
            if(isset($value)) self::setRequired($value);
            return self::getRequired();
        }
        public function setRequired($value) {
            $this->required = $value;
            return $this;
        }
        public function getDefault() {
            return $this->default;
        }
        public function setDefault($value) {
            $this->default = $value;
            return $this;
        }
        public function getField() {
            return $this->getId();
        }
        public function setField($value) {
            return $this->setId($value);
        }
        public function getSize() {
            return $this->size;
        }
        public function setSize($value) {
            $this->size = $value;
            return $this;
        }
        public function getEntity() {
            return $this->entity;
        }
        public function setEntity($value) {
            $this->entity = $value;
            return $this;
        }
        public function getNativeValue() {
            $value = null;
            if($this->getEntity() instanceof \myJSON\Data\Entities\Entity) {
                if($this->getEntity()->isValid()) {
                    $data = $this->getEntity()->getData();
                    if(isset($data[$this->getId()])) {
                        $value = $data[$this->getId()];
                    }
                }
            }
            return $value;
        }
        public function __get($property) {
            return $this->getData()->{$property};
        }
        public function __set($property, $value) {
            $this->getData()->{$property} = $value;
        }
        public function getNormalValue() {
            return $this->getResolver()->encodeValue($this->getNativeValue(), $this->getType());
        }
        public function setNormalValue($parameter) {
            $this->setNativeValue($this->getResolver()->decodeValue($parameter, $this->getType()));
            return $this;
        }
        public function setNativeValue($parameter) {
            $this->getEntity()->__set($this->getId(), $parameter);
            return $this;
        }
        public function toArray() {
            $data = [
                "id"=>$this->getId(),
                "type"=>$this->getType(),
                "size"=>$this->getSize(),
                "key"=>$this->getKey(),
                "required"=>$this->getRequired(),
                "default"=>$this->getResolver()->encodeValue($this->getDefault(), $this->getType()),
                "attribute"=>$this->getAttribute(),
                "property"=>$this->getProperty()
            ];
            return $data;
        }
    }
?>
