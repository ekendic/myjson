<?php
    namespace myJSON\Collections;
    class ErrorCollection extends Collection {
        public function getErrors() {
            return $this->getData();
        }
        public function toString() {
            $ret = "";
            if($this->length()) {
                $errors = array_unique($this->getData());
                $ret = "{\"errors\": [";
                $total = count($errors);
                $i = 0;
                foreach($errors as $error) {
                    $ret .= $error . (++$i < $total ? "," : "");
                }
                $ret .= "]}";
            }
            return $ret;
        }
        public function __toString() {
            return $this->toString();
        }
    }
 ?>
