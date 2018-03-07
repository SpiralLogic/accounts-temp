<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core.db
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\DB\Query;

  use Serializable;

  /** **/
  abstract class Query extends Where implements Serializable
  {
    /** @var \ADV\Core\DB\Query\Query * */
    protected static $query = null;
    /** @var bool * */
    protected $compiled_query = false;
    /** @var */
    protected $type;
    /** @var \ADV\Core\DB\DB * */
    protected $conn;
    /**
     * @abstract
     * @return
     */
    abstract protected function execute();
    /**
     * @param $conn
     */
    public function __construct($conn) {
      $this->conn    = $conn;
      static::$query = $this;
    }
    /**
     * @param $data
     *
     * @return bool
     */
    protected function compileQuery($data) {
      if (!$this->compiled_query) {
        $this->compiled_query = $this->execute($data);
      }
      return $this->compiled_query;
    }
    /**
     * @param null $data
     *
     * @return bool
     */
    public function getQuery($data = null) {
      return $this->compileQuery($data);
    }
    /***
     * @param null $data
     *
     * @return \ADV\Core\DB\Query\Result|int|bool
     */
    public function exec($data = null) {
      $result = $this->conn->exec($this->compileQuery($data), $this->type, $this->data);
      return $result;
    }
    /***
     * @return \ADV\Core\DB\Query\Result
     */
    public function fetch() {
      return $this->exec(null);
    }
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     */
    public function serialize() {
      $this->conn    = null;
      static::$query = null;
      return serialize($this);
    }
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     *
     * @param string $serialized <p>
     *                           The string representation of the object.
     * </p>
     *
     * @throws \ErrorException
     * @return mixed the original value unserialized.
     */
    public function unserialize($serialized) {
    }
  }
