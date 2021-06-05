<?php
    namespace myJSON\Relationships;
    class Relationship extends \Tobscure\JsonApi\Relationship {
        private $errors = array();
        public function addError($error) {
			$this->errors[] = $error;
			return $this;
		}
		public function mergeErrors($errors) {
			foreach($errors as $error) {
				$this->addError($error);
			}
			return $this;
		}
        public function getErrors() {
			return $this->errors;
		}
    }
 ?>
