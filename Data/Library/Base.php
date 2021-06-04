<?php

	namespace myJSON\Data\Library;

	use myJSON\Data\Errors\ResponseError;
	use myJSON\Data\Collections\ErrorCollection;

	class Base {
		protected $_cache = null;
		protected $_errors = null;
		protected $_id = 0;
		protected $_type = "";
		protected $_name = "";
		protected $_class = "";
		protected $_data = null;

		public function __construct($type = "base") {
			$this->_type = $type;
			$this->setId(uniqid($type));
		}
		public function getType() {
			return $this->_type;
		}
		public function setType($type = null) {
			if(is_string($type)) {
				$this->_type = $type;
			}
			else {
				$this->addError(new ResponseError(500, ["class"=>$this->getClass()], "Unexpected Value", "Attempted to set type to non-string."));
			}
			return $this;
		}
		public function getUID() {
			return $this->getType() . $this->getId();
		}
		public function getId($obj=null) {
			return $this->_id;
		}
		public function setId($id) {
			$this->_id = (isset($id) ? $this->stringify($id) : "null");
			return $this;
		}
		public function setCache($_cache) {
            $this->_cache = $_cache;
			return $this;
        }
		public function getName() {
            return $this->_name;
        }
        public function setName($value) {
            $this->_name = $value;
            return $this;
        }
		public function getClass() {
			return $this->_class;
		}
		public function setClass($value) {
			$this->_class = $value;
			return $this;
		}
        public function toCache($key, $value, $tags=[], $expiration=3600) {
            $item = null;
            if(isset($this->_cache)) {
                $item = $this->_cache->getItem($key);
                $item->set($value);
				$item->tag($tags);
				$item->expiresAfter($expiration);
                $this->_cache->save($item);
            }
            return $item;
        }
		public function deleteCachedItem($key) {
			$ret = true;
			if(isset($this->_cache)) {
				if($this->_cache->hasItem($key)) {
					$this->_cache->deleteItem($key);
				}
			}
			else {
				$ret = false;
			}
			return $ret;
		}
		public function loadCache($default = null) {
			$this->_data = $this->fromCache($this->getType() . "." . $this->getId()) ?? $default;
			return $this;
		}
		public function saveCache() {
			$this->toCache($this->getType() . "." . $this->getId(), $this->_data);
			return $this;
		}
		public function &getData() {
			return $this->_data;
		}
		public function setData($data) {
			$this->_data = $data;
			return $this;
		}
		public function deleteCachedItems($keys) {
			$ret = true;
			try {
				if(isset($this->_cache)) {
					$this->_cache->deleteItems($keys);
				}
			}
			catch(\Exception $ex) {
				$ret = false;
			}
			return $ret;
		}
        public function fromCache($key) {
            $value = null;
            if(isset($this->_cache)) {
                $item = $this->_cache->getItem($key);
                if($item->isHit()) {
                    $value = $item->get();
                }
            }
            return $value;
        }
		public static function transform($value, callable $callback) {
			return $callback($value);
		}
		public function addError(ResponseError $error) {
			$this->getErrors()->addData($error);
			return $this;
		}
		public function getErrors() {
			if(!isset($this->_errors)) {
				$this->_errors = new ErrorCollection();
			}
			return $this->_errors;
		}
		public function stringify($value) {
			$ret = "";
			if(!isset($value) && !is_string($value) && !is_numeric($value) && !is_callable(array($value, '__toString'))) {
				$this->addError(new ResponseError(500, ["class"=>get_class($this)], "Unexpected Value", sprintf("The value provided to %s::stringify must be a string or object implementing __toString().", $this->getClass())));
			}
			$ret = (string) $value;
			return $ret;
		}
		public static function fromISODateTime($date) {
			return date("Y-m-d H:i:s", strtotime($date));
		}
		public static function fromISODate($date) {
			return date("Y-m-d", strtotime($date));
		}
		public static function fromUTCDateTime($date) {
			return date('Y-m-d H:i:s', strtotime(substr($date, 0, strpos($date, '('))));
		}
		public static function fromUTCDate($date) {
			return date('Y-m-d', strtotime(substr($date, 0, strpos($date, '('))));
		}
		public function getCacheKey(...$segments) {
			$id = $this->getType();
			foreach($segments as $segment) {
				$id .= "." . stripslashes($segment);
			}
	        return $id;
	    }
	}
?>
