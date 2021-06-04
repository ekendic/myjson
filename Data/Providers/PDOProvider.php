<?php
    namespace myJSON\Data\Providers;
    use myJSON\Data\Errors\ResponseError;
    use myJSON\Data\Resolvers\PDOResolver;

    class PDOProvider extends \myJSON\Data\Library\Base {
        protected static $global_dsn = null;
        protected static $global_username = null;
        protected static $global_password = null;
        protected static $global_options = null;

        protected static $global_table = null;
        protected static $global_column = null;
        protected static $global_attributes = null;
        protected static $global_type = null;
        protected static $global_collection = null;

        protected static $cache_tables = [];
        protected static $global_resolver = null;

        protected $dsn = null;
        protected $username = null;
        protected $password = null;
        protected $options = null;
        protected static $global_driver = null;
        protected $driver = null;
        public function __construct($dsn = null, $username = null, $password = null, $options = null) {
            $this->setType("provider");
            $this->setDSN((isset($dsn) ? $dsn : $this->getGlobalDSN()));
            $this->setUsername((isset($username) ? $username : $this->getGlobalUsername()));
            $this->setPassword((isset($password) ? $password : $this->getGlobalPassword()));
            $this->setOptions((isset($options) ? $options : $this->getGlobalOptions()));
            $this->setDriver($this->getGlobalDriver());
        }
        public static function getGlobalResolver() {
            return self::$global_resolver;
        }
        public static function setGlobalResolver($resolver) {
            self::$global_resolver = $resolver;
        }
        public function getDriver() {
            return $this->driver;
        }
        public static function getGlobalDriver() {
            if(!isset(self::$global_driver)) self::setGlobalDriver(self::initDriver());
            return self::$global_driver;
        }
        public static function setGlobalDriver($driver) {
            self::$global_driver = $driver;
        }
        public static function initDriver() {
            //return new \PDO(self::$global_dsn, self::$global_username, self::$global_password, self::$global_options);
        }
        public function setDriver($driver) {
            $this->driver = $driver;
            return $this;
        }
        public static function setGlobalDSN($dsn) {
            self::$global_dsn = $dsn;
        }
        public static function getGlobalDSN() {
            return self::$global_dsn;
        }
        public static function setGlobalUsername($username) {
            self::$global_username = $username;
        }
        public static function getGlobalUsername() {
            return self::$global_username;
        }
        public static function setGlobalPassword($password) {
            self::$global_password = $password;
        }
        public static function getGlobalPassword() {
            return self::$global_password;
        }
        public static function setGlobalOptions($options) {
            self::$global_options = $options;
        }
        public static function getGlobalOptions() {
            return self::$global_options;
        }
        public function setDSN($dsn) {
            $this->dsn = $dsn;
            return $this;
        }
        public function getDSN() {
            return $this->dsn;
        }
        public function setUsername($username) {
            $this->username = $username;
            return $this;
        }
        public function getUsername() {
            return $this->username;
        }
        public function setPassword($password) {
            $this->password = $password;
            return $this;
        }
        public function getPassword() {
            return $this->password;
        }
        public function setOptions($options) {
            $this->options = $options;
            return $this;
        }
        public function getOptions() {
            return $this->options;
        }
        public static function quote($value) {
            return self::getGlobalDriver()->quote($value);
        }
        public static function SelectCommand($columns, $table, $suffix) {
			$ret = "SELECT ";
			if(is_array($columns)) {
                for($i = 0; $i < count($columns); $i++) $ret .= "`{$table}`.`{$columns[$i]}`" . ($i+1 < count($columns) ? ", " : " " );
            }
            else {
                $ret .= $columns . " ";
            }
			$ret .= "FROM `" . $table . "` " . $suffix;
			return $ret;
		}
		public static function UpdateCommand($table, $columnskeyvalue, $suffix) {
			$ret = "UPDATE `" . $table . "` SET ";
			$keys = array_keys($columnskeyvalue);
			for($i = 0; $i < count($keys); $i++) $ret .= "`" . $keys[$i] . "`" . "=" . (!is_null($columnskeyvalue[$keys[$i]]) ? self::getGlobalDriver()->quote(($columnskeyvalue[$keys[$i]] === FALSE ? 0 : $columnskeyvalue[$keys[$i]])) : "NULL") . ($i+1 < count($keys) ? ", " : " ");
			$ret .= $suffix;
			return $ret;
		}
		public static function InsertCommand($table, $columnskeyvalue) {
			$ret = "INSERT INTO `" . $table . "`(";
			$keys = array_keys($columnskeyvalue);
			$vals = "";
			for($i = 0; $i < count($keys); $i++) {
				$ret .= $keys[$i] . ($i+1 < count($keys) ? ", " : " ");
				$vals .= (!is_null($columnskeyvalue[$keys[$i]]) ? "'" . self::getGlobalDriver()->real_escape_string($columnskeyvalue[$keys[$i]]) . "'" : "NULL") . ($i+1 < count($keys) ? ", " : "");
			}
			$ret .= ") VALUES (" . $vals . ");";
			return $ret;
		}
		public static function DeleteCommand($table, $suffix) {
			$ret = "DELETE FROM `" . $table . "` " . $suffix . ";";
			return $ret;
		}
		public static function CallCommand($procfunc, &...$parameters) {
            $ret = array();
			$keys = [];
            $index = 1;
			foreach($parameters as $parameter) {
                if(!is_array($parameter)) {
                    $ret["SET"][] = "SET @param" . $index . "=" . (!is_null($parameter) ? self::getGlobalDriver()->quote(($parameter === FALSE ? 0 : $parameter)) : 'NULL');
                    $ret["PARAMS"][] = "@param" . $index++;
                }
            }
            $parameters = (isset($ret["PARAMS"]) ? $ret["PARAMS"] : array());
			$ret["CALL"] = "CALL {$procfunc}(" . (count($parameters) ? join(", ", $parameters) : "") . ");";
			return $ret;
		}
        public function getColumns($table) {
            self::getStaticColumns($table);
            if(!count(self::$cache_tables[$table])) {
                $this->addError(new ResponseError(500, ["class"=>get_class($this)], "Binding Error", sprintf("Unable to get table columns for %s. The table may not exist.", $table)));
            }
            return self::$cache_tables[$table];
        }
        public static function getStaticColumns($table) {
            if(!in_array($table, array_keys(self::$cache_tables))) {
                self::$cache_tables[$table] = self::staticQuery("SHOW COLUMNS FROM `{$table}`;", \PDO::FETCH_OBJ);
            }

            return self::$cache_tables[$table];
        }
        public static function getStaticDriver() {
            return self::initDriver();
        }
        public function getPrimaryColumns($table) {
            $columns = [];
            foreach($this->getColumns($table) as $column) {
                if($column->Key == "PRI") $columns[] = $column;
            }
            return $columns;
        }

        public function query($command, $type = \PDO::FETCH_ASSOC) {
            $ret = array();
            if($this->getDriver() != null) {
    			$result = $this->getDriver()->query($command);
    			if($result != false) {
    				if($result->rowCount() > 0) {
    					for($i = 0; $i < $result->rowCount(); $i++) {
    						$row = $result->fetch($type);
    						array_push($ret, $row);
    					}
    				}
    			}
    		}
    		return $ret;
        }
        public static function staticQuery($command, $type = \PDO::FETCH_ASSOC) {
            $ret = array();
    		$result = self::initDriver()->query($command);
			if($result != false) {
				if($result->rowCount() > 0) {
					for($i = 0; $i < $result->rowCount(); $i++) {
						$row = $result->fetch($type);
						array_push($ret, $row);
					}
				}
			}
    		return $ret;
        }

        //execute call to stored procedure
        public static function call($procfunc, &...$parameters) {
			$ret = false;
			$data = self::CallCommand($procfunc, ...$parameters);
            $set = (isset($data["SET"]) ? $data["SET"] : array());
            $call = (isset($data["CALL"]) ? $data["CALL"] : "");
            $query = join("; ", $set) . "; " . $call;
			if(($statement  = self::getGlobalDriver()->query($query)) !== FALSE) {
				$ret = true;
				$statement->closeCursor();
                if(count($parameters)) {
                    $query = "SELECT" . join(", ", $data["PARAMS"]) . ";";
                    if(($statement = self::getGlobalDriver()->query($query)) !== FALSE) {
                        if(($row = $statement->fetch(\PDO::FETCH_NUM)) !== FALSE) {
                            $statement->closeCursor();
                            $index = 0;
                            foreach($parameters as &$parameter) {
                                $parameter = $row[$index++];
                            }
                        }
                    }
                }
			}
			else {
                throw new \Exception("Failed to execute stored procedure. ");
			}
			return $ret;
		}

        //execute exec to function
        public function execute() {

        }

        //get last id affected by query
        public function getLastId() {

        }

        //get last error info;
        public function errorInfo() {

        }

        public function select($columns, $table, $suffix) {
    		$ret = array();
    		if($this->getDriver() != null) {
    			$result = $this->getDriver()->query($this->SelectCommand($columns, $table, $suffix));
    			if($result != false) {
    				if($result->rowCount() > 0) {
    					for($i = 0; $i < $result->rowCount(); $i++) {
    						$row = $result->fetch(\PDO::FETCH_ASSOC);
    						array_push($ret, $row);
    					}
    				}
    				else {
                        $this->addError(new ResponseError(500, ["class"=>get_class($this)], "Database Error", "No data in select query."));
    				}
    			}
    			else {
                    $this->addError(new ResponseError(500, ["class"=>get_class($this)], "Database Error", "Failed binding to data source due to query error."));
    			}
    		}
    		return $ret;
    	}
    	public function update($columnskeyvalue, $table, $suffix) {
    		$ret = false;
    		$result = $this->getDriver()->query($this->UpdateCommand(($table != null ? $table : $this->Table), $columnskeyvalue, ($suffix != null ? $suffix : $this->Suffix)));
    		if($result !== FALSE) {
    			$ret = true;
    		}
    		else {
                $this->addError(new ResponseError(500, ["class"=>get_class($this)], "Database Error", "Failed updating data."));
    		}
    		return $ret;
    	}
    	public function delete($table=null, $suffix=null) {
    		$ret = false;
    		$result = $this->getDriver()->query($this->DeleteCommand(($table != null ? $table : $this->Table), ($suffix != null ? $suffix : $this->Suffix)));
    		if($result) {
    			$ret = true;
    		}
    		return $ret;
    	}
    	public function insert($columnskeyvalue, $table=null) {
    		$ret = 0;
    		$result = $this->getDriver()->query($this->InsertCommand(($table != null ? $table : $this->Table), $columnskeyvalue));
    		if($result !== FALSE) {
    			$ret = self::getDriver()->insert_id;
    		}
    		else {
                $this->addError(new ResponseError(500, ["class"=>get_class($this)], "Database Error", "Failed inserting data."));
    		}
    		return $ret;
    	}
    }
 ?>
