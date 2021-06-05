<?php
    namespace myJSON\Models;
    use myJSON\Entities\DecoratedEntity;
    class Model extends DecoratedEntity {
        public function __construct(...$key) {
            parent::__construct($key);
        }
    }
 ?>
