<?php
    namespace myJSON\Data\Collections;
    class TraitCollection extends Collection {
        public function findByAttribute($attribute) {
            $result = null;
            foreach($this->getData() as $trait) {
                if(!isset($result)) {
                    if($trait->getAttribute() == $attribute) {
                        $result = $trait;
                    }
                }
            }
            return $result;
        }
        public function findByProperty($property) {
            $result = null;
            foreach($this->getData() as $trait) {
                if(!isset($result)) {
                    if($trait->getProperty() == $property) {
                        $result = $trait;
                    }
                }
            }
            return $result;
        }
        public function findById($id) {
            $result = null;
            foreach($this->getData() as $trait) {
                if(!isset($result)) {
                    if($trait->getId() == $id) {
                        $result = $trait;
                    }
                }
            }
            return $result;
        }
        public function setEntity($entity) {
            $this->forEach(function($trait) use($entity){
                $trait->setEntity($entity);
            });
            return $this;
        }
        public function getNativeIdentities() {
            $identities = [];
            if($this->length()) {
                foreach($this->getData() as $trait) {
                    if($trait->isIdentity()) {
                        $keys[] = $trait->getNativeValue();
                    }
                }
            }
        }
        public function getNormalIdentities() {
            $identities = [];
            if($this->length()) {
                foreach($this->getData() as $trait) {
                    if($trait->isIdentity()) {
                        $keys[] = $this->getNormalValue();
                    }
                }
            }
        }
    }
?>
