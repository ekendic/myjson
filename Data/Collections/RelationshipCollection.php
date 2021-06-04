<?php
    namespace myJSON\Data\Collections;
    class RelationshipCollection extends Collection {
        public function findByTarget($target) {
            $result = null;
            foreach($this->getData() as $relationship) {
                if($relationship->getTarget() == $target) {
                    $result = $relationship;
                }
            }
            return $result;
        }
        public function toArray() {
            $data = [];
            if($this->length()) {
                foreach($this->getData() as $relationship) {
                    $data[] = [
                        "entity"=>$relationship->getEntity()->toArray(false),
                        "target"=>$relationship->getTarget(),
                        "link"=>$relationship->getLink(),
                        "includes"=>$relationship->toArray()
                    ];
                }
            }
            return $data;
        }
    }
?>
