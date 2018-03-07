<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Sales {
    use ADV\Core\DB\DB;
    use ADV\App\Validation;

    /**
     */
    class Type extends \ADV\App\DB\Base implements \ADV\App\Pager\Pageable
    {

      protected $_table = 'sales_types';
      protected $_classname = 'Sales Type';
      protected $_id_column = 'id';
      public $id;
      public $sales_type;
      public $tax_included = 0;
      public $factor = 100;
      public $inactive = 0;
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      protected function canProcess() {
        if (strlen($this->sales_type) > 50) {
          return $this->status(false, 'Sales Type must be not be longer than 50 characters!', 'sales_type');
        }
        if (!Validation::is_num($this->factor, 0)) {
          return $this->status(false, 'Factor must be a number bigger than 0', 'factor');
        }
        $this->factor *= .01;
        return true;
      }
      /**
       * @param bool $inactive
       *
       * @return array
       */
      public static function getAll($inactive = false) {
        $q = DB::_select()->from('sales_types');
        if (!$inactive) {
          $q->andWhere('inactive=', 0);
        }
        return $q->fetch()->all();
      }
      /**
       * @param bool $inactive
       *
       * @return array
       */
      public static function selectBoxItems($inactive = false) {
        $q = DB::_select('id', 'sales_type')->from('sales_types')->orderby('sales_type');
        if (!$inactive) {
          $q->andWhere('inactive=', 0);
        }
        $result = $q->fetch();
        $types  = [];
        foreach ($result as $row) {
          $types[$row['id']] = $row['sales_type'];
        }
        return $types;
      }
      /**
       * @return array
       */
      public function getPagerColumns() {
        return $cols = [
          ['type' => 'skip'],
          'Sales Type',
          'Tax Incl.' => ['type' => 'bool'],
          'Factor'    => ['type' => 'percent'],
          'Inactive'  => ['type' => 'inactive'],
        ];
      }
    }
  }
  namespace {
    use ADV\Core\DB\DB;
    use ADV\App\Validation;
    use ADV\Core\JS;
    use ADV\Core\Event;
    use ADV\App\Forms;

    /**
     *
     */
    class Sales_Type
    {

      /**
       * @static
       *
       * @param $name
       * @param $tax_included
       * @param $factor
       */
      public static function add($name, $tax_included, $factor) {
        $sql = "INSERT INTO sales_types (sales_type,tax_included,factor) VALUES (" . DB::_escape($name) . "," . DB::_escape($tax_included) . "," . DB::_escape($factor) . ")";
        DB::_query($sql, "could not add sales type");
      }
      /**
       * @static
       *
       * @param $id
       * @param $name
       * @param $tax_included
       * @param $factor
       */
      public static function update($id, $name, $tax_included, $factor) {
        $sql = "UPDATE sales_types SET sales_type = " . DB::_escape($name) . ",
    tax_included =" . DB::_escape($tax_included) . ", factor=" . DB::_escape($factor) . " WHERE id = " . DB::_escape($id);
        DB::_query($sql, "could not update sales type");
      }
      /**
       * @static
       *
       * @param bool $all
       *
       * @return null|PDOStatement
       */
      public static function getAll($all = false) {
        $sql = "SELECT * FROM sales_types";
        if (!$all) {
          $sql .= " WHERE !inactive";
        }
        return DB::_query($sql, "could not get all sales types");
      }
      /**
       * @static
       *
       * @param $id
       *
       * @return \ADV\Core\DB\Query\Result|Array
       */
      public static function get($id) {
        $sql    = "SELECT * FROM sales_types WHERE id=" . DB::_escape($id);
        $result = DB::_query($sql, "could not get sales type");
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
        $sql    = "SELECT sales_type FROM sales_types WHERE id=" . DB::_escape($id);
        $result = DB::_query($sql, "could not get sales type");
        $row    = DB::_fetchRow($result);
        return $row[0];
      }
      /**
       * @static
       *
       * @param $id
       */
      public static function delete($id) {
        $sql = "DELETE FROM sales_types WHERE id=" . DB::_escape($id);
        DB::_query($sql, "The Sales type record could not be deleted");
        $sql = "DELETE FROM prices WHERE sales_type_id=" . DB::_escape($id);
        DB::_query($sql, "The Sales type prices could not be deleted");
      }
      /**
       * @static
       *
       * @param      $name
       * @param null $selected_id
       * @param bool $submit_on_change
       * @param bool $special_option
       *
       * @return string
       */
      public static function  select($name, $selected_id = null, $submit_on_change = false, $special_option = false) {
        $sql = "SELECT id, sales_type, inactive FROM sales_types";
        return Forms::selectBox(
          $name,
          $selected_id,
          $sql,
          'id',
          'sales_type',
          array(
            'spec_option'   => $special_option === true ? _("All Sales Types") : $special_option,
            'spec_id'       => 0,
            'select_submit' => $submit_on_change,
            //	 'async' => false,
          )
        );
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       * @param bool $submit_on_change
       * @param bool $special_option
       */
      public static function  cells($label, $name, $selected_id = null, $submit_on_change = false, $special_option = false) {
        if ($label != null) {
          echo "<td>$label</td>\n";
        }
        echo "<td>";
        echo static::select($name, $selected_id, $submit_on_change, $special_option);
        echo "</td>\n";
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       * @param bool $submit_on_change
       * @param bool $special_option
       */
      public static function  row($label, $name, $selected_id = null, $submit_on_change = false, $special_option = false) {
        echo "<tr><td class='label'>$label</td>";
        static::cells(null, $name, $selected_id, $submit_on_change, $special_option);
        echo "</tr>\n";
      }
      /**
       * @static
       * @return bool
       */
      public static function can_process() {
        if (strlen($_POST['sales_type']) == 0) {
          Event::error(_("The sales type description cannot be empty."));
          JS::_setFocus('sales_type');
          return false;
        }
        if (!Validation::post_num('factor', 0)) {
          Event::error(_("Calculation factor must be valid positive number."));
          JS::_setFocus('factor');
          return false;
        }
        return true;
      }
    }
  }
