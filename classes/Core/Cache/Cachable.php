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
  interface Cachable {
    /**
     * @abstract
     *
     * @param $key
     * @param $value
     * @param $expires
     *
     * @return mixed
     */
    public function set($key, $value, $expires = 86400);
    /**
     * @abstract
     *
     * @param $key
     *
     * @return mixed
     */
    public function delete($key);
    /**
     * @abstract
     *
     * @param $key
     * @param $default
     *
     * @return mixed
     */
    public function get($key, $default = false);
    public function init();
    /**
     * @param int $time
     */
    public function flush($time = 0);
    /**
     * @param $name
     * @param $constants
     *
     * @return mixed
     */
    public function defineConstants($name, $constants);
  }
