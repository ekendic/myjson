<?php
    namespace myJSON\Data\Models;
    use myJSON\Data\Entities\DecoratedEntity;
    class Model extends DecoratedEntity {
        public function __construct(...$key) {
            parent::__construct($key);
        }
    }
 ?>
