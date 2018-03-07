<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      10/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\Session;

  use ADV\Core\SessionException;

  /**
   * @property mixed lifetime
   */
  class Memcached implements \SessionHandlerInterface
  {
    public $connected;
    /** @var \Memcached */
    public $connection;
    public $lockid;
    public function __construct() {
      if (!extension_loaded('memcached')) {
        throw new \RuntimeException('MemCached is not installed!');
      }
    }
    /**
     * @return bool
     */
    public function close() {
      return true;
    }
    /**
     * @param $session_id
     *
     * @return mixed
     */
    public function destroy($session_id) {
      $this->connection->delete($session_id);
      return true;
    }
    /**
     * @param $maxlifetime
     *
     * @return mixed
     */
    public function gc($maxlifetime) {
      return true;
    }
    /**
     * @param string $save_path
     * @param string $session_id
     *
     * @throws \ADV\Core\SessionException
     * @return mixed
     */
    public function open($save_path, $session_id) {
      if (class_exists('\\Memcached', false)) {
        $i = new \Memcached($_SERVER["SERVER_NAME"] . 'sessions');
        if (!count($i->getServerList())) {
          $i->setOption(\Memcached::OPT_RECV_TIMEOUT, 1000);
          $i->setOption(\Memcached::OPT_SEND_TIMEOUT, 3000);
          $i->setOption(\Memcached::OPT_TCP_NODELAY, true);
          $i->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
          $i->setOption(\Memcached::OPT_PREFIX_KEY, 'memc.sess.key.' . $_SERVER['SERVER_NAME']);
          $i->addServer('127.0.0.1', 11211);
        }
        $this->connected  = ($i->getVersion() !== false);
        $this->connection = $i;
        $this->lifetime   = min(ini_get('session.gc_maxlifetime'), 60 * 60 * 24 * 30);
      } else {
        throw new SessionException('Memcached extension does not exist!');
      }
      return $this->connected;
    }
    /**
     * @param $session_id
     *
     * @return mixed
     */
    public function read($session_id) {
      $result = $this->connection->get($session_id);
      if ($this->connection->getResultCode() === \Memcached::RES_SUCCESS) {
        return (string) $result;
      }
      return '';
    }
    /**
     * @param $session_id
     * @param $session_data
     *
     * @return mixed
     */
    public function write($session_id, $session_data) {
      $this->connection->set($session_id, $session_data, $this->lifetime);
      return ($this->connection->getResultCode() !== \Memcached::RES_NOTSTORED);
    }
  }
