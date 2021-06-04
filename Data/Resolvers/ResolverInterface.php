<?php
    namespace myJSON\Data\Resolvers;

    interface ResolverInterface {
        public function getEntity($type);
        public function findRecord($type, $keys);
        public function findAll($type, FilterCollection $filters);
        public function getProperties($type);
        public function getAttributes($type);
        public function getTraits($type);
    }
 ?>
