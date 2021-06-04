<?php
    namespace myJSON\Data\Collections;
    use myJSON\Data\Builders\FilterBuilder;

    class FilterCollection extends Collection {
        private $group = "AND";
        public function attribute($key, $value, $operator) {
            $this->addData($this->getFilterBuilder()->attribute($key, $value, $operator));
            return $this;
        }
        public function getFilterBuilder() {
            return (new FilterBuilder());
        }
        public function getGroup() {
            return $this->group;
        }
        public function setGroup($value) {
            $this->group = $value;
            return $this;
        }
        public function isEqual($key, $value) {
            return $this->addData($this->getFilterBuilder()->isEqual($key, $value));
        }
        public function notEqual($key, $value) {
            return $this->addData($this->getFilterBuilder()->notEqual($key, $value));
        }
        public function isNull($key) {
            return $this->addData($this->getFilterBuilder()->isNull($key));
        }
        public function notNull($key) {
            return $this->addData($this->getFilterBuilder()->notNull($key));
        }
        public function isLike($key, $value) {
            return $this->addData($this->getFilterBuilder()->isLike($key, $value));
        }
        public function appendFilters($collection = null) {
            $this->addData($collection);
            return $this;
        }
        public function getParameters() {
            $parameters = [];
            foreach($this->getData() as $instance) {
                foreach($instance->getParameters() as $key=>$value) {
                    $parameters[$key] = $value;
                }
            }
            return $parameters;
        }
    }
?>
