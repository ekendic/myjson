<?php
namespace myJSON\Data\Controllers;

use myJSON\Data\Requests\JSONRequest;
use myJSON\Data\Responses\JSONResponse;

use myJSON\Data\Providers\PDOProvider;
use myJSON\Data\Entities\DecoratedEntity;
use myJSON\Data\Serializers\EntitySerializer;

class EntityController extends \Symfony\Bundle\FrameworkBundle\Controller\Controller {
    protected $request = null;
    protected $response = null;
    protected $data = null;
    protected $attributes = null;
    protected $relationships = null;

    public function getRequest() {
        if(!isset($this->request)) {
            $this->request = $request = new JSONRequest($_GET,$_POST,array(),$_COOKIE,$_FILES,$_SERVER);
        }
        return $this->request;
    }
    public function setRequest($request) {
        $this->request = $request;
        return $this;
    }
    public function getData() {
        return $this->getRequest()->getData();
    }
    public function getAttribute($name, $default = null) {
        return $this->getRequest()->getAttribute($name, $default);
    }
    public function getAttributes() {
        return $this->getRequest()->getAttributes();
    }
    public function getFiles() {
        return $this->getRequest()->getFiles();
    }
    public function getFile($name) {
        return $this->getRequest()->getFile($name);
    }
    public function getRelationshipData($name) {
        return $this->getRequest()->getRelationshipData($name);
    }
    public function getRelationshipId($name) {
        return $this->getRequest()->getRelationshipId($name);
    }
    public function getRelationship($name) {
        return $this->getRelationshipId($name);
    }
    public function getRelationships($name) {
        return $this->getRelationshipIdentities($name);
    }
    public function getInclude() {
        return $this->getRequest()->getInclude();
    }
    public function getRelationshipNames() {
        return $this->getRequest()->getRelationshipNames();
    }
    public function getRelationshipIdentities($name) {
        return $this->getRequest()->getRelationshipIdentities($name);
    }
    public function getQuery($default = null, $parameter = "query") {
        return $this->getRequest()->getQuery($default, $parameter);
    }
    public function getFilter($filter, $default = null, $parameter = "filters") {
        return $this->getRequest()->getFilter($filter, $default, $parameter);
    }
    public function getFilters($default = [], $parameter = "filters") {
        return $this->getRequest()->getFilters($default, $parameter);
    }
    public function getOperator($operator, $default = null, $parameter = "operators") {
        return $this->getRequest()->getOperator($operator, $default, $parameter);
    }
    public function getOperators($default = [], $parameter = "operators") {
        return $this->getRequest()->getOperators($default, $parameter);
    }
    public function getResource($data, $decorator, $code=200) {

        return new JSONResponse($data, $decorator, $code);
    }
    public function getCollection($data, $decorator, $code = 200) {
        return new JSONResponse($data, $decorator, $code);
    }
    public function getResponse($data = "", $code = 200) {
        return new JSONResponse($data, null, $code);
    }
}

?>
