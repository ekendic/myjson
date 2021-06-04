<?php
    namespace myJSON\Data\Entities;
    use myJSON\Data\Providers\PDOProvider;
    use myJSON\Data\Errors\ResponseError;

    class Entity extends \myJSON\Data\Library\Base {
        protected $_attributes = null;
        public function __construct($data = null) {
            parent::__construct("_entity");
            parent::setData(isset($data) ? $data : array());
        }
        public function __get($attribute) {
            return $this->getValue($attribute);
        }
        public function __set($property, $value) {
            return $this->setValue($property, $value);
        }
        public function getValue($attribute) {
            return $this->getData()[$attribute];
        }
        public function setValue($attribute, $value) {
            $this->getData()[$attribute] = $value;
            return $this;
        }
        public function setAttributes($_attributes = null) {
            $set = [];
            if(isset($_attributes)) {
                if(is_callable($_attributes) && $_attributes instanceof Closure) {
                    $set = $_attributes($this);
                }
                else {
                    $set = $_attributes;
                }
            }
            else {
                $_attributes = array_keys($this->getData());
                foreach($_attributes as $attribute) {
                    $set[$attribute] = $attribute;
                }
            }
            $this->_attributes = $set;
            return $this;
        }
        public function getAttributes() {
            return $this->_attributes;
        }
        public function isValid() {
            return $this->hasData();
        }
        public function hasData() {
            return count(parent::getData() ?? []);
        }
    }
 ?>
