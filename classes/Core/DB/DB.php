<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core.db
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\DB;

  /**
   * @method static \ADV\Core\DB\DB i()
   * @method static \PDOStatement _query($sql, $err_msg = null)
   * @method static \ADV\Core\DB\Query\Select _select($columns = null)
   * @method static \ADV\Core\DB\Query\Insert _insert($into)
   * @method static \ADV\Core\DB\Query\Update _update($into)
   * @method static _escape($value, $null = false)
   * @method static \ADV\Core\DB\Query\Result|Array _fetch($result = null, $fetch_mode = \PDO::FETCH_BOTH)
   * @method static _fetchRow($result = null)
   * @method static _fetchAll($fetch_type = \PDO::FETCH_ASSOC)
   * @method static _fetchAssoc()
   * @method static _errorMsg()
   * @method static _insertId()
   * @method static _numRows($sql = null)
   * @method static _numFields()
   * @method static DB _begin()
   * @method static DB  _commit()
   * @method static DB  _prepare($sql, $debug = false)
   * @method static DB  _execute($data, $debug = false)
   * @method  static DB _updateRecordStatus($id, $status, $table, $key)
   * @method  static DB _cancel()
   * @method static \ADV\Core\DB\Query\Delete _delete()
   * @method static _errorNo()
   * @method  static _quote($value, $type = null)
   * @method  static _quoteWild($value, $both = true, $type = null)
   */
  class DB extends \PDO implements \Serializable
  {
    use \ADV\Core\Traits\StaticAccess;

    const SELECT = 0;
    const INSERT = 1;
    const UPDATE = 2;
    const DELETE = 4;
    /** @var array */
    protected $data = [];
    /*** @var string */
    public $queryString = [];
    /*** @var \PDOStatement */
    protected $prepared = null;
    /**   @var null */
    protected $debug = false;
    /** @var bool */
    protected $nested = false;
    /** @var Query\Query|Query\Select|Query\Update $query */
    protected $query = false;
    /** @var bool * */
    protected $results = false;
    /** @var bool * */
    protected $errorSql = false;
    /** @var bool * */
    protected $errorInfo = false;
    /** @var bool * */
    protected $intransaction = false;
    /** @var */
    protected $default_connection;
    /** @var \ADV\Core\Cache */
    protected $Cache;
    protected $lastError;
    /**
     * @throws DBException
     */
    public function __construct(Array $config, \ADV\Core\Cache $cache = null) {
      $this->Cache = $cache;
      $this->connect($config);
      $this->default_connection = $config['name'];
    }
    /**
     * @param $config
     *
     * @throws \Exception|\PDOException
     * @return bool
     */
    protected function connect($config) {
      try {
        $options = [];
        if (defined('\\PDO\\MYSQL_ATTR_FOUND_ROWS')) {
          $options[\PDO::MYSQL_ATTR_FOUND_ROWS] = true;
        }
        parent::__construct('mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'], $config['user'], $config['pass'], $options);
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_TO_STRING);
        //       $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
      } catch (\PDOException $e) {
        throw new DBException('Could not connect to database: ' . $config['name'] . '!<br> (' . $e->getMessage() . ')');
      }
      return true;
    }
    /**
     * @param      $sql
     * @param null $err_msg
     *
     * @return null|\PDOStatement
     */
    public function query($sql, $err_msg = null) {
      $this->prepared = null;
      try {
        $this->prepared = $this->prepare($sql);
        try {
          $this->prepared->execute();
        } catch (\PDOException $e) {
          $this->error($e, " (execute) " . $err_msg);
        }
      } catch (\PDOException $e) {
        $this->error($e, " (prepare) " . $err_msg);
      }
      $this->data = [];
      return $this->prepared;
    }
    /**
     * @static
     *
     * @param      $value
     * @param null $type
     *
     * @return mixed
     */
    /**
     * @param          $value
     * @param int|null $type
     *
     * @return string
     */
    public function quote($value, $type = null) {
      $value = trim($value);
      return parent::quote($value, $type);
    }
    /**
     * @param      $value
     * @param bool $both
     * @param null $type
     *
     * @return string
     */
    public function quoteWild($value, $both = true, $type = null) {
      $value = ($both ? '%' . trim($value) : trim($value)) . '%';
      return $this->quote($value, $type);
    }
    /**
     * @static
     *
     * @param      $value
     * @param bool $null
     *
     * @internal param bool $paramaterized
     * @return bool|mixed|string
     */
    public function escape($value, $null = false) {
      $value = trim($value);
      if (!isset($value) || is_null($value) || $value === "") {
        $value = ($null) ? 'null' : '';
        $type  = \PDO::PARAM_NULL;
      } elseif (is_int($value)) {
        $type = \PDO::PARAM_INT;
      } elseif (is_bool($value)) {
        $type = \PDO::PARAM_BOOL;
      } elseif (is_string($value)) {
        $type = \PDO::PARAM_STR;
      } else {
        $type = false;
      }
      $this->data[] = array($value, $type);
      return ' ? ';
    }
    /**
     * @param      $sql
     * @param bool $debug
     *
     * @throws DBException
     * @return bool|\PDOStatement
     */
    public function prepare($sql, $debug = false) {
      $this->debug     = $debug;
      $this->errorInfo = false;
      $this->errorSql  = $sql;
      $data            = $this->data;
      try {
        /** @var \PDOStatement $prepared */
        $prepared = parent::prepare($sql);
        $params   = substr_count($sql, '?');
        if ($data && $params > count($data)) {
          throw new DBException('There are more escaped values than there are placeholders!!');
        }
        $k = 1;
        while (($v = array_shift($data)) && $k <= $params) {
          $prepared->bindValue($k, $v[0], $v[1]);
          $k++;
        }
      } catch (\PDOException $e) {
        $prepared = false;
        $this->error($e);
      }
      if ($debug) {
        $this->queryString = $sql;
      }
      $this->data     = [];
      $this->prepared = $prepared;
      return $prepared;
    }
    /**
     * @param      $data
     * @param bool $debug
     *
     * @return array|bool
     */
    public function execute($data, $debug = false) {
      if (!$this->prepared) {
        return false;
      }
      if ($debug) {
        $this->queryString = $this->placeholderValues($this->queryString, $data);
      }
      $this->data = $data;
      try {
        $this->prepared->execute($data);
        $result = $this->prepared->fetchAll(\PDO::FETCH_ASSOC);
      } catch (\PDOException $e) {
        $result = $this->error($e);
      }
      $this->data = [];
      return $result;
    }
    /**
     * @static
     * @return string
     */
    public function insertId() {
      return parent::lastInsertId();
    }
    /***
     * @param string $columns,... Database columns to select
     *
     * @return Query\Select
     */
    public function select($columns = null) {
      $this->prepared = null;
      $columns        = (is_string($columns)) ? func_get_args() : [];
      $this->query    = new Query\Select($columns, $this);
      return $this->query;
    }
    /**
     * @static
     *
     * @param $into
     *
     * @return \ADV\Core\DB\Query\Update
     */
    public function update($into) {
      $this->prepared = null;
      $this->query    = new Query\Update($into, $this);
      return $this->query;
    }
    /**
     * @param $into
     *
     * @return Query\Insert|bool
     */
    public function insert($into) {
      $this->prepared = null;
      $this->query    = new Query\Insert($into, $this);
      return $this->query;
    }
    /**
     * @param $into
     *
     * @return \ADV\Core\DB\Query\Query|bool
     */
    public function delete($into) {
      $this->prepared = null;
      $this->query    = new Query\Delete($into, $this);
      return $this->query;
    }
    /***
     * @param \PDOStatement $result     The result of the query or whatever cunt
     * @param int           $fetch_mode
     *
     * @return Query\Result|Array This is something
     */
    public function fetch($result = null, $fetch_mode = \PDO::FETCH_BOTH) {
      try {
        if ($result !== null) {
          return $result->fetch($fetch_mode);
        }
        if ($this->prepared === null) {
          return $this->query->fetch($fetch_mode);
        }
        return $this->prepared->fetch($fetch_mode);
      } catch (\Exception $e) {
        $this->error($e);
      }
      return false;
    }
    /**
     * @param null|\PDOStatement $result
     *
     * @return Query\Result|Array
     */
    public function fetchRow($result = null) {
      return $this->fetch($result, \PDO::FETCH_NUM);
    }
    /**
     * @return bool|mixed
     */
    public function fetchAssoc() {
      return is_a($this->prepared, 'PDOStatement') ? $this->prepared->fetch(\PDO::FETCH_ASSOC) : false;
    }
    /**
     * @param int $fetch_type
     *
     * @return array|bool
     */
    public function fetchAll($fetch_type = \PDO::FETCH_ASSOC) {
      $results = $this->results;
      if (!$this->results) {
        $results = $this->prepared->fetchAll($fetch_type);
      }
      $this->results = false;
      return $results;
    }
    public function getLastError() {
      return $this->lastError;
    }
    /**
     * @static
     * @return mixed
     */
    public function errorNo() {
      $info = $this->errorInfo();
      return $info[1];
    }
    /**
     * @static
     * @return mixed
     */
    public function errorInfo() {
      if ($this->errorInfo) {
        return $this->errorInfo;
      }
      if ($this->prepared) {
        return $this->prepared->errorInfo();
      }
      return parent::errorInfo();
    }
    /**
     * @static
     * @return mixed
     */
    public function errorMsg() {
      $info = $this->errorInfo();
      return isset($info[2]) ? $info[2] : false;
    }
    /**
     * @static
     * @return bool
     */
    public function freeResult() {
      $result         = ($this->prepared) ? $this->prepared->closeCursor() : false;
      $this->errorSql = $this->errorInfo = $this->prepared = null;
      $this->data     = [];
      return $result;
    }
    /**
     * @static
     *
     * @param null|\PDOStatement $sql
     *
     * @return int
     */
    public function numRows($sql = null) {
      if ($sql === null) {
        return $this->prepared->rowCount();
      }
      if (is_object($sql)) {
        return $sql->rowCount();
      }
      $rows = ($this->Cache) ? $this->Cache->get('sql.rowcount.' . md5($sql)) : false;
      if ($rows !== false) {
        return (int) $rows;
      }
      $rows = $this->query($sql)->rowCount();
      if ($this->Cache) {
        $this->Cache->set('sql.rowcount.' . md5($sql), $rows);
      }
      return $rows;
    }
    /**
     * @static
     * @return int
     */
    public function numFields() {
      return $this->prepared->columnCount();
    }
    /**
     * @static
     */
    public function begin() {
      /** @noinspection PhpUndefinedMethodInspection */
      if (!parent::inTransaction() && !$this->intransaction) {
        try {
          parent::beginTransaction();
          $this->intransaction = true;
        } catch (\PDOException $e) {
          $this->error($e);
        }
      }
    }
    /**
     * @static
     */
    public function commit() {
      /** @noinspection PhpUndefinedMethodInspection */
      if (parent::inTransaction() || $this->intransaction) {
        $this->intransaction = false;
        try {
          parent::commit();
        } catch (\PDOException $e) {
          $this->error($e);
        }
      }
    }
    /**
     * @static
     */
    public function cancel() {
      /** @noinspection PhpUndefinedMethodInspection */
      if (parent::inTransaction() || $this->intransaction) {
        try {
          $this->intransaction = false;
          parent::rollBack();
        } catch (\PDOException $e) {
          $this->error($e);
        }
      }
      $this->data = [];
    }
    /**
     * @static
     *
     * @param $id
     * @param $status
     * @param $table
     * @param $key
     * Update record activity status.
     *
     * @return Query\Result
     */
    public function updateRecordStatus($id, $status, $table, $key) {
      try {
        $this->update($table)->value('inactive', $status)->where($key . '=', $id)->exec();
      } catch (DBUpdateException $e) {
        static::insertRecordStatus($id, $status, $table, $key);
      }
    }
    /**
     * @static
     *
     * @param $id
     * @param $status
     * @param $table
     * @param $key
     *
     * @throws \ADV\Core\DB\DBUpdateException
     * @return Query\Result
     */
    public function insertRecordStatus($id, $status, $table, $key) {
      try {
        $this->insert($table)->values(array('inactive' => $status, $key => $id))->exec();
      } catch (DBInsertException $e) {
        throw new DBUpdateException('Could not update record inactive status');
      }
    }
    /***
     * @param            $sql
     * @param            $type
     * @param array|null $data
     *
     * @throws \ADV\Core\DB\DBDeleteException
     * @throws \ADV\Core\DB\DBUpdateException
     * @throws \ADV\Core\DB\DBInsertException
     * @throws \ADV\Core\DB\DBSelectException
     * @return Query\Result|int
     */
    public function exec($sql, $type, $data = []) {
      $this->errorInfo = false;
      $this->errorSql  = $sql;
      $this->data      = $data;
      if ($data && is_array(reset($data))) {
        $this->queryString = $this->placeholderValues($this->errorSql, $data);
      } elseif ($data) {
        $this->queryString = $this->namedValues($this->errorSql, $data);
      }
      try {
        $prepared = $this->prepare($sql);
        if (!$prepared) {
          return false;
        }
        switch ($type) {
          case DB::SELECT:
            return new Query\Result($prepared, $data);
          case DB::INSERT:
            $prepared->execute($data);
            return parent::lastInsertId();
          case DB::UPDATE:
          case DB::DELETE:
            $result = $prepared->execute($data);
            if ($result !== false) {
              return $prepared->rowCount();
            }
            return false;
          default:
            return false;
        }
      } catch (\PDOException $e) {
        $error = $this->error($e, false, true);
        switch ($type) {
          case DB::SELECT:
            throw new DBSelectException('Could not select from database: ' . $this->queryString);
            break;
          case DB::INSERT:
            $count = preg_match('/Column \'(.+)\'(.*)/', $error['message'], $matches);
            if ($count) {
              $this->lastError = [E_ERROR, 'message' => str_replace(['cannot be null', '_'], ['cannot be empty', ' '], $matches[1] . $matches[2]), 'var' => $matches[1]];
            }
            throw new DBInsertException('Could not insert into database.');
            break;
          case DB::UPDATE:
            throw new DBUpdateException('Could not update database.');
            break;
          case DB::DELETE:
            throw new DBDeleteException('Could not delete from database.');
            break;
        }
      }
      $this->data = [];
      return false;
    }
    /**
     * @static
     *
     * @param       $sql
     * @param array $data
     *
     * @return mixed
     */
    protected function namedValues($sql, array $data) {
      foreach ($data as $k => $v) {
        $sql = str_replace(":$k", " '$v' ", $sql); // outputs '123def abcdef abcdef' str_replace(,,$sql);
      }
      return $sql;
    }
    /**
     * @static
     *
     * @param       $sql
     * @param array $data
     *
     * @return mixed
     */
    protected function placeholderValues($sql, array $data) {
      foreach ($data as $v) {
        if (is_array($v)) {
          $v = $v[0];
        }
        $sql = preg_replace('/\?/i', "'$v'", $sql, 1); // outputs '123def abcdef abcdef' str_replace(,,$sql);
      }
      return $sql;
    }
    /**
     * @param \Exception|\PDOException  $e
     * @param bool                      $msg
     * @param bool                      $silent
     *
     * @throws DBDuplicateException
     * @throws DBException
     * @internal param bool|string $exit
     * @return bool
     */
    protected function error(\Exception $e, $msg = false, $silent = false) {
      $data       = $this->data;
      $this->data = [];
      if ($data && is_array(reset($data))) {
        $this->errorSql = $this->placeholderValues($this->errorSql, $data);
      } elseif ($data) {
        $this->errorSql = $this->namedValues($this->errorSql, $data);
      }
      $this->queryString = $this->errorSql;
      $this->errorInfo   = $error = $e->errorInfo;
      $error['debug']    = $e->getCode() . (!isset($error[2])) ? $e->getMessage() : $error[2];
      $error['message']  = ($msg != false) ? $msg : $e->getMessage();
      /** @noinspection PhpUndefinedMethodInspection */
      if ((parent::inTransaction() || $this->intransaction)) {
        parent::rollBack();
        $this->intransaction = false;
      }
      if (isset($this->errorInfo[1]) && $this->errorInfo[1] == 1062) {
        throw new DBDuplicateException($this->errorInfo[2]);
      }
      if ($silent) {
        return $error;
      }
      if (class_exists('\\ADV\\Core\\Errors')) {
        \ADV\Core\Errors::databaseError($error, $this->errorSql, $data);
      } else {
        throw new DBException($error);
      }
      return null;
    }
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     */
    public function serialize() {
      $this->prepared           = null;
      $this->default_connection = null;
      $this->query              = null;
      return base64_encode(serialize($this));
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
      return static::i();
    }
  }

  /** **/
  class DBException extends \Exception
  {
  }

  /** **/
  class DBInsertException extends DBException
  {
  }

  /** **/
  class DBDeleteException extends DBException
  {
  }

  /** **/
  class DBSelectException extends DBException
  {
  }

  /** **/
  class DBUpdateException extends DBException
  {
  }

  /** **/
  class DBDuplicateException extends DBException
  {
  }
