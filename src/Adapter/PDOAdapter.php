<?php
    namespace myJSON\Adapter;

    use Symfony\Component\Cache\Adapter\AdapterInterface as Cache;
    use myJSON\Errors\ResponseError;
    use myJSON\Resolvers\ResolverInterface;
    use myJSON\Builders\QueryBuilder;
    use myJSON\Builders\FilterBuilder;
    use myJSON\Collections\FilterCollection;
    use myJSON\Entities\PersistedEntity;

    class PDOAdapter extends \myJSON\Library\Base implements AdapterInterface {
        private $dsn, $username, $password;
        public function __construct(String $url, Cache $cache) {
            $this->setType("adapter");
            $this->setCache($cache);
            $proto = explode("://", $url);
            $userhost = explode("@", $proto[1] ?? "");
            $userpass = explode(":", $userhost[0] ?? "");
            $hostdb = explode("/", $userhost[1] ?? "");
            $user = $userpass[0] ?? "";
            $pass = $userpass[1] ?? "";
            $host = $hostdb[0] ?? "";
            $db = $hostdb[1] ?? "";
            $type = $proto[0] ?? "";
            $dsn = $type . ":host=" . $host . ";dbname=" . $db;
            $this->dsn = $dsn;
            $this->username = $user;
            $this->password = $pass;
            $this->setDriver($this->initDriver($dsn, $user, $pass));
            $this->getDriver()->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        public function initDriver($dsn, $username, $password, $options = null) {
            return new \PDO($dsn, $username, $password, $options);
        }
        public function setDriver($driver) {
            $this->driver = $driver;
            return $this;
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
        public function getDriver($new = false) {
            return ($new ? $this->initDriver($this->dsn, $this->username, $this->password) : $this->driver);
        }
        public function getOptions() {
            return $this->options;
        }
        public function quote($value) {
            return $this->getDriver()->quote($value);
        }
        public function SelectCommand($columns, $table, $suffix) {
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
		public function UpdateCommand($table, $columnskeyvalue, $suffix) {
			$ret = "UPDATE `" . $table . "` SET ";
			$keys = array_keys($columnskeyvalue);
			for($i = 0; $i < count($keys); $i++) $ret .= "`" . $keys[$i] . "`" . "=" . (!is_null($columnskeyvalue[$keys[$i]]) ? self::getDriver()->quote(($columnskeyvalue[$keys[$i]] === FALSE ? 0 : $columnskeyvalue[$keys[$i]])) : "NULL") . ($i+1 < count($keys) ? ", " : " ");
			$ret .= $suffix;
			return $ret;
		}
		public function InsertCommand($table, $columnskeyvalue) {
			$ret = "INSERT INTO `" . $table . "`(";
			$keys = array_keys($columnskeyvalue);
			$vals = "";
			for($i = 0; $i < count($keys); $i++) {
				$ret .= $keys[$i] . ($i+1 < count($keys) ? ", " : " ");
				$vals .= (!is_null($columnskeyvalue[$keys[$i]]) ? "'" . self::getDriver()->real_escape_string($columnskeyvalue[$keys[$i]]) . "'" : "NULL") . ($i+1 < count($keys) ? ", " : "");
			}
			$ret .= ") VALUES (" . $vals . ");";
			return $ret;
		}
		public function DeleteCommand($table, $suffix) {
			$ret = "DELETE FROM `" . $table . "` " . $suffix . ";";
			return $ret;
		}
		public function CallCommand($procfunc, &...$parameters) {
            $ret = array();
			$keys = [];
            $index = 1;
			foreach($parameters as $parameter) {
                if(!is_array($parameter)) {
                    $ret["SET"][] = "SET @param" . $index . "=" . (!is_null($parameter) ? self::getDriver()->quote(($parameter === FALSE ? 0 : $parameter)) : 'NULL');
                    $ret["PARAMS"][] = "@param" . $index++;
                }
            }
            $parameters = (isset($ret["PARAMS"]) ? $ret["PARAMS"] : array());
			$ret["CALL"] = "CALL {$procfunc}(" . (count($parameters) ? join(", ", $parameters) : "") . ");";
			return $ret;
		}
        public function getColumns($table) {
            $columns = $this->query("SHOW COLUMNS FROM `{$table}`;", \PDO::FETCH_OBJ);
            if(!is_array($columns) || !count($columns)) {
                throw new \Exception("Failed to load columns for {$table}. The table was not identified.");
            }
            return $columns;
        }
        public function query($command, $type = \PDO::FETCH_ASSOC) {
            $ret = [];
            if($this->getDriver() != null) {
    			$result = $this->getDriver()->query($command);
    			if($result != false) {
    				if($result->rowCount() > 0) {
    					for($i = 0; $i < $result->rowCount(); $i++) {
    						$ret[] = $result->fetch($type);
    					}
    				}
    			}
    		}
    		return $ret;
        }
        public function staticQuery($command, $type = \PDO::FETCH_ASSOC) {
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
        public function call($procfunc, &...$parameters) {
			$ret = false;
			$data = self::CallCommand($procfunc, ...$parameters);
            $set = (isset($data["SET"]) ? $data["SET"] : array());
            $call = (isset($data["CALL"]) ? $data["CALL"] : "");
            $query = join("; ", $set) . "; " . $call;
			if(($statement  = self::getDriver()->query($query)) !== FALSE) {
				$ret = true;
				$statement->closeCursor();
                if(count($parameters)) {
                    $query = "SELECT" . join(", ", $data["PARAMS"]) . ";";
                    if(($statement = self::getDriver()->query($query)) !== FALSE) {
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
                        throw new \Exception("Database Error", "No data in select query.");
    				}
    			}
    			else {
                    throw new \Exception("Database Error", "Failed binding to data source due to query error.");
    			}
    		}
    		return $ret;
    	}
    	public function update($columnskeyvalue, $table, $suffix) {
    		$ret = false;
    		$result = $this->getDriver()->query($this->UpdateCommand(($table != null ? $table : $this->Table), $columnskeyvalue, ($suffix != null ? $suffix : "")));
    		if($result !== FALSE) {
    			$ret = true;
    		}
    		else {
                throw new \Exception("Failed updating data.");
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
                throw new \Exception("Failed inserting data.");
    		}
    		return $ret;
    	}

        //working from here down
        public function findRecord($table, $keysvalues) {
            $columns = array_keys($keysvalues);
            $query = new QueryBuilder();
            $query->append("SELECT * FROM `" . $table . "` WHERE ");
            $values = [];
            for($i = 0; $i < count($columns); $i++) {
                if($i > 0) $query->append(" AND ");
                $query->append($columns[$i] . " = :" . $columns[$i]);
                $values[":" . $columns[$i]] = $keysvalues[$columns[$i]];
            }
            $result = $this->getDriver()->prepare($query);
            try {
                $result->execute($values);
                $data = $result->fetchAll(\PDO::FETCH_ASSOC);
                if(is_array($data)) {
                    if(count($data) > 1) {
                        throw new \Exception(get_class($this) . "::findRecord was called and expected one record. {$table} with keys: [{join(',', $keys)}] returned many.");
                    }
                }
            }
            catch(\Exception $ex) {
                throw new \Exception("Failed finding record for {$table} due to error. " . $ex->getMessage());
            }
            return (isset($data[0]) ? $data[0] : []);
        }
        public function getAttribute($attribute) {
            $value = null;
            $query = (new QueryBuilder())->append("SELECT " . $attribute . " FROM dual;");
            $result = $this->query($query);
            if(is_array($result)) {
                if(count($result)) {
                    if(isset($result[0][$attribute])) {
                        $value = $result[0][$attribute];
                    }
                    else {
                        throw new \Exception("Attribute result returned without a value for " . $attribute);
                    }
                }
                else {
                    throw new \Exception("Attribute result returned with no values for " . $attribute);
                }
            }
            else {
                throw new \Exception("Attribute request failed");
            }
            return $value;
        }
        public function saveRecord(string $entity, $data) : bool {
            $query = new QueryBuilder();
            $query->append("INSERT INTO `{$entity}`(");
            $values = [];
            $columns = array_keys($data);
            $parameters = "";
            $variables = "";
            for($i = 0; $i < count($columns); $i++) {
                if($i > 0) {
                    $parameters .= (", ");
                    $variables .= (", ");
                }
                $parameters .= $columns[$i];
                $variables .= ":{$columns[$i]}";
                $values[":{$columns[$i]}"] = $data[$columns[$i]];
            }
            $query->append($parameters)->append(") VALUES(")->append($variables)->append(");");
            $result = $this->getDriver()->prepare($query);
            try {
                if(!$result->execute($values)) {
                    throw new \Exception("Adapter Error");
                }
            }
            catch(\Exception $ex) {
                throw new \Exception("Failed saving {$entity} record into {$entity} due to query error. QUERY: {$query} ERROR: {$ex->getMessage()}");
            }
            return true;
        }
        public function updateRecord(string $entity, $id, $data) {
            $query = new QueryBuilder();
            $query->append("UPDATE `{$entity}` SET ");
            $columns = array_keys($data);
            $values = [];
            for($i = 0; $i < count($columns); $i++) {
                if($i > 0) $query->append(", ");
                $query->append($columns[$i])
                    ->append(" = ")
                    ->append(":" . $columns[$i]);
                $values[":{$columns[$i]}"] = $data[$columns[$i]];
            }
            $query->append(" WHERE ");
            $columns = array_keys($id);
            for($i = 0; $i < count($columns); $i++) {
                if($i > 0) $query->append(" AND ");
                $query->append($columns[$i])
                ->append(" = ")
                ->append(":{$columns[$i]}");
                $values[":$columns[$i]"] = $id[$columns[$i]];
            }
            $query->append(";");
            $updateStatement = $this->getDriver()->prepare($query);
            try {
                if(!$result = $updateStatement->execute($values)) {
                    throw new \Exception("Adapter Error");
                }
            }
            catch(\Exception $ex) {
                throw new \Exception("Failed updating {$entity} record due to query error: " . $ex->getMessage());
            }
            return $result;
        }
        public function destroyRecord($table, $keyvalue) {
            $result = false;
            $query = new QueryBuilder();
            if(count($keyvalue ?? [])) {
                $columns = array_keys($keyvalue);
                $parameters = [];
                $query->append("DELETE FROM `{$table}` WHERE ");
                for($i = 0; $i < count($columns); $i++) {
                    if($i > 0) $query->append(" AND ");
                    $query->append("{$columns[$i]}=:{$columns[$i]}");
                    $parameters[":{$columns[$i]}"] = $keyvalue[$columns[$i]];
                }
                $query->append(";");
                $deleteStatement = $this->getDriver()->prepare($query);
                try {
                    $result = $deleteStatement->execute($parameters);
                }
                catch(\Exception $ex) {
                    throw new \Exception("Failed to delete record due to driver error. " . $ex->getMessage());
                }
            }
            return $result;
        }
        public function findAll($table, FilterCollection $filter) {
            $query = new QueryBuilder();
            $query->append("SELECT * FROM `" . $table . "` WHERE ");
            $query->match($filter, $table)->append(";");
            $result = $this->getDriver()->prepare($query);
            $data = null;
            try {
                $parameters = $filter->getParameters();
                $result->execute($parameters);
                $data = $result->fetchAll(\PDO::FETCH_ASSOC);
            }
            catch(\Exception $ex) {
                throw new \Exception("Failed finding records for {$table} due to adapter error. " . $ex->getMessage() . " Query: " . (string) $query);
            }
            return $data;
        }
        public function saveRelationship($link, $keys, $data) {
            $result = false;
            if(count($keys ?? [])) {
                $insertQuery = new QueryBuilder();
                $deleteQuery = new QueryBuilder();
                $primaryColumns = array_keys($keys);
                $deleteParameters = [];
                $deleteQuery->append("DELETE FROM `{$link}` WHERE ");
                for($i = 0; $i < count($primaryColumns); $i++) {
                    if($i > 0) {
                        $deleteQuery->append(" AND ");
                    }
                    $deleteQuery->append($primaryColumns[$i] . "=:" . $primaryColumns[$i]);
                    $deleteParameters[":" . $primaryColumns[$i]] = $keys[$primaryColumns[$i]];
                }
                $deleteQuery->append(";");
                if($this->getDriver()->beginTransaction()) {
                    $deleteStatement = $this->getDriver()->prepare($deleteQuery);
                    if($deleteStatement->execute($deleteParameters)) {
                        if(count($data ?? [])) {
                            $success = true;
                            foreach($data as $item) {
                                $secondaryColumns = array_keys($item);
                                $dataColumns = [];
                                $insertParameters = [];
                                for($i = 0; $i < count($primaryColumns); $i++) {
                                    if(isset($keys[$primaryColumns[$i]])) {
                                        $dataColumns[$primaryColumns[$i]] = $keys[$primaryColumns[$i]];
                                        $insertParameters[":" . $primaryColumns[$i]] = $keys[$primaryColumns[$i]];
                                    }
                                }
                                for($i = 0; $i < count($secondaryColumns); $i++) {
                                    if(!isset($dataColumns[$secondaryColumns[$i]])) {
                                        $dataColumns[$secondaryColumns[$i]] = $item[$secondaryColumns[$i]];
                                        $insertParameters[":" . $secondaryColumns[$i]] = $item[$secondaryColumns[$i]];
                                    }
                                }
                                $insertQuery->append("INSERT INTO {$link}(" . \implode(", ", array_keys($dataColumns)) . ") VALUES(" . \implode(", ", array_keys($insertParameters)) . ");");
                                $statement = $this->getDriver()->prepare($insertQuery);
                                $success = $success && $statement->execute($insertParameters);
                            }
                            if($success) {
                                if(!($result = $this->getDriver()->commit())) {
                                    $this->getDriver()->rollBack();
                                }
                            }
                            else {
                                $this->getDriver()->rollBack();
                            }
                        }
                        else {
                            if(!($result = $this->getDriver()->commit())) {
                                $this->getDriver()->rollBack();
                            }
                        }
                    }
                    else {
                        $this->getDriver()->rollBack();
                    }
                }
            }
            return $result;
        }
    }
 ?>
