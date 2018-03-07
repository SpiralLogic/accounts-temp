<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;

  use ADV\Core\Cache\Cachable;
  use RuntimeException;

  /**
   * @method static mixed _get($key, $default = false)
   * @method static _set($key, $value, $expires = 86400)
   * @method static _defineConstants($name, $constants)
   * @method static _delete($key)
   * @method static Cache i()
   */
  class Cache {
    use \ADV\Core\Traits\StaticAccess;

    /** @var Cachable * */
    protected $driver = false;
    /**
     * @param Cachable $driver
     *
     * @internal param $Cachable $
     */
    public function __construct(Cachable $driver) {
      $this->driver = $driver;
      $this->driver->init();
      if (isset($_GET['reload_cache'])) {
        $this->driver->flush();
        header('Location: ' . ROOT_URL . '?cache_reloaded');
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
      return $this->driver->set($key, $value, $expires);
    }
    /**
     * @static
     *
     * @param $key
     *
     * @return void
     */
    public function delete($key) {
      $this->driver->delete($key);
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
      return $this->driver->get($key, $default);
    }
    /**
     * @param     $time
     * @param int $time
     */
    public function flush($time) {
      $this->driver->flush($time);
    }
    /**
     * @param $name
     * @param $constants
     *
     * @return \ADV\Core\Cache
     * @throws \RuntimeException
     */
    public function defineConstants($name, $constants) {
      if (!$this->driver->defineConstants($name, $constants)) {
   if (is_callable($constants)) {
        $constants = (array) call_user_func($constants);
      }
      foreach ($constants as $constant => $value) {
        define($constant, $value);
      }
      }
      return $this;
    }
  }
