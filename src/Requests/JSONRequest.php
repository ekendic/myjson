<?php
    namespace myJSON\Requests;
    use Symfony\Component\HttpFoundation\Request;
    class JSONRequest extends Request {
        protected $data = null;
        protected $relationships = null;
        protected $_attributes = null;
        protected $_files = null;
        public function getData() {
    		if(!isset($this->data)) {
        		$content = json_decode($this->getContent());
        		if($content !== null && $content !== false) {
        			$this->data = (isset($content->data) ? $content->data : null);
        		}
            }
    		return $this->data;
    	}
        public function getFiles() {
            return $this->files;
        }
        public function getFile($name) {
            return $this->getFiles()->get($name);
        }
        public function getAttribute($name, $default = null) {
            $attributes = (array) $this->getAttributes();
            return (isset($attributes[$name]) ? $attributes[$name] : $default);
        }
        public function getAttributes() {
            if(!isset($this->_attributes)) {
                $data = (array) $this->getData();
                if(isset($data, $data["attributes"])) {
                    $this->_attributes = $this->getData()->attributes;
                }
            }
            return $this->_attributes;
        }
        public function getRelationships() {
            if(!isset($this->relationships)) {
                $data = (array) $this->getData();
                if(isset($data, $data["relationships"])) {
                    $this->relationships = $this->getData()->relationships;
                }
            }
            return $this->relationships;
        }
        public function getRelationship($name) {
            $relationships = (array) $this->getRelationships();
            return (isset($relationships[$name]) ? $relationships[$name] : array());
        }
        public function getRelationshipData($name) {
            $relationship = (array) $this->getRelationship($name);
            return (isset($relationship["data"]) ? $relationship["data"] : array());
        }
        public function getRelationshipId($name) {
            $data = (array) $this->getRelationshipData($name);
            return (isset($data["id"]) ? $data["id"] : null);
        }
        public function getInclude() {
            return (count($this->getRelationshipNames()) ? $this->getRelationshipNames() : $this->get("include"));
        }
        public function getQuery($default, $parameter) {
            $ret = $this->get($parameter);
            if(!strlen($ret)) $ret = $default;
            return $ret;
        }
        public function getFilters($default, $parameter) {
            $ret = $this->get($parameter);
            if(!is_array($ret)) {
                $ret = $default;
            }
            return $ret;
        }
        public function getFilter($filter, $default, $parameter) {
            $filters = $this->getFilters([], $parameter);
            return (isset($filters[$filter]) ? $filters[$filter] : $default);
        }
        public function getOperators($default, $parameter) {
            $ret = $this->get($parameter);
            if(!is_array($ret)) {
                $ret = $default;
            }
            return $ret;
        }
        public function getOperator($operator, $default, $parameter) {
            $operators = $this->getOperators([], $parameter);
            return (isset($operators[$operator]) ? $operators[$operator] : $default);
        }
        public function getRelationshipNames() {
            $ret = [];
            $relationships = (array) $this->getRelationships();
            foreach($relationships as $key=>$name) {
                $ret[] = $key;
            }
            return $ret;
        }
        public function getRelationshipIdentities($name) {
            $identities = [];
            $relationships = (array) $this->getRelationshipData($name);
            foreach($relationships as $relationship) {
                $relationship = (array) $relationship;
                if(isset($relationship["id"])) $identities[] = $relationship["id"];
            }
            return $identities;
        }
    }
 ?>
