<?php
    namespace myJSON\Relationships;
    use myJSON\Collections\Collection;
    class PersistedRelationship extends Collection {
        private $_target = null;
        private $_link = null;
        private $_entity = null;

        public function getEntity() {
            return $this->_entity;
        }
        public function setEntity($value) {
            $this->_entity = $value;
            return $this;
        }
        public function getTarget() {
            return $this->_target;
        }
        public function setTarget($value) {
            $this->_target = $value;
            return $this;
        }
        public function getLink() {
            return $this->_link;
        }
        public function setLink($value) {
            $this->_link = $value;
            return $this;
        }
        public function isLink($value) {
            if(isset($value)) self::setLink($value);
            return isset($this->_link);
        }
        public function toArray($recurse = true) {
            $data = [];
            foreach($this->getData() as $record) {
                $data[] = $record->toArray(false);
            }
            return $data;
        }
        public function getNormalKeys() {
            $keys = [];
            foreach($this->getData() as $record) {
                $keys[] = $record->getId();
            }
            return $keys;
        }
        public function getNativeKeys() {
            $keys = [];
            foreach($this->getData() as $record) {
                $keys[] = $record->getTraits()->objectAt(0)->getNativeValue();
            }
            return $keys;
        }
    }
?>
