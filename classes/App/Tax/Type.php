<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Tax {
    use ADV\Core\DB\DB;
    use Tax_Type;
    use ADV\App\Validation;

    /**

     */
    class Type extends \ADV\App\DB\Base  implements \ADV\App\Pager\Pageable {
      protected $_table = 'tax_types';
      protected $_classname = 'Tax Type';
      protected $_id_column = 'id';
      public $id = 0;
      public $rate = 0;
      public $sales_gl_code;
      public $purchasing_gl_code;
      public $name;
      public $inactive = 0;
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      protected function canProcess() {
        if (strlen($this->name) == 0) {
          return $this->status(
            false,
            'The tax type name cannot be empty.'
          );
        }
        if (!Tax_Type::is_tax_gl_unique($this->sales_gl_code, $this->purchasing_gl_code, $this->id)) {
          return $this->status(false, 'Selected GL Accounts cannot be used by another tax type.');
        }
        if (!Validation::is_num($this->rate, 0)) {
          return $this->status(false, 'The default tax rate must be numeric and not less than zero.', 'rate');
        }
        if (strlen($this->sales_gl_code) > 11) {
          return $this->status(false, 'Sales GL Code must be not be longer than 11 characters!', 'sales_gl_code');
        }
        if (strlen($this->purchasing_gl_code) > 11) {
          return $this->status(false, 'Purchasing GL Code must be not be longer than 11 characters!', 'purchasing_gl_code');
        }
        if (strlen($this->name) > 60) {
          return $this->status(false, 'Name must be not be longer than 60 characters!', 'name');
        }
        return true;
      }
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      public function delete() {
        $count = DB::_select('count(*) as count')->from('tax_group_items')->where('tax_type_id=', $this->id)->fetch()->one('count');
        if ($count) {
          return $this->status(false, "Cannot delete this tax type because tax groups have been created referring to it.");
        }
        if (parent::delete()) {
          static::$DB->delete('item_tax_type_exemptions')->where('tax_type_id=', $this->id)->exec();
          return $this->status(true, 'Selected tax type has been deleted');
        }
        return null;
      }
      /**
       * @param bool $inactive
       *
       * @return array
       */
      public static function getAll($inactive = false) {
        $q = static::$DB->select('id', 'name', 'rate', 'sales_gl_code', 'purchasing_gl_code', 'inactive')->from('tax_types');
        if (!$inactive) {
          $q->andWhere('inactive=', 0);
        }
        return $q->fetch()->all();
      }
      /**
       * @return array
       */
      public function getPagerColumns() {
        return [
          ['type' => "skip"],
          'Name',
          'Rate' => ['type' => "percent"],
          'Sales GL Account',
          'Purchasing GL Account',
          'Inactive' => ['type' => 'inactive'],
        ];
      }
    }
  }
  namespace {
    use ADV\Core\DB\DB;
    use ADV\App\Validation;
    use ADV\Core\Input\Input;
    use ADV\Core\JS;
    use ADV\App\Forms;
    use ADV\Core\Event;

    /**

     */
    class Tax_Type {
      /**
       * @static
       *
       * @param $name
       * @param $sales_gl_code
       * @param $purchasing_gl_code
       * @param $rate
       */
      public static function add($name, $sales_gl_code, $purchasing_gl_code, $rate) {
        $sql
          = "INSERT INTO tax_types (name, sales_gl_code, purchasing_gl_code, rate)
        VALUES (" . DB::_escape($name) . ", " . DB::_escape($sales_gl_code) . ", " . DB::_escape($purchasing_gl_code) . ", $rate)";
        DB::_query($sql, "could not add tax type");
      }
      /**
       * @static
       *
       * @param $type_id
       * @param $name
       * @param $sales_gl_code
       * @param $purchasing_gl_code
       * @param $rate
       */
      public static function update($type_id, $name, $sales_gl_code, $purchasing_gl_code, $rate) {
        $sql = "UPDATE tax_types SET name=" . DB::_escape($name) . ",
        sales_gl_code=" . DB::_escape($sales_gl_code) . ",
        purchasing_gl_code=" . DB::_escape($purchasing_gl_code) . ",
        rate=$rate
        WHERE id=" . DB::_escape($type_id);
        DB::_query($sql, "could not update tax type");
      }
      /**
       * @static
       *
       * @param bool $all
       *
       * @return null|PDOStatement
       */
      public static function getAll($all = false) {
        $sql
          = "SELECT tax_types.*,
        Chart1.account_name AS SalesAccountName,
        Chart2.account_name AS PurchasingAccountName
        FROM tax_types, chart_master AS Chart1,
        chart_master AS Chart2
        WHERE tax_types.sales_gl_code = Chart1.account_code
        AND tax_types.purchasing_gl_code = Chart2.account_code";
        if (!$all) {
          $sql .= " AND !tax_types.inactive";
        }
        return DB::_query($sql, "could not get all tax types");
      }
      /**
       * @static
       * @return null|PDOStatement
       */
      public static function get_all_simple() {
        $sql = "SELECT * FROM tax_types";
        return DB::_query($sql, "could not get all tax types");
      }
      /**
       * @static
       *
       * @param $type_id
       *
       * @return \ADV\Core\DB\Query\Result|Array
       */
      public static function get($type_id) {
        $sql
                = "SELECT tax_types.*,
        Chart1.account_name AS SalesAccountName,
        Chart2.account_name AS PurchasingAccountName
        FROM tax_types, chart_master AS Chart1,
        chart_master AS Chart2
        WHERE tax_types.sales_gl_code = Chart1.account_code
        AND tax_types.purchasing_gl_code = Chart2.account_code AND id=" . DB::_escape($type_id);
        $result = DB::_query($sql, "could not get tax type");
        return DB::_fetch($result);
      }
      /**
       * @static
       *
       * @param $type_id
       *
       * @return mixed
       */
      public static function get_default_rate($type_id) {
        $sql    = "SELECT rate FROM tax_types WHERE id=" . DB::_escape($type_id);
        $result = DB::_query($sql, "could not get tax type rate");
        $row    = DB::_fetchRow($result);
        return $row[0];
      }
      /**
       * @static
       *
       * @param $type_id
       *
       * @return bool
       */
      public static function delete($type_id) {
        if (static::can_delete($type_id)) {
          return false;
        }
        DB::_begin();
        $sql = "DELETE FROM tax_types WHERE id=" . DB::_escape($type_id);
        DB::_query($sql, "could not delete tax type");
        // also delete any item tax exemptions associated with this type
        $sql = "DELETE FROM item_tax_type_exemptions WHERE tax_type_id=$type_id";
        DB::_query($sql, "could not delete item tax type exemptions");
        DB::_commit();

        return Event::notice(_('Selected tax type has been deleted'));
      }
      /**
      Check if gl_code is used by more than 2 tax types,
      or check if the two gl codes are not used by any other
      than selected tax type.
      Necessary for pre-2.2 installations.
       * @param $gl_code
       * @param $gl_code2
       * @param $selected_id
       *
       * @return bool
       */
      public static function is_tax_gl_unique($gl_code, $gl_code2 = null, $selected_id = null) {
        $purch_code = $gl_code2 ? : $gl_code;
        $q          = DB::_select('count(*) as count')->from('tax_types')->open('sales_gl_code=', $gl_code)->orWhere('purchasing_gl_code=', $purch_code)->close();
        if ($selected_id) {
          $q->andWhere("id!=", (int) $selected_id);
        }
        $count = $q->fetch()->one('count');
        return $gl_code2 ? ($count == 0) : ($count <= 1);
      }
      /**
       * @static
       *
       * @param      $name
       * @param null $selected_id
       * @param bool $none_option
       * @param bool $submit_on_change
       *
       * @return string
       */
      public static function select($name, $selected_id = null, $none_option = false, $submit_on_change = false) {
        $sql = "SELECT id, CONCAT(name, ' (',rate,'%)') as name FROM tax_types";
        return Forms::selectBox(
          $name,
          $selected_id,
          $sql,
          'id',
          'name',
          array(
               'spec_option'   => $none_option,
               'spec_id'       => ALL_NUMERIC,
               'select_submit' => $submit_on_change,
               'async'         => false,
          )
        );
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       * @param bool $none_option
       * @param bool $submit_on_change
       */
      public static function cells($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false) {
        if ($label != null) {
          echo "<td>$label</td>\n";
        }
        echo "<td>";
        echo Tax_Type::select($name, $selected_id, $none_option, $submit_on_change);
        echo "</td>\n";
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       * @param bool $none_option
       * @param bool $submit_on_change
       */
      public static function row($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false) {
        echo "<tr><td class='label'>$label</td>";
        Tax_Type::cells(null, $name, $selected_id, $none_option, $submit_on_change);
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
        $sql    = "SELECT COUNT(*) FROM tax_group_items	WHERE tax_type_id=" . DB::_escape($selected_id);
        $result = DB::_query($sql, "could not query tax groups");
        $myrow  = DB::_fetchRow($result);
        if ($myrow[0] > 0) {
          Event::error(_("Cannot delete this tax type because tax groups been created referring to it."));
          return false;
        }
        return true;
      }
      /**
       * @static
       *
       * @param $selected_id
       *
       * @return bool
       */
      public static function can_process($selected_id) {
        if (strlen($_POST['name']) == 0) {
          Event::error(_("The tax type name cannot be empty."));
          JS::_setFocus('name');
          return false;
        } elseif (!Validation::post_num('rate', 0)) {
          Event::error(_("The default tax rate must be numeric and not less than zero."));
          JS::_setFocus('rate');
          return false;
        }
        if (!Tax_Type::is_tax_gl_unique(Input::_post('sales_gl_code'), Input::_post('purchasing_gl_code'), $selected_id)) {
          Event::error(_("Selected GL Accounts cannot be used by another tax type."));
          JS::_setFocus('sales_gl_code');
          return false;
        }
        return true;
      }
    }
  }
