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

  /** **/
  class Status implements \ArrayAccess
  {
    const SUCCESS = E_SUCCESS;
    const INFO    = E_USER_NOTICE;
    const WARNING = E_USER_WARNING;
    const ERROR   = E_USER_ERROR;
    /** @var array * */
    protected $status = [];
    /** @var array * */
    protected $errors = [];
    /**
     * @param null   $status
     * @param string $message
     * @param null   $var
     *
     * @internal param null $process
     */
    public function __construct($status = null, $message = '', $var = null) {
      if (func_num_args() > 0 && $message) {
        $this->set($status, $message, $var);
      }
    }
    /**
     * Adds new status entry
     *
     * @param int|null      $status  One of Status::SUCCESS | Status::INFO | Status::WARNING | Status::ERROR
     * @param string        $message Friendly message to display
     * @param mixed         $var     The variable if any invovled in causeing the status
     *
     * @internal param string $process What process caused the change in status
     * @return bool
     */
    public function set($status = self::INFO, $message = '', $var = null) {
      if ($status === true) {
        $status = self::INFO;
      }
      if ($status === false) {
        $status = self::ERROR;
      }
      if ($status === null) {
        $newstatus['status']  = self::ERROR;
        $newstatus['message'] = 'Not enough parameters passed for status update.';
      } else {
        $newstatus['status']  = $status;
        $newstatus['message'] = $message;
        if (!empty($var) && $var != null) {
          $newstatus['var'] = $var;
        }
      }
      $this->status[] = $newstatus;
      if ($status === self::ERROR) {
        $this->errors[] = $newstatus;
      }
      return !($status == self::ERROR);
    }
    /**
     * @param \ADV\Core\Status|array $statuses
     * @param bool                   $error_only
     *
     * @return mixed
     */
    public function append(Status $statuses, $error_only = true) {
      $statuses = $statuses->getAll();
      foreach ($statuses as $status) {
        if ($error_only && $status['status'] !== self::ERROR) {
          continue;
        }
        $this->status[] = $status;
      }
      return false;
    }
    /**
     * @return array
     */
    public function getAll() {
      return $this->status;
    }
    /**
     * @return bool|mixed
     */
    public function hasError() {
      if (!empty($this->errors)) {
        return end($this->errors);
      }
      return false;
    }
    /**
     * @return string
     */
    public function __toString() {
      $last = $this->get();
      $str  = ($last['status'] != self::ERROR) ? 'Success: ' : 'Error: ';
      $str .= $last['message'];
      return $str;
    }
    /**
     * @return array
     */
    public function get() {
      if (!empty($this->errors)) {
        return end($this->errors);
      }
      if (!empty($this->status)) {
        return end($this->status);
      }
      return [];
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
      $status = $this->get();
      return isset($status[$offset]);
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
      $status = $this->get();
      return $status[$offset];
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
      $status          = array_pop($this->status);
      $status[$offset] = $value;
      array_push($this->status, $status);
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
     * @throws \RuntimeException
     * @return void
     */
    public function offsetUnset($offset) {
      throw new \RuntimeException("Cannot unset status offset " . $offset);
    }
  }
