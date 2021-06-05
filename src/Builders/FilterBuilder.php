<?php
    namespace myJSON\Builders;
    use myJSON\Library\Base;
    class FilterBuilder extends Base {
        public static $EqualOperator = "equal";
        public static $NotEqualOperator = "notequal";
        public static $NullOperator = "isnull";
        public static $NotNullOperator = "isnotnull";
        public static $ContainOperator = "contain";
        public static $TimeframeOperator = "timeframe";
        public static $AfterOperator = "after";
        public static $BeforeOperator = "before";
        private $key = null;
        private $value = null;
        private $operator = null;
        public function attribute($key, $value, $operator) {
            $operator = $operator ?? (isset($value) ? self::$EqualOperator : self::$NullOperator);
            $this->key = $key;
            $this->value = $value;
            $this->operator = $operator;
            return $this;
        }
        public function getIdentity() {
            return $this->key . ".operators." . $this->operator . ".values." . $this->value;
        }
        public function toString() {
            $str = "`{$this->key}`";
            switch($this->operator) {
                case self::$EqualOperator:
                    $str .= "=:{$this->key}";
                break;
                case self::$NotEqualOperator:
                    $str .= "!=:{$this->key}";
                break;
                case self::$ContainOperator:
                    $str .= " LIKE CONCAT('%', :{$this->key}, '%')";
                break;
                case self::$NullOperator:
                    $str .= " IS NULL";
                break;
                case self::$NotNullOperator:
                    $str .= " IS NOT NULL";
                break;
                case self::$TimeframeOperator:
                    $from = self::fromUTCDateTime($this->value[0]);
                    $to = self::fromUTCDateTime($this->value[1]);
                    $str .= " BETWEEN :{$this->key}_from AND :{$this->key}_to";
                break;
                case self::$AfterOperator:
                    $str .= " > :{$this->key}";
                break;
                case self::$BeforeOperator:
                    $str .= " < :{$this->key}";
                break;
                default:
                    $str .= "1=1";
                break;
            }
            return $str;
        }
        public function getParameters() {
            $parameters = [];
            switch($this->operator) {
                case self::$EqualOperator:
                    $parameters[":" . $this->key] = $this->value;
                break;
                case self::$NotEqualOperator:
                    $parameters[":" . $this->key] = $this->value;
                break;
                case self::$ContainOperator:
                    $parameters[":" . $this->key] = $this->value;
                break;
                case self::$TimeframeOperator:
                    $parameters[":" . $this->key . "_from"] = self::fromUTCDateTime($this->value[0]);
                    $parameters[":" . $this->key . "_to"] = self::fromUTCDateTime($this->value[1]);
                break;
                case self::$AfterOperator:
                    $parameters[":" . $this->key] = self::fromUTCDateTime($this->value);
                break;
                case self::$BeforeOperator:
                    $parameters[":" . $this->key] = self::fromUTCDateTime($this->value);
                break;
            }
            return $parameters;
        }
        public function __toString() {
            return $this->toString();
        }
        public function isEqual($key, $value) {
            return $this->attribute($key, $value, self::$EqualOperator);
        }
        public function notEqual($key, $value) {
            return $this->attribute($key, $value, self::$NotEqualOperator);
        }
        public function isNull($key) {
            return $this->attribute($key, null, self::$NullOperator);
        }
        public function notNull($key) {
            return $this->attribute($key, null, self::$NotNullOperator);;
        }
        public function isLike($key, $value) {
            return $this->attribute($key, $value, self::$ContainOperator);
        }
        public function isWithin($key, $value) {
            return $this->attribute($key, $value, self::$TimeframeOperator);
        }
        public function isBefore($key, $value) {
            return $this->attribute($key, $value, self::$BeforeOperator);
        }
        public function isAfter($key, $value) {
            return $this->attribute($key, $value, self::$AfterOperator);
        }
    }
?>
