<?php
    namespace myJSON\Collections;
    use myJSON\Library\Base;

    class Collection extends Base {
        public function __construct($data = array()) {
            parent::__construct("_collection");
            $this->setData($data ?? []);
        }
        public function setData($data) {
            if(is_array($data)) {
                parent::setData($data);
            }
            else {
                throw new \Exception("Invalid data type for collection. Must be array.");
            }
            return $this;
        }
        public function addItem($key, $value) {
            parent::getData()[$key] = $value;
            return $this;
        }
        public function getItem($key) {
            return (isset(parent::getData()[$key]) ? parent::getData()[$key] : null);
        }
        public function hasItem($key) {
            return isset(parent::getData()[$key]);
        }
        public function removeItem($key) {
            unset(parent::getData()[$key]);
            return $this;
        }
        public function addData($data) {
            if(isset($data)) {
                if(is_array($data)) {
                    foreach($data as $item) {
                        parent::getData()[] = $item;
                    }
                }
                else {
                    parent::getData()[] = $data;
                }
            }
            return $this;
        }
        public function objectAt($index) {
            $ret = null;
            if($index >= 0 && $index < $this->length()) {
                $key = 0;
                foreach(parent::getData() as $value) {
                    if(!isset($ret)) {
                        if($key++ == $index) $ret = $value;
                    }
                }
            }
            return $ret;
        }
        public function length() {
    		return count(parent::getData());
    	}
        public function exists() {
            return $this->hasData();
        }
        public function hasData() {
            return ($this->length() ? true: false);
        }
        public function shuffle() {
            shuffle(parent::getData());
    		return $this;
    	}
        public function reject(callable $callback) {
    		$ret = [];
    		foreach(parent::getData() as $row) {
    			if(!$callback($row)) {
    				$ret[] = $row;
    			}
    		}
    		return new static($ret);
    	}
    	public function rejectBy($key, $value=null) {
    		$ret = [];
    		foreach(parent::getData() as &$row) {
    			if(($value != null ? $row->$key != $value : !$row->$key)) {
    				$ret[] = $row;
    			}
    		}
    		return new static($ret);
    	}
    	public function filter(callable $callback) {
    		$ret = [];
    		foreach(parent::getData() as $row) {
    			if($callback($row)) {
    				$ret[] = $row;
    			}
    		}
    		return new static($ret);
    	}
    	public function filterBy($key, $value = null) {
    		$ret = [];
    		foreach(parent::getData() as $row) {
    			if(($value != null ? $row->$key == $value : $row->$key)) {
    				$ret[] = $row;
    			}
    		}
    		return new static($ret);
    	}
    	public function reduce($accept, callable $callback) {
    		$reject = $this->reject(function($row) use($accept, $callback) {
    			return in_array($row->getId(), $accept);
    		});
    		$reject->forEach(function($row) use($callback) {
    			$callback($row->getId());
    		});
    		return $this;
    	}
    	public function include($accept, callable $callback) {
    		$filtered = $this->filter(function($row) use($accept) {
    			return in_array($row->getId(), $accept);
    		});
    		foreach($accept as $id) {
                $found = false;
                if($filtered->length()) {
                    $keys = $filtered->getKeys();
                    for($i = 0; $i < count($keys) && !$found; $i++) {
                        $found = $id == $filtered->objectAt($i)->getId();
                    }
                }
    			if(!$found) $callback($id);
    		}
    		return $this;
    	}
        public function getKeys() {
            return array_keys(parent::getData());
        }
    	public function merge($accept, $add=null, $remove=null) {
            if(is_callable($remove)) {
        		$this->reduce($accept, function($id) use($remove) {
        			$remove($id);
        		});
            }
            if(is_callable($add)) {
                $this->include($accept, function($id) use($add) {
        			$add($id);
        		});
            }
    		return $this;
    	}
    	public function forEach(callable $callback) {
    		foreach($this->getData() as &$row) {
    			$callback($row);
    		}
    		return $this;
    	}
        public function sort($on, $order = SORT_ASC) {
            $this->setData((function($array, $on, $order)
            {
                $new_array = array();
                $sortable_array = array();

                if (count($array) > 0) {
                    foreach ($array as $k => $v) {
                        if (is_array($v)) {
                            foreach ($v as $k2 => $v2) {
                                if ($k2 == $on) {
                                    $sortable_array[$k] = $v2;
                                }
                            }
                        } else {
                            $sortable_array[$k] = $v;
                        }
                    }

                    switch ($order) {
                        case SORT_ASC:
                            asort($sortable_array);
                        break;
                        case SORT_DESC:
                            arsort($sortable_array);
                        break;
                    }

                    foreach ($sortable_array as $k => $v) {
                        $new_array[$k] = $array[$k];
                    }
                }
                return $new_array;
            })(parent::getData(), $on, $order));
            return $this;
        }
        public function sortBy($on, $order=SORT_ASC) {
            return $this->sort($on, $order);
        }
        public function reverse() {
            $this->setData(array_reverse(parent::getData()));
            return $this;
        }
    }
 ?>
