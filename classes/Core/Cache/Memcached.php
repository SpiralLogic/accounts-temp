<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      28/08/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\Cache;

  /** **/
  class Memcached implements Cachable
  {
    /** @var bool * */
    protected $connected = false;
    protected $connection = false;
    public function init() {
      if (class_exists('\\Memcached', false)) {
        $i = new \Memcached($_SERVER["SERVER_NAME"] . '.');
        if (!count($i->getServerList())) {
          $i->setOption(\Memcached::OPT_RECV_TIMEOUT, 1000);
          $i->setOption(\Memcached::OPT_SEND_TIMEOUT, 3000);
          $i->setOption(\Memcached::OPT_TCP_NODELAY, true);
          $i->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
          $i->setOption(\Memcached::OPT_PREFIX_KEY, $_SERVER["SERVER_NAME"] . '.');
          (\Memcached::HAVE_IGBINARY) and $i->setOption(\Memcached::SERIALIZER_IGBINARY, true);
          $i->addServer('127.0.0.1', 11211);
        }
        $this->connected  = ($i->getVersion() !== false);
        $this->connection = $i;
      }
    }
    /**
     * @param     $key
     * @param     $value
     * @param int $expires
     *
     * @return mixed
     */
    public function set($key, $value, $expires = 86400) {
      if ($this->connection !== false) {
        $this->connection->set($key, $value, time() + $expires);
      }
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
      if ($this->connection !== false) {
        $this->connection->delete($key);
      }
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
      $success = $result = false;
      if ($this->connection !== false) {
        $result  = $this->connection->get($key);
        $success = ($this->connection->getResultCode() === \Memcached::RES_NOTFOUND);
      }
      $result = ($success === false) ? $default : $result;
      return $result;
    }
    /**
     * @static
     * @return mixed
     */
    public function getStats() {
      return ($this->connected) ? $this->connection->getStats() : false;
    }
    /**
     * @static
     * @return mixed
     */
    public function getVersion() {
      return ($this->connected) ? $this->connection->getVersion() : false;
    }
    /**
     * @static
     * @return mixed
     */
    public function getServerList() {
      return ($this->connected) ? $this->connection->getServerList() : false;
    }
    /**
     */
    public function flush($time = 0) {
      if ($this->connection) {
        $this->connection->flush($time);
      }
    }
    /**
     * @param                $name
     * @param \Closure|Array $constants
     *
     * @return bool
     * @return \ADV\Core\Cache|bool
     */
    public function defineConstants($name, $constants) {
      if (is_callable($constants)) {
        $constants = (array)call_user_func($constants);
      }
      foreach ($constants as $constant => $value) {
        define($constant, $value);
      }
      return true;
    }
  }
