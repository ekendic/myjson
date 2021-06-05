<?php
    namespace myJSON\Builders;
    use myJSON\Library\Base;
    use myJSON\Collections\FilterCollection;
    use myJSON\Builders\FilterBuilder;
    class QueryBuilder extends Base {
        private $query = "";
        public function append($str) {
            $this->query .= $str;
            return $this;
        }
        public function __toString() {
            return $this->toString();
        }
        public function toString() {
            return $this->query;
        }
        public function getColumns($columns, $table = null) {
            $list = [];
            foreach($columns as $column) {
                $list[] = $this->getColumn($column->Field, $table);
            }
            return $list;
        }
        public function getColumn($column, $table = null) {
            return (isset($table) ? "`{$table}`." : "") . "`{$column}`";
        }
        public function match($attributes, $table = null) {
            if($attributes instanceof FilterCollection) {
                $k = 0;
                foreach($attributes->getData() as $filter) {
                    if($k++ > 0) $this->append(" {$attributes->getGroup()} ");
                    $this->filter($filter, $table);
                }
            }
            else {
                $this->filter($attributes, $table);
            }
            return $this;
        }
        public function filter($filter, $table=null) {
            if($filter instanceof \myJSON\Builders\FilterBuilder) {
                $this->append((isset($table) ? "`{$table}`." : "") . ((string) $filter));
            }
            else {
                if($filter instanceof \myJSON\Collections\FilterCollection) {
                    if($filter->length() > 1) {
                        $this->append("(")->match($filter, $table)->append(")");
                    }
                    else {
                        $this->match($filter, $table);
                    }
                }
            }
            return $this;
        }
    }
 ?>
