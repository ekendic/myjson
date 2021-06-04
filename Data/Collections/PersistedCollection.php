<?php
    namespace myJSON\Data\Collections;
    use myJSON\Data\Stores\PDOStore;
    use myJSON\Data\Entities\DecoratedEntity;
    use myJSON\Data\Entities\PersistedEntity;
    use myJSON\Data\Errors\ResponseError;

    class PersistedCollection extends Collection {
        public function __set($property, $value) {
            $this->forEach(function($entity) use($property, $value) {
                $entity->$property = $value;
            });
        }
        public function isValid() {
            return (count($this->getData() ?? []));
        }
    }
 ?>
