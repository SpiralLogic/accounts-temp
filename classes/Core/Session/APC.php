<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      10/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\Session;

  /**
   * @property mixed lifetime
   */
  class APC implements \SessionHandlerInterface
  {
    protected $key;
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
      apc_delete($this->key . $session_id);
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
     * @param $save_path
     * @param $session_id
     *
     * @return mixed
     */
    public function open($save_path, $session_id) {
      $this->key      = 'sess.key.' . $_SERVER['SERVER_NAME'];
      $this->lifetime = min(ini_get('session.gc_maxlifetime'), 60 * 60 * 24 * 30);
      return true;
    }
    /**
     * @param $session_id
     *
     * @return mixed
     */
    public function read($session_id) {
      $result = apc_fetch($this->key . $session_id, $success);
      if ($success === true) {
        return (string)$result;
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
      return apc_store($this->key . $session_id, $session_data, $this->lifetime);
    }
  }
