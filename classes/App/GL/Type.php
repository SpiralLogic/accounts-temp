<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\GL {
    use ADV\Core\DB\DB;
    use ADV\App\Validation;

    /**

     */
    class Type extends \ADV\App\DB\Base implements \ADV\App\Pager\Pageable
    {
      protected $_table = 'chart_types';
      protected $_classname = 'Chart Type';
      protected $_id_column = 'id';
      public $id = 0;
      public $name;
      public $class_id;
      public $parent = -1;
      public $inactive = 0;
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      public function delete() {
        $result = $this->DB->select("COUNT(*) as count")->from('chart_master')->where('account_type=', $this->id)->fetch()->one('count');
        if ($result > 0) {
          return $this->status(false, "Cannot delete this account group because GL accounts have been created referring to it.");
        }
        $result = $this->DB->select("COUNT(*) as count")->from('chart_types')->where('parent=', $this->id)->fetch()->one('count');
        if ($result > 0) {
          return $this->status(false, "Cannot delete this account group because GL account groups have been created referring to it.");
        }
        return parent::delete();
      }
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      protected function canProcess() {
        if (strlen($this->name) < 1 || strlen($this->name) > 60) {
          return $this->status(false, 'Name must be not be longer than 60 characters!', 'name');
        }
        if (!Validation::is_num($this->class_id, 0)) {
          return $this->status(false, 'Class Id must be a number', 'class_id');
        }
        if (!Validation::is_num($this->parent, null)) {
          return $this->status(false, 'Parent must be a number', 'parent');
        }
        if ($this->parent == $this->id) {
          return $this->status(false, 'You cannot set an account group to be a subgroup of itself.', 'parent');
        }
        return true;
      }
      /**
       * @param bool $inactive
       *
       * @return array
       */
      public static function getAll($inactive = false) {
        $q = DB::_select()->from('chart_types');
        if (!$inactive) {
          $q->andWhere('inactive=', 0);
        }
        return $q->fetch()->all();
      }
      /**
       * @return array
       */
      public function getPagerColumns() {
        return [['type' => 'skip'], 'Name', 'Class', 'Parent', 'Inactive' => ['type' => 'inactive']];
      }
      /**
       * @return array
       */
      public static function selectBoxItems() {
        $self  = new static();
        $q     = DB::_select($self->_id_column, 'name')->from($self->_table)->andWhere('inactive=', 0)->fetch();
        $items = [];
        foreach ($q as $row) {
          $items[$row[$self->_id_column]] = $row['name'];
        }
        return $items;
      }
    }
  }
  namespace {
    /**
     *
     */
    class GL_Type
    {
      /**
       * @static
       *
       * @param $id
       * @param $name
       * @param $class_id
       * @param $parent
       *
       * @return null|PDOStatement
       */
      public static function add($id, $name, $class_id, $parent) {
        $sql
          = "INSERT INTO chart_types (id, name, class_id, parent)
        VALUES ($id, " . DB::_escape($name) . ", " . DB::_escape($class_id) . ", " . DB::_escape($parent) . ")";
        return DB::_query($sql);
      }
      /**
       * @static
       *
       * @param $id
       * @param $name
       * @param $class_id
       * @param $parent
       *
       * @return null|PDOStatement
       */
      public static function update($id, $name, $class_id, $parent) {
        $sql = "UPDATE chart_types SET name=" . DB::_escape($name) . ",
        class_id=" . DB::_escape($class_id) . ", parent=" . DB::_escape($parent) . " WHERE id = " . DB::_escape($id);
        return DB::_query($sql, "could not update account type");
      }
      /**
       * @static
       *
       * @param bool $all
       * @param bool $class_id
       * @param bool $parent
       *
       * @return null|PDOStatement
       */
      public static function getAll($all = false, $class_id = false, $parent = false) {
        $sql = "SELECT * FROM chart_types";
        if (!$all) {
          $sql .= " WHERE !inactive";
        }
        if ($class_id != false) {
          $sql .= " AND class_id=" . DB::_escape($class_id);
        }
        if ($parent == -1) {
          $sql .= " AND parent <= 0";
        } elseif ($parent != false) {
          $sql .= " AND parent=" . DB::_escape($parent);
        }
        $sql .= " ORDER BY class_id, id";
        return DB::_query($sql, "could not get account types");
      }
      /**
       * @static
       *
       * @param $id
       *
       * @return \ADV\Core\DB\Query\Result|Array
       */
      public static function get($id) {
        $sql    = "SELECT * FROM chart_types WHERE id = " . DB::_escape($id);
        $result = DB::_query($sql, "could not get account type");
        return DB::_fetch($result);
      }
      /**
       * @static
       *
       * @param $id
       *
       * @return mixed
       */
      public static function get_name($id) {
        $sql    = "SELECT name FROM chart_types WHERE id = " . DB::_escape($id);
        $result = DB::_query($sql, "could not get account type");
        $row    = DB::_fetchRow($result);
        return $row[0];
      }
      /**
       * @static
       *
       * @param $id
       */
      public static function delete($id) {
        $sql = "DELETE FROM chart_types WHERE id = " . DB::_escape($id);
        DB::_query($sql, "could not delete account type");
      }
      /**
       * @static
       *
       * @param      $name
       * @param null $selected_id
       * @param bool $all_option
       * @param bool $all_option_numeric
       *
       * @return string
       */
      public static function  select($name, $selected_id = null, $all_option = false, $all_option_numeric = true) {
        $sql = "SELECT id, name FROM chart_types";
        return Forms::selectBox(
          $name,
          $selected_id,
          $sql,
          'id',
          'name',
          array(
               'order'       => 'id',
               'spec_option' => $all_option,
               'spec_id'     => $all_option_numeric ? 0 : ALL_TEXT
          )
        );
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       * @param bool $all_option
       * @param bool $all_option_numeric
       */
      public static function  cells($label, $name, $selected_id = null, $all_option = false, $all_option_numeric = false) {
        if ($label != null) {
          echo "<td>$label</td>\n";
        }
        echo "<td>";
        echo GL_Type::select($name, $selected_id, $all_option, $all_option_numeric);
        echo "</td>\n";
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       * @param bool $all_option
       * @param bool $all_option_numeric
       */
      public static function  row($label, $name, $selected_id = null, $all_option = false, $all_option_numeric = false) {
        echo "<tr><td class='label'>$label</td>";
        GL_Type::cells(null, $name, $selected_id, $all_option, $all_option_numeric);
        echo "</tr>\n";
      }
    }
  }
