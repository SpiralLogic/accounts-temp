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

  /** **/
  abstract class Where
  {
    /** @var array * */
    public $data = [];
    /** @var array * */
    protected $where = [];
    /** @var array * */
    private $wheredata = [];
    /** @var int * */
    protected $count = 0;
    protected function resetWhere() {
      $this->wheredata = $this->where = [];
      $this->count     = 0;
    }
    /***
     * @param array  $conditions
     * @param string $type
     * @param null   $uservar
     *
     * @return Select
     */
    protected function _where($conditions, $type = 'AND', $uservar = null) {
      if (is_array($conditions)) {
        foreach ($conditions as $condition) {
          if (is_array($condition)) {
            $this->_where($condition[0], $type, $condition[1]);
          } else {
            $this->_where($condition);
          }
        }
        return $this;
      }
      if ($uservar !== null) {
        $name = ':dbcondition' . $this->count;
        $this->count++;
        $this->wheredata[$name] = $uservar;
        $conditions             = $conditions . ' ' . $name;
      }
      $this->where[] = (empty($this->where)) ? $conditions : $type . ' ' . $conditions;
      return $this;
    }
    /**
     * @param      $condition
     * @param null $uservar
     *
     * @return Query|Select
     */
    public function where($condition, $uservar = null) {
      return $this->_where($condition, 'AND', $uservar);
    }
    /**
     * @param      $condition
     * @param null $uservar
     *
     * @return Select
     */
    public function orWhere($condition, $uservar = null) {
      return $this->_where($condition, 'OR', $uservar);
    }
    /**
     * @param      $condition
     * @param null $uservar
     *
     * @return Select
     */
    public function andWhere($condition, $uservar = null) {
      return $this->_where($condition, 'AND', $uservar);
    }
    /**
     * @param      $condition
     * @param null $uservar
     *
     * @return Select
     */
    public function orOpen($condition, $uservar = null) {
      return $this->_where($condition, 'OR (', $uservar);
    }
    /**
     * @param      $condition
     * @param null $uservar
     *
     * @return Select
     */
    public function andOpen($condition, $uservar = null) {
      return $this->_where($condition, 'AND (', $uservar);
    }
    /**
     * @param      $condition
     * @param null $uservar
     *
     * @return Select
     */
    public function closeAnd($condition, $uservar = null) {
      return $this->_where($condition, ') AND', $uservar);
    }
    /**
     * @param      $condition
     * @param null $uservar
     *
     * @return Select
     */
    public function closeOr($condition, $uservar = null) {
      return $this->_where($condition, ') OR', $uservar);
    }
    /**
     * @param      $condition
     * @param null $uservar
     *
     * @return Select
     */
    public function open($condition, $uservar = null) {
      if (empty($this->where)) {
        $condition = '(' . $condition;
      }
      return $this->_where($condition, ' AND ', $uservar);
    }
    /**
     * @return Where
     */
    public function close() {
      array_push($this->where, array_pop($this->where) . ') ');
      return $this;
    }
    /**
     * @return string
     */
    protected function buildWhere() {
      $sql = '';
      if (!empty($this->where)) {
        $sql .= ' WHERE ' . implode(' ', $this->where);
      }
      $this->data = $this->data + $this->wheredata;
      return $sql;
    }
  }
