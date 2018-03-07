<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      28/08/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\Cache;

  /** **/
  class APC implements Cachable
  {
    public $defineFunction;
    public $loadFunction;
    /**
     *
     */
    public function __construct() {
      if (!extension_loaded('apc')) {
        throw new \RuntimeException('APC is not installed!');
      }
    }
    public function init() {
      $this->loadFunction   = (function_exists('apc_load_constants')) ? 'apc_load_constants' : false;
      $this->defineFunction = (function_exists('apc_define_constants')) ? 'apc_define_constants' : false;
    }
    /**
     * @static
     *
     * @param     $key
     * @param     $value
     * @param int $expires
     *
     * @return mixed
     */
    public function set($key, $value, $expires = 86400) {
      if (!function_exists('igbinary_serialize')) {
        $serialized_value = igbinary_serialize($value);
      } else {
        $serialized_value = $value;
      }
      apc_store($_SERVER["SERVER_NAME"] . '.' . $key, $serialized_value, $expires);
      return $value;
    }
    /**
     * @static
     *
     * @param $key
     *
     * @return mixed|void
     */
    public function delete($key) {
      apc_delete($_SERVER["SERVER_NAME"] . '.' . $key);
    }
    /**
     * @static
     *
     * @param       $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = false) {
      $result = apc_fetch($_SERVER["SERVER_NAME"] . '.' . $key, $success);
      if (!function_exists('igbinary_unserialize')) {
        $result = ($success === true) ? igbinary_unserialize($result) : $default;
      } else {
        $result = ($success === true) ? $result : $default;
      }
      return $result;
    }
    /**
     * @static
     *
     * @param int $time
     */
    public function flush($time = 0) {
      apc_clear_cache('user');
      apc_clear_cache();
    }
    /**
     * @param                $name
     * @param \Closure|Array $constants
     *
     * @return bool
     * @return \ADV\Core\Cache|bool
     */
    public function defineConstants($name, $constants) {
      $loader = $this->loadFunction;
      if (is_callable($loader)) {
        $loader = $loader($name);
      }
      if ($loader === true) {
        return true;
      }
      if (is_callable($constants)) {
        $constants = (array) call_user_func($constants);
      }
      $definer = $this->defineFunction;
      if (is_callable($definer)) {
        $definer = $definer($name, $constants);
      }
      if ($definer === true) {
        return true;
      }
      foreach ($constants as $constant => $value) {
        define($constant, $value);
      }
      return false;
    }
  }
