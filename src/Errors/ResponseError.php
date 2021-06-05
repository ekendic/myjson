<?php
    namespace myJSON\Errors;
    use myJSON\Entities\Entity;
    class ResponseError extends Entity {
        public function __construct($code = "", $source = array(), $title = "", $detail = "") {
            parent::__construct(["code"=>$code, "source"=>$source, "title"=>$title, "detail"=>$detail]);
            $this->setType("error");
        }
        public function toArray() {
            return $this->getData();
        }
        public function toString() {
            $ret = "{" . "\"code\": \"" . str_replace("\\", "/", $this->code) . "\", \"source\": {";
            foreach($this->source as $i=>$v) {
                $ret .= "\"" . $i . "\": " . "\"" . str_replace("\\", "/", $v) . "\", ";
            }
            $ret = substr($ret, 0, -2);
            $ret .= "}, \"title\": \"" . str_replace("\\", "/", $this->title) . "\", " . "\"detail\": \"" . str_replace("\\", "/", $this->detail) . "\"" . "}";
            return $ret;
        }
        public function __toString() {
            return $this->toString();
        }
    }
 ?>
