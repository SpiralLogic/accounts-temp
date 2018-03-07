<?php

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\DB;

  use ADV\Core\DB\DBDuplicateException;
  use ADV\Core\DIC;
  use ADV\Core\DB\DBException;
  use ADV\Core\DB\DBDeleteException;
  use ADV\Core\DB\DBInsertException;
  use ADV\Core\DB\DBSelectException;
  use ADV\Core\Status;
  use ADV\Core\DB\DBUpdateException;
  use ADV\Core\DB\DB;

  /**
   * @method \Adv\Core\Status getStatus()
   */
  abstract class Base
  {
    use \ADV\Core\Traits\SetFromArray;
    use \ADV\Core\Traits\Status;

    /** @var DB */
    static $DB;
    /** @var int * */
    public $id = 0;
    protected $_table;
    protected $_id_column;
    protected $_id_values = [];
    protected $_classname;
    abstract protected function canProcess();
    /**
     * @param int              $id    Id to read from database, or an array of changes which can include the id to load before applying changes or 0 for a new object
     * @param array            $extra
     * @param \Adv\Core\Status $status
     *
     * @internal param \ADV\Core\DB\DB $db
     */
    public function __construct($id = 0, $extra = [], \Adv\Core\Status $status = null) {
      static::$DB = DIC::get('DB');
      $this->setStatus($status);
      $this->load($id, $extra);
      $this->_classname = $this->_classname ? : end(explode('\\', ltrim(get_called_class(), '\\')));
      if ($this->_id_column != 'id' && !is_array($this->_id_column)) {
        $_id_column = $this->_id_column;
        $this->id   = & $this->$_id_column;
      }
    }
    /**
     * @param int   $id
     * @param array $extra
     *
     * @return \ADV\Core\Traits\Status|bool
     */
    public function load($id = 0, $extra = []) {
      if (is_array($this->_id_column)) {
        return $this->loadComposite($id, $extra);
      }
      $_id_column = $this->_id_column;
      if ((is_numeric($id) && $id > 0) || (is_string($id) && strlen($id) > 0)) {
        $this->$_id_column = $id;
        $this->read($id, $extra);
        return $this->status(Status::INFO, $this->_classname . " details loaded from DB!");
      } elseif (is_array($id)) {
        if (isset($id['id']) && !isset($id[$_id_column])) {
          $id[$_id_column] = $id['id'];
        }
        if (isset($id[$_id_column]) && $id[$_id_column]) {
          $this->read($id[$_id_column], $extra);
        } else {
          $this->init();
        }
        $this->setFromArray($id);
        return $this->status(Status::INFO, $this->_classname . " details constructed!");
      }
      return $this->init();
    }
    /**
     * @param $id
     * @param $extra
     *
     * @throws \ADV\Core\DB\DBException
     * @return \ADV\Core\Traits\Status|bool
     */
    protected function loadComposite($id = [], $extra) {
      if (!$id) {
        return $this->init();
      }
      if (!is_array($id)) {
        throw new DBException('Comosite key tables must be loaded with initial key vales');
      }
      foreach ($this->_id_column as $col) {
        if (isset($id[$col])) {
          $extra[$col] = $id[$col];
        } else {
          throw new DBException('Comosite key tables must be loaded with initial key vales');
        }
      }
      $this->read($id, $extra);
      if (is_array($this->_id_column)) {
        foreach ($this->_id_column as $col) {
          $this->_id_values[$col] = $this->$col;
        }
      }
      $this->setFromArray($id);
      return $this->status(Status::INFO, $this->_classname . " details loaded from DB!");
    }
    /***
     * @param int|array   $id    Id of row to read from database
     * @param array       $extra
     *
     * @throws \ADV\Core\DB\DBException
     * @return bool
     */
    protected function read($id, $extra = []) {
      $this->defaults();
      if (!$this->_table || !$this->_id_column) {
        throw new DBException('No table name or id column for class: ' . get_called_class() . '(' . $this->_classname . ')');
      }
      if (!is_array($this->_id_column)) {
        $extra[$this->_id_column] = $id;
      }
      try {
        $query = static::$DB->select()->from($this->_table);
        $query = $this->getSelectModifiers($query);
        foreach ($extra as $field => $value) {
          $query->andWhere($field . '=', $value);
        }
        static::$DB->fetch()->intoClass($this);
        return $this->status(Status::INFO, 'Successfully read ' . $this->_classname, $id);
      } catch (DBSelectException $e) {
        return $this->status(false, 'Could not read ' . $this->_classname, (string) $id);
      }
    }
    /**
     * @param \ADV\Core\DB\Query\Select $query
     *
     * @return \ADV\Core\DB\Query\Select
     */
    protected function getSelectModifiers(\ADV\Core\DB\Query\Select $query) {
      return $query;
    }
    /**
     * @return bool
     * @return \ADV\Core\Traits\Status|bool
     */
    protected function init() {
      $this->defaults();
      return $this->status(Status::INFO, 'Now working with new ' . $this->_classname);
    }
    /**
     * @param null       $changes
     * @param array|null $changes can take an array of  changes  where key->value pairs match properties->values and applies them before save
     *
     * @return array|bool|int|null
     * @return \ADV\Core\Traits\Status|array|bool|int|string
     */
    public function save($changes = []) {
      $this->setFromArray((array) $changes);
      if (!$this->canProcess()) {
        return false;
      }
      try {
        if (is_array($this->_id_column)) {
          return $this->saveComposite();
        } else {
          return $this->saveUpdate();
        }
      } catch (DBUpdateException $e) {
        return $this->status(Status::ERROR, "Could not update " . $this->_classname);
      } catch (DBInsertException $e) {
        $error = static::$DB->getLastError();
        if ($error) {
          return $this->status(false, $error['message'], $error['var']);
        }
        return $this->status(false, 'Could not add ' . $this->_classname . ' to database');
      } catch (DBDuplicateException $e) {
        return $this->status(false, 'You have tried to enter a duplicate ' . $this->_classname . '. Please modify the existing record or use different values.');
      }
    }
    /**
     * @return \ADV\Core\Traits\Status|bool|int
     */
    protected function saveUpdate() {
      if ($this->id == 0) {
        return $this->saveNew();
      }
      $data = (array) $this;
      static::$DB->update($this->_table)->values($data)->where($this->_id_column . '=', $this->id)->exec();
      if (property_exists($this, 'inactive')) {
        try {
          /** @noinspection PhpUndefinedFieldInspection */
          static::$DB->updateRecordStatus($this->id, $this->inactive, $this->_table, $this->_id_column);
        } catch (DBUpdateException $e) {
          static::$DB->cancel();
          return $this->status(Status::ERROR, "Could not update active status of " . $this->_classname);
        }
      }
      return $this->status(Status::SUCCESS, $this->_classname . ' changes saved to database.');
    }
    /**
     * @return \ADV\Core\Traits\Status|bool
     */
    protected function saveComposite() {
      foreach ($this->_id_column as $col) {
        if ($this->$col != $this->_id_values[$col] && !$this->_id_values[$col]) {
          return $this->saveNew();
        } elseif ($this->$col != $this->_id_values[$col]) {
          return $this->status(Status::ERROR, "Identity columns values changed for a composite key table: " . $this->_classname);
        }
      }
      $data  = (array) $this;
      $query = static::$DB->update($this->_table)->values($data);
      foreach ($this->_id_column as $field) {
        $query->where($field . '=', $this->$field);
      }
      $query->exec();
      return $this->status(Status::SUCCESS, $this->_classname . ' changes saved to database.');
    }
    /**
     * @return int|bool Id assigned to new database row or false if entry failed
     */
    protected function saveNew() {
      $this->id = static::$DB->insert($this->_table)->values((array) $this)->exec();
      return $this->status(Status::SUCCESS, 'Added ' . $this->_classname . ' to database');
    }
    /**
     * @return \ADV\Core\Traits\Status|bool
     */
    public function delete() {
      try {
        $id_column = (array) $this->_id_column;
        $query     = static::$DB->delete($this->_table);
        foreach ($id_column as $field) {
          $query->where($field . '=', $this->$field);
        }
        $query->exec();
      } catch (DBDeleteException $e) {
        return $this->status(false, 'Could not delete' . $this->_classname);
      }
      $this->defaults();
      return $this->status(true, $this->_classname . ' deleted!');
    }
    /**
     * Set class properties to their default values
     */
    protected function defaults() {
      $values = get_class_vars(get_called_class());
      unset($values['DB'], $values['_id_column'], $values['_table'], $values['_classname']);
      $this->setFromArray($values);
    }
    public function getIDColumn() {
      return $this->_id_column;
    }
    /**
     * @return mixed
     */
    public function getClassname() {
      return $this->_classname;
    }
    public function getTable() {
      return $this->_table;
    }
    /**
     * @return array
     */
    public static function getAll() {
      return [];
    }
  }
