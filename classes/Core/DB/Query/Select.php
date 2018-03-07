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

  use ADV\Core\DB\DB;

  /** **/
  class Select extends Query
  {
    /** @var array * */
    protected $select = [];
    /** @var array * */
    protected $from = [];
    /** @var array * */
    protected $limit = [];
    /** @var array * */
    protected $orderby = [];
    /** @var array * */
    protected $groupby = [];
    /** @var array * */
    protected $union = [];
    /** @var array * */
    protected $union_or = [];
    /***
     * @param string $columns,... Database columns to select
     * @param        DB_C
     *
     * @return Select
     */
    public function __construct($columns, $db) {
      parent::__construct($db);
      $this->type = DB::SELECT;
      call_user_func_array(array($this, 'select'), $columns);
    }
    /***
     * @param mixed ... Database columns to select
     *
     * @return Select
     */
    public function select() {
      $columns      = func_get_args();
      $this->select = array_merge($this->select, $columns);
      return $this;
    }
    /***
     * @param null $tables
     *
     * @return Select
     */
    public function from($tables = null) {
      if (is_null($tables)) {
        return $this;
      }
      $tables     = func_get_args();
      $this->from = array_merge($this->from, $tables);
      return $this;
    }
    /**
     * @param null $by
     *
     * @return Select
     */
    public function orderby($by = null) {
      if (is_null($by)) {
        return $this;
      }
      $by            = func_get_args();
      $this->orderby = array_merge($this->orderby, $by);
      return $this;
    }
    /**
     * @param null $by
     *
     * @return Select
     */
    public function groupby($by = null) {
      if (is_null($by)) {
        return $this;
      }
      $by            = func_get_args();
      $this->groupby = array_merge($this->groupby, $by);
      return $this;
    }
    /**
     * @param      $start
     * @param null $quantity
     *
     * @return Select
     */
    public function limit($start = 0, $quantity = null) {
      $this->limit = ($quantity == null) ? $start : "$start, $quantity";
      return $this;
    }
    /**
     * @return Select
     */
    public function union() {
      $this->union[] = '(' . $this->buildQuery() . ')';
      $this->select  = $this->from = $this->orderby = $this->groupby = [];
      $this->limit   = '';
      $this->resetWhere();
      return $this;
    }
    /**
     * @param $condition
     * @param $var
     *
     * @return void
     */
    public function union_or($condition, $var) {
      $this->union_or[$condition] = $var;
    }
    /**
     * @return string
     */
    protected function execute() {
      if ($this->union) {
        return implode(' UNION ', $this->union);
      }
      return $this->buildQuery();
    }
    /**
     * @return string
     */
    protected function buildQuery() {
      $sql = "SELECT ";
      $sql .= (empty($this->select)) ? '*' : implode(', ', $this->select);
      $sql .= " FROM " . implode(', ', $this->from);
      $sql .= parent::buildWhere();
      if (!empty($this->union_or)) {
        //$data = $this->data;
        $finalsql = [];
        foreach ($this->union_or as $k => $v) {
          $finalsql[] = $sql . ' AND ' . $k . ' ' . $v;
        }
      }
      if (!empty($this->groupby)) {
        $sql .= ' GROUP BY ' . implode(', ', $this->groupby);
      }
      if (!empty($this->orderby)) {
        $sql .= ' ORDER BY ' . implode(', ', $this->orderby);
      }
      if (!empty($this->limit)) {
        $sql .= ' LIMIT ' . $this->limit;
      }
      return $sql;
    }
  }
