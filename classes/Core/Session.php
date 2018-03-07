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

  use SessionHandlerInterface;

  /**
   * @method  static _getGlobal($var, $default = null)
   * @method static _setGlobal($var, $value = null)
   * @method static _get()
   * @method static _set()
   * @method static _regenerate()
   * @method static _kill()
   * @method static _checkUserAgent()
   * @method static _getFlash()
   * @method static _setFlash()
   * @method static Session i()
   * @property  string                page_title
   */
  class Session implements \ArrayAccess
  {
    const GLOBALS = '_globals';
    const FLASH   = '_flash';
    use \ADV\Core\Traits\StaticAccess;

    /** @var \ADV\Core\Language */
    public $language;
    private $_flash = [];
    /**
     * @throws \ADV\Core\SessionException
     */
    public function __construct(SessionHandlerInterface $handler = null) {
      if (session_status() === PHP_SESSION_DISABLED) {
        throw new SessionException('Sessions are disasbled!');
      }
      ini_set('session.gc_maxlifetime', 60 * 60 * 10); // 10hrs
      if ($handler) {
        /** @noinspection PhpParamsInspection */
        session_set_save_handler($handler, true);
      }
      session_start();
      if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new SessionException('Could not start a Session!');
      }
      header("Cache-control: private");
      if (!isset($_SESSION[self::GLOBALS])) {
        $this[self::GLOBALS] = [];
      }
      if (isset($_SESSION[self::FLASH])) {
        $this->_flash = $_SESSION[self::FLASH];
      }
      $_SESSION[self::FLASH] = [];
    }
    /**
     * @static
     * @return bool
     */
    public function checkUserAgent() {
      $user_agent = $this->get('HTTP_USER_AGENT');
      if ($user_agent != sha1(Arr::get($_SERVER, 'HTTP_USER_AGENT', $_SERVER['REMOTE_ADDR']))) {
        $this->setUserAgent();
        return false;
      }
      return true;
    }
    /**
     * @static
     * @return bool
     */
    protected function setUserAgent() {
      return $this->set('HTTP_USER_AGENT', sha1(Arr::get($_SERVER, 'HTTP_USER_AGENT', $_SERVER['REMOTE_ADDR'])));
    }
    /**
     * @param string $var
     *
     * @throws SessionException
     * @return mixed|null
     */
    public function __get($var) {
      if ($var === self::FLASH || $var === self::GLOBALS) {
        throw new SessionException('You muse use getGlobal and getFlash to retrieve global and flash session variables.');
      }
      return $this->get($var);
    }
    /**
     * @param $var
     * @param $value
     *
     * @return void
     */
    public function __set($var, $value) {
      $this->set($var, $value);
    }
    /**
     * @param $var
     * @param $value
     *
     * @return mixed
     */
    public function set($var, $value) {
      $_SESSION[$var] = $value;
      return $value;
    }
    /**
     * @param $var
     * @param $value
     *
     * @internal param $valie
     * @return float|string
     */
    public function setGlobal($var, $value = null) {
      if ($value === null) {
        if (isset($_SESSION[self::GLOBALS][$var])) {
          unset($_SESSION[self::GLOBALS][$var]);
        }
        return null;
      }
      $_SESSION[self::GLOBALS][$var] = $value;
      $this[$var]                    = $value;
      return $value;
    }
    /**
     * @param $var
     * @param $value
     *
     * @internal param $valie
     * @return float|string
     */
    public function setFlash($var, $value) {
      $_SESSION[self::FLASH][$var] = $value;
      return $value;
    }
    /**
     * @param      $var
     * @param null $default
     *
     * @return null
     */
    public function get($var, $default = null) {
      return Arr::get($_SESSION, $var, $default);
    }
    /**
     * @param $var
     * @param $default
     *
     * @return mixed
     */
    public function getGlobal($var, $default = null) {
      return Arr::get($_SESSION[self::GLOBALS], $var, $default);
    }
    /**
     * @param      $var
     * @param null $default
     *
     * @internal param $value
     * @internal param $valie
     * @return float|string
     */
    public function getFlash($var, $default = null) {
      return Arr::get($this->_flash, $var, $default);
    }
    /**
     * @param $var
     */
    public function keepFlash($var, $default = null) {
      $value = $this->getFlash($var);
      $value = ($value === null) ? $default : $value;
      $this->setFlash($var, $value);
    }
    /**
     * @internal param $globals
     */
    public function removeGlobal() {
      if (func_num_args() === 0) {
        $_SESSION[self::GLOBALS] = [];
      }
      $globals = func_get_args();
      foreach ($globals as $var) {
        if (is_string($var) || is_int($var)) {
          unset ($_SESSION[self::GLOBALS][$var]);
        }
      }
    }
    /**
     * @static
     * @return void
     */
    public function regenerate() {
      session_regenerate_id();
    }
    /**
     * @static
     * @return void
     */
    public static function kill() {
      if (session_status() === PHP_SESSION_NONE) {
        session_start();
      }
      session_destroy();
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     *                      An offset to check for.
     * </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     *       The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset) {
      return array_key_exists($offset, $_SESSION);
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     *                      The offset to retrieve.
     * </p>
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset) {
      if (!$this->offsetExists($offset)) {
        return null;
      }
      return $_SESSION[$offset];
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     *                      The offset to assign the value to.
     * </p>
     * @param mixed $value  <p>
     *                      The value to set.
     * </p>
     *
     * @return void
     */
    public function offsetSet($offset, $value) {
      $_SESSION[$offset] = $value;
    }
    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     *                      The offset to unset.
     * </p>
     *
     * @return void
     */
    public function offsetUnset($offset) {
      unset($_SESSION[$offset]);
    }
  }

  /** **/
  class SessionException extends \Exception
  {
  }
