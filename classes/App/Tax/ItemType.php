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
  namespace ADV\App\Tax {
    use ADV\Core\DB\DB;

    /**

     */
    class ItemType extends \ADV\App\DB\Base implements \ADV\App\Pager\Pageable
    {
      protected $_table = 'item_tax_types';
      protected $_classname = 'Item Tax Type';
      protected $_id_column = 'id';
      public $id = 0;
      public $name;
      public $exempt = 0;
      public $exemptions = [];
      public $inactive = 0;
      /**
       * @param int   $id
       * @param array $extra
       *
       * @return bool|void
       */
      protected function read($id, $extra = []) {
        parent::read($id, $extra);
        $exemptions = static::$DB->select('tax_type_id')->from('item_tax_type_exemptions')->where('item_tax_type_id=', $this->id)->fetch();
        foreach ($exemptions as $exemption) {
          $this->exemptions[$exemption['tax_type_id']] = 1;
        }
      }
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      public function delete() {
        $count = static::$DB->select('count(*) as count')->from('stock_master')->where('tax_type_id=', $this->id)->fetch()->one('count');
        if ($count) {
          return $this->status(false, 'Cannot delete this item tax type because items have been created referring to it.');
        }
        return parent::delete();
      }
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      protected function canProcess() {
        if (strlen($this->name) > 60) {
          return $this->status(false, 'Name must be not be longer than 60 characters!', 'name');
        }
        return true;
      }
      /**
       * @param bool $inactive
       *
       * @return array
       */
      public static function getAll($inactive = false) {
        $q = DB::_select()->from('item_tax_types');
        if (!$inactive) {
          $q->andWhere('inactive=', 0);
        }
        return $q->fetch()->all();
      }
      /**
       * @return array
       */
      public function getPagerColumns() {
        $cols = [
          ['type' => 'skip'],
          'Item Tax Type',
          'Fully Exempt' => ['type' => 'bool'],
          'Inactive'     => ['type' => 'inactive'],
        ];
        return $cols;
      }
    }
  }
  namespace {
    /**
     *
     */
    class Tax_ItemType
    {
      /**
       * @static
       *
       * @param $name
       * @param $exempt
       * @param $exempt_from
       */
      public static function add($name, $exempt, $exempt_from) {
        DB::_begin();
        $sql
          = "INSERT INTO item_tax_types (name, exempt)
        VALUES (" . DB::_escape($name) . "," . DB::_escape($exempt) . ")";
        DB::_query($sql, "could not add item tax type");
        $id = DB::_insertId();
        // add the exemptions
        static::add_exemptions($id, $exempt_from);
        DB::_commit();
      }
      /**
       * @static
       *
       * @param $id
       * @param $name
       * @param $exempt
       * @param $exempt_from
       */
      public static function update($id, $name, $exempt, $exempt_from) {
        DB::_begin();
        $sql = "UPDATE item_tax_types SET name=" . DB::_escape($name) . ",	exempt=" . DB::_escape($exempt) . " WHERE id=" . DB::_escape($id);
        DB::_query($sql, "could not update item tax type");
        // readd the exemptions
        static::delete_exemptions($id);
        static::add_exemptions($id, $exempt_from);
        DB::_commit();
      }
      /**
       * @static
       * @return null|PDOStatement
       */
      public static function getAll() {
        $sql = "SELECT * FROM item_tax_types";
        return DB::_query($sql, "could not get all item tax type");
      }
      /**
       * @static
       *
       * @param $id
       *
       * @return \ADV\Core\DB\Query\Result|Array
       */
      public static function get($id) {
        $sql    = "SELECT * FROM item_tax_types WHERE id=" . DB::_escape($id);
        $result = DB::_query($sql, "could not get item tax type");
        return DB::_fetch($result);
      }
      /**
       * @static
       *
       * @param $stock_id
       *
       * @return \ADV\Core\DB\Query\Result|Array
       */
      public static function get_for_item($stock_id) {
        $sql
                = "SELECT item_tax_types.* FROM item_tax_types,stock_master WHERE
        stock_master.stock_id=" . DB::_escape($stock_id) . "
        AND item_tax_types.id=stock_master.tax_type_id";
        $result = DB::_query($sql, "could not get item tax type");
        return DB::_fetch($result);
      }
      /**
       * @static
       *
       * @param $id
       *
       * @return bool
       */
      public static function delete($id) {
        if (!static::can_delete($id)) {
          return false;
        }
        DB::_begin();
        $sql = "DELETE FROM item_tax_types WHERE id=" . DB::_escape($id);
        DB::_query($sql, "could not delete item tax type");
        // also delete all exemptions
        static::delete_exemptions($id);
        DB::_commit();
        Event::notice(_('Selected item tax type has been deleted'));
      }
      /**
       * @static
       *
       * @param $id
       * @param $exemptions
       */
      public static function add_exemptions($id, $exemptions) {
        for ($i = 0; $i < count($exemptions); $i++) {
          $sql
            = "INSERT INTO item_tax_type_exemptions (item_tax_type_id, tax_type_id)
            VALUES (" . DB::_escape($id) . ", " . DB::_escape($exemptions[$i]) . ")";
          DB::_query($sql, "could not add item tax type exemptions");
        }
      }
      /**
       * @static
       *
       * @param $id
       */
      public static function delete_exemptions($id) {
        $sql = "DELETE FROM item_tax_type_exemptions WHERE item_tax_type_id=" . DB::_escape($id);
        DB::_query($sql, "could not delete item tax type exemptions");
      }
      /**
       * @static
       *
       * @param $id
       *
       * @return null|PDOStatement
       */
      public static function get_exemptions($id) {
        $sql = "SELECT * FROM item_tax_type_exemptions WHERE item_tax_type_id=" . DB::_escape($id);
        return DB::_query($sql, "could not get item tax type exemptions");
      }
      /**
       * @static
       *
       * @param      $name
       * @param null $selected_id
       *
       * @return string
       */
      public static function select($name, $selected_id = null) {
        $sql = "SELECT id, name FROM item_tax_types";
        return Forms::selectBox($name, $selected_id, $sql, 'id', 'name', array('order' => 'id'));
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       */
      public static function cells($label, $name, $selected_id = null) {
        if ($label != null) {
          echo "<td>$label</td>\n";
        }
        echo "<td>";
        echo Tax_ItemType::select($name, $selected_id);
        echo "</td>\n";
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       */
      public static function row($label, $name, $selected_id = null) {
        echo "<tr><td class='label'>$label</td>";
        Tax_ItemType::cells(null, $name, $selected_id);
        echo "</tr>\n";
      }
      /**
       * @static
       *
       * @param $selected_id
       *
       * @return bool
       */
      public static function can_delete($selected_id) {
        $sql    = "SELECT COUNT(*) FROM stock_master WHERE tax_type_id=" . DB::_escape($selected_id);
        $result = DB::_query($sql, "could not query stock master");
        $myrow  = DB::_fetchRow($result);
        if ($myrow[0] > 0) {
          Event::error(_("Cannot delete this item tax type because items have been created referring to it."));
          return false;
        }
        return true;
      }
    }
  }
