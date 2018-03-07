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

  /**
   *
   */
  class Session implements Cachable
  {
    public $defineFunction;
    public $loadFunction;
    public function init() {
      $this->loadFunction   = false;
      $this->defineFunction = false;
      if (!isset($_SESSION['_cache'])) {
        $_SESSION['_cache'] = [];
      }
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
      $_SESSION['_cache'][$key] = $value;
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
      unset($_SESSION['_cache'][$key]);
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
      $result = $_SESSION['_cache'][$key];
      return $result;
    }
    /**
     * @static
     *
     * @param int $time
     */
    public function flush($time = 0) {
      $_SESSION['_cache'] = [];
    }
    /**
     * @param $name
     * @param $constants
     *
     * @return mixed
     */
    public function defineConstants($name, $constants) {
      return false;
    }
  }
