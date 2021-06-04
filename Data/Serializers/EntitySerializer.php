<?php
    namespace myJSON\Data\Serializers;
    use Tobscure\JsonApi\SerializerInterface;
    use myJSON\Data\Relationships\Relationship;
    use myJSON\Data\Errors\ResponseError;
    use myJSON\Data\Collections\ErrorCollection;

    class EntitySerializer implements SerializerInterface {
        protected $include = array();
        protected $errors = null;
        protected static $relationships = [];
        public function __construct($include = null) {
            if(isset($include) && !is_array($include)) $include = [$include];
            $this->errors = new ErrorCollection();
            $this->setInclude($include);
        }
        public function getType($entity) {
            return $entity->getType();
        }
        public function setInclude($include) {
            $this->include = $include;
            return $this;
        }
        public function getInclude() {
            return $this->include;
        }
        public function getId($entity) {
            return $entity->getId();
        }
        public function getAttributes($entity, array $fields = null) {
            $ret = array();
            if($entity->isValid()) {
                foreach($entity->getTraits()->getData() as $trait) {
                    if(!$trait->isIdentity()) {
                        $ret[$trait->getAttribute()] = $trait->getNormalValue();
                    }
                }
            }
            return $ret;
        }
        public function getAttributeStrings($entity) {
            $ret = [];
            if($entity->isValid()) {
                foreach($entity->getAttributes() as $key=>$value) {
                    $ret[] = $key . "=>" . $value;
                }
            }
            return $ret;
        }
        public function getErrors() {
            return $this->errors;
        }
        public function getLinks($model)
        {
            return [];
        }
        public function obfuscateRelationship($entity, $name, $relationship) {
            self::$relationships[$entity->getType() . "." . $entity->getId() . "." . $name] = $relationship;
        }
        public function clarifyRelationship($entity, $name) {
            $key = $entity->getType() . "." . $entity->getId() . "." . $name;
            $keys = array_keys(self::$relationships);
            return (in_array($key, $keys) ? self::$relationships[$key] : null);
        }
        public function getMeta($model)
        {
            return [];
        }
        public function getRelationship($entity, $name)
        {
            $ret = null;
            $relationship = $this->clarifyRelationship($entity, $name);
            if(empty($relationship)) {
                $error = null;
                $method = $this->getRelationshipMethodName($name);
                if (method_exists($this, $method)) {
                    $relationship = $this->$method($entity);
                    $this->obfuscateRelationship($entity, $name, $relationship);
                    if ($relationship !== null) {
                        if(($relationship instanceof Relationship)) {
                            $ret = $relationship;
                        }
                        else {
                            $this->getErrors()->addData(new ResponseError(500, ["class"=>get_class($this)], "Database Error", sprintf("Relationship [%s] is returning invalid type in %s.", $name, get_class($this))));
                        }
                    }
                }
                else {
                    $error = new ResponseError(500, ["class"=>get_class($this)], "Database Error", sprintf("Relationship [%s] was not found in %s.", $name, get_class($this)));
                }
                if(isset($error)) {
                    $this->getErrors()->addData($error);
                }
            }
            return $ret;
        }
        private function getRelationshipMethodName($name)
        {
            if (stripos($name, '-')) {
                $name = lcfirst(implode('', array_map('ucfirst', explode('-', $name))));
            }

            return $name;
        }
    }
?>
