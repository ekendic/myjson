<?php
    namespace myJSON\Responses;
    use Symfony\Component\HttpFoundation\Response;
    use Tobscure\JsonApi\Document;
    use Tobscure\JsonApi\Collection as BaseCollection;
    use Tobscure\JsonApi\Resource as BaseResource;

    use myJSON\Entities\Entity;
    use myJSON\Entities\PersistedEntity;
    use myJSON\Collections\Collection;
    use myJSON\Collections\PersistedCollection;
    use myJSON\Errors\ResponseError;
    use myJSON\Collections\ErrorCollection;

    class JSONResponse extends Response {
        protected $entity = null;
        protected $serializer = null;
        protected $document = null;
        protected $response = null;
        protected $data = null;
        public function __construct($data = null, $serializer = null, $status=200) {
            $this->setSerializer($serializer);
            $this->setData($data);
            $this->response = $this->toString();
            parent::__construct($this, $status);
            $this->headers->set("Content-Type", "application/json");
            if(isset($this->serializer)) {
                if($this->getSerializer()->getErrors()->length()) {
                    $this->setStatusCode(400);
                    parent::setContent((string) $this->getSerializer()->getErrors());
                }
            }
        }
        public function getDocument() {
            return $this->document;
        }
        public function setDocument(Document $document) {
            $this->document = $document;
            return $this;
        }
        public function getResource() {
            $document = new Document();
            if($this->getData()->hasData()) {
                $resource = new BaseResource($this->getData(), $this->getSerializer());
                $include = $this->getSerializer()->getInclude();
                $resource->with($include);
                $document->setData($resource);
            }
            return $document;
        }
        public function getCollection() {
            $document = new Document();
            if($this->getData()->hasData()) {
                $collection = new BaseCollection($this->getData()->getData(), $this->getSerializer());
                $include = $this->getSerializer()->getInclude();
                $collection->with($include);
                $document->setData($collection);
            }
            return $document;
        }
        public function getData() {
            return $this->data;
        }
        public function setData($data) {
            $this->data = $data;
            return $this;
        }
        public function getSerializer() {
            return $this->serializer;
        }
        public function setSerializer($serializer) {
            $this->serializer = $serializer;
            return $this;
        }
        public function toString() {
          try {
            if(!isset($this->response)) {
                if($this->data instanceof Entity) {
                    if($this->getData()->hasData()) {
                        $this->response = \json_encode($this->getResource());
                    }
                    else {
                        $this->response = (string) (new ResponseError(400, ["class"=>get_class($this)], "Response Error", "No data loaded for entity."));
                    }
                }
                else if($this->data instanceof Collection) {
                    if($this->getData()->hasData()) {
                        $this->response = \json_encode($this->getCollection());
                    }
                    else {
                        $this->response = (string) (new ResponseError(400, ["class"=>get_class($this)], "Response Error", "No data loaded for collection."));
                    }
                }
                else {
                    $this->response = \json_encode($this->data);
                }
            }
          }
          catch(\Exception $ex) {
            $this->response = $ex->getMessage();
          }
          return $this->response;
        }
        public function __toString() {
            return $this->toString();
        }
    }
 ?>
