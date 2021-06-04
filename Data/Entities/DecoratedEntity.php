<?php
    namespace myJSON\Data\Entities;
    use myJSON\Data\Providers\PDOProvider;

    class DecoratedEntity extends PersistedEntity {
        protected $_properties = array();
        public function __construct($key = null) {
            parent::__construct($key);
        }
        public function initialize($key, $table = null, $_properties = null, $data = array()) {
            parent::initialize($key, $table, $data);
            $this->setProperties($_properties);
        }
        public function getProperties() {
            return $this->_properties;
        }
        public function setProperties($_properties = null) {
            $fields = null;
            if(isset($_properties)) {
                if(is_callable($_properties) && $_properties instanceof Closure) {
                    $fields = $_properties($this);
                }
                else {
                    $fields = $_properties;
                }
            }
            else {
                $fields = $this->getStore()->getProperties($this);
            }
            $this->_properties = $fields;
            return $this;
        }
        public function __get($property) {
            if(!isset($this->_properties[$property])) throw new \Exception("The requested property [{$property}] was not bound by " . get_class($this) . ". Available properties are [" . join(", ", array_keys($this->_properties)) . "]");
            return parent::__get($this->_properties[$property]);
        }
        public function __set($property, $value) {
            parent::__set((isset($this->_properties[$property]) ? $this->_properties[$property] : $property), $value);
            return $this;
        }
    }
 ?>
