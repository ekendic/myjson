<?php
    namespace myJSON\Data\Decorators;
    use myJSON\Data\Serializers\EntitySerializer;
    use myJSON\Data\Serializers\Serializer;
    use myJSON\Data\Entities\PersistedEntity;
    use myJSON\Data\Errors\ResponseError;
    use myJSON\Data\Entities\Entity;
    use myJSON\Data\Collections\Collection;
    use myJSON\Data\Collections\PersistedCollection;
    use Tobscure\JsonApi\Collection as BaseCollection;
    use Tobscure\JsonApi\Resource as BaseResource;
    use myJSON\Data\Relationships\Relationship as BaseRelationship;

    class Decorator extends EntitySerializer {
        public function __construct($include = null) {
            if(isset($include)) {
                if(is_callable($include) && $include instanceof Closure) {
                    $include = $include($this);
                }
                else {
                    if(!is_array($include)) {
                        $include = [$include];
                    }
                }
            }
            parent::__construct($include);
        }
        public function serialize($data, $decorator) {
            $ret = null;
            if(isset($data)) {
                if($data instanceof Collection) {
                    $collection = (new BaseCollection($data->getData(), $decorator))->with($decorator->getInclude());
                    $ret = (new BaseRelationship($collection));
                }
                else if($data instanceof Entity) {
                    if(count($data->getData())) {
                        $resource = (new BaseResource($data, $decorator))->with($decorator->getInclude());
                        $ret = (new BaseRelationship($resource));
                    }
                    else {
                        $ret = (new BaseRelationship());
                    }
                }
                else {
                    throw new \Exception("Unable to serialize " . \print_r($data, true) . " as JSON resource.");
                }
            }
            return $ret;
        }
        public function getRelationships() {
            return array_filter(get_class_methods(get_class($this)), function($value) {
                return !in_array($value, get_class_methods(__CLASS__));
            });
        }
        public function includeAll() {
            $this->setInclude($this->getRelationships());
            return $this;
        }
    }
 ?>
