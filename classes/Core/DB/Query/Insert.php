<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.core.db
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\DB\Query;

  use PDO;
  use ADV\Core\Cache;
  use ADV\Core\DB\DB;
  use ADV\Core\DB\DBException;

  /** **/
  class Insert extends Query
  {
    /** @var */
    protected $table;
    /** @var array * */
    protected $values = [];
    /** @var array * */
    protected $fields = [];
    /** @var array * */
    protected $hasfields = [];
    /** @var array * */
    public $data = [];
    /**
     * @param bool $table
     * @param      $db
     */
    public function __construct($table = false, $db) {
      parent::__construct($db);
      if ($table) {
        $this->into($table);
      }
      $this->type      = DB::INSERT;
      $this->hasfields = null; //Cache::_get('INFORMATION_SCHEMA.COLUMNS.' . $table);
      if (!$this->hasfields) {
        $query = DB::_query('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = ' . DB::_quote($table), false);
        /** @noinspection PhpAssignmentInConditionInspection */
        while ($row = $query->fetch(\PDO::FETCH_ASSOC)) {
          $this->hasfields[] = $row['COLUMN_NAME'];
        }
        Cache::_set('INFORMATION_SCHEMA.COLUMNS.' . $table, $this->hasfields);
      }
      return $this;
    }
    /**
     * @param $table
     *
     * @return Insert
     */
    public function into($table) {
      $this->table = $table;
      return $this;
    }
    /**
     * @param $values array key pair
     *
     * @return Insert|Update
     */
    public function values($values) {
      $this->data = (array)$values + $this->data;
      return $this;
    }
    /**
     * @param array|string $field
     * @param              $value
     *
     * @throws \ADV\Core\DB\DBException
     * @return \ADV\Core\DB\Query\Insert
     */
    public function value($field, $value) {
      if (is_array($field) && is_array($value)) {
        if (count($field) != count($value)) {
          throw new DBException('Field count and Value count unequal');
        } else {
          $this->values(array_combine($field, $value));
        }
      } elseif (is_array($field) && !is_array($value)) {
        $values = array_fill(0, count($field), $value);
        $this->values(array_combine($field, $values));
      } else {
        $this->values(array($field => $value));
      }
      return $this;
    }
    /**
     * @param null $data
     *
     * @return string
     */
    protected function execute($data = null) {
      if ($data !== null) {
        $this->values((array)$data);
      }
      $this->data   = array_intersect_key($this->data, array_flip($this->hasfields));
      $this->data   = array_filter(
        $this->data,
        function ($value) {
          return !is_object($value);
        }
      );
      $this->fields = array_keys($this->data);
      return $this->buildQuery();
    }
    /**
     * @return string
     */
    protected function buildQuery() {
      $sql = "INSERT INTO " . $this->table . " (";
      $sql .= implode(', ', $this->fields) . ") VALUES (";
      $sql .= ':' . implode(', :', str_replace('-', '_', $this->fields));
      $sql .= ') ';
      return $sql;
    }
  }
