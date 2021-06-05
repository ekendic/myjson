<?php
    namespace myJSON\Collections;
    use myJSON\Stores\PDOStore;
    use myJSON\Entities\DecoratedEntity;
    use myJSON\Entities\PersistedEntity;
    use myJSON\Errors\ResponseError;

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
