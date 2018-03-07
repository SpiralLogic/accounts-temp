<?php
  namespace ADV\Core;

  use InvalidArgumentException;

  /** **/
  class DIC implements \ArrayAccess
  {
    protected $_objects = [];
    protected $_callbacks = [];
    /** @var DIC */
    protected static $i;
    protected $_last;
    /**
     * @return DIC
     */
    public static function i() {
      if (!static::$i) {
        static::$i = new static();
      }
      return static::$i;
    }
    /**
     * @param          $name
     * @param \Closure $callable
     *
     * @return \ADV\Core\DIC
     */
    public static function set($name, \Closure $callable) {
      static::$i->offsetSet($name, $callable);
    }
    /**
     * Sets a parameter or an object.
     * Objects must be defined as Closures.
     * Allowing any PHP callable leads to difficult to debug problems
     * as function names (strings) are callable (creating a function with
     * the same a name as an existing parameter would break your container).
     *
     * @param string $name  The unique identifier for the parameter or object
     * @param mixed  $callable
     *
     * @throws \InvalidArgumentException
     * @internal param mixed $value The value of the parameter or a closure to defined an object
     * @return \ADV\Core\DIC|void
     */
    public function offsetSet($name, $callable) {
      $name = strtolower($name);
      if (!$callable instanceof \Closure) {
        throw new InvalidArgumentException('Must be closure!');
      }
      $this->_last             = $name;
      $this->_callbacks[$name] = $callable;
      return $this;
    }
    /**
     * @param $name
     * @param $param
     */
    public function setParam($name, $param) {
      $name = strtolower($name);
      $this->set(
        $name, function () use ($param) {
          return $param;
        }
      );
    }
    /**
     * @param $name
     *
     * @return bool
     */
    public static function has($name) {
      $name = strtolower($name);
      return static::$i->offsetExists($name);
    }
    /**
     * Checks if a parameter or an object is set.
     *
     * @param string $name The unique identifier for the parameter or object
     *
     * @return Boolean
     */
    public function offsetExists($name) {
      $name = strtolower($name);
      return isset($this->_callbacks[$name]);
    }
    /**
     * @param $name
     *
     * @return mixed
     */
    public static function get($name = null) {
      return call_user_func_array([static::$i, 'offsetGet'], func_get_args());
    }
    /**
     * Gets a parameter or an object.
     *
     * @param string $name The unique identifier for the parameter or object
     *
     * @return mixed                     The value of the parameter or an object
     * @throws \InvalidArgumentException if the identifier is not defined
     */
    public function offsetGet($name) {
      $name = strtolower($name);
      $name = $name ? : $this->_last;
      if (isset($this->_objects[$name])) {
        $args = func_get_args();
        array_shift($args);
        if (0 == count($args)) {
          $key = '_no_arguments';
        } else {
          $key = $this->keyForArguments($args);
        }
        if ('_no_arguments' == $key && !isset($this->_objects[$name][$key]) && !empty($this->_objects[$name])) {
          $key = key($this->_objects[$name]);
        }
        if (isset($this->_objects[$name][$key])) {
          return $this->_objects[$name][$key];
        }
      }
      // Otherwise create a new one
      return $this->fresh($name, func_get_args());
    }
    /**
     * @param      $name
     * @param null $args
     *
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public function fresh($name, $args = null) {
      if (!isset($this->_callbacks[$name])) {
        throw new \InvalidArgumentException(sprintf('Callback for "%s" does not exist.', $name));
      }
      $arguments                   = is_array($args) && func_num_args() == 2 ? $args : func_get_args();
      $arguments[0]                = $this;
      $key                         = $this->keyForArguments($arguments);
      $this->_objects[$name][$key] = call_user_func_array($this->_callbacks[$name], $arguments);
      return $this->_objects[$name][$key];
    }
    /**
     * @param $name
     *
     * @return bool
     */
    public static function delete($name) {
      $name = strtolower($name);
      return static::$i->offsetUnset($name);
    }
    /**
     * Unsets a parameter or an object.
     *
     * @param string $name The unique identifier for the parameter or object
     *
     * @return bool|void
     */
    public function offsetUnset($name) {
      // TODO: Should this also delete the callback?
      $name = strtolower($name);
      if (isset($this->_objects[$name])) {
        unset($this->_objects[$name]);
        return true;
      }
      return false;
    }
    /**
     * @param array $arguments
     *
     * @return string
     */
    protected function keyForArguments(Array $arguments) {
      if (count($arguments) && $this === $arguments[0]) {
        array_shift($arguments);
      }
      array_walk_recursive(
        $arguments, function (&$element) {
          # do some special stuff (serialize closure) ...
          if (is_object($element)) {
            $element = spl_object_hash($element);
          }
        }
      );
      return md5(serialize($arguments));
    }
  }
