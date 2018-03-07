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
  namespace ADV\App\Sales {
    use ADV\Core\DB\DB;
    use ADV\Core\Event;

    /**

     */
    class Point extends \ADV\App\DB\Base implements \ADV\App\Pager\Pageable
    {
      protected $_table = 'sales_pos';
      protected $_classname = 'Sales POS';
      protected $_id_column = 'id';
      public $id = 0;
      public $pos_name;
      public $cash_sale = 0;
      public $credit_sale = 0;
      public $pos_location;
      public $pos_account;
      public $inactive = 0;
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      protected function canProcess() {
        if (strlen($this->pos_name) > 30) {
          return $this->status(false, 'Pos_name must be not be longer than 30 characters!', 'pos_name');
        }
        if (strlen($this->pos_location) > 5) {
          return $this->status(false, 'Pos_location must be not be longer than 5 characters!', 'pos_location');
        }
        return true;
      }
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      public function delete() {
        $sql = "SELECT * FROM users WHERE pos=" . DB::_escape($this->id);
        $res = DB::_query($sql, "canot check pos usage");
        if (DB::_numRows($res)) {
          return Event::error(_("Cannot delete this POS because it is used in users setup."));
        }
        return parent::delete();
      }
      /**
       * @param bool $inactive
       *
       * @return array
       */
      public static function getAll($inactive = false) {
        $q = DB::_select()->from('sales_pos');
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
          'Name',
          'Cash Sale'   => ['type' => 'bool'],
          'Credit Sale' => ['type' => 'bool'],
          'Location',
          'Account',
          'Inactive'    => ['type', 'inactive'],
          ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatEditBtn']],
          ['type' => 'insert', "align" => "center", 'fun' => [$this, 'formatDeleteBtn']],
        ];
        return $cols;
      }
    }
  }

  namespace {
    use ADV\App\Forms;

    /**
     *
     */
    class Sales_Point
    {
      /**
       * @static
       *
       * @param $name
       * @param $location
       * @param $account
       * @param $cash
       * @param $credit
       */
      public static function     add($name, $location, $account, $cash, $credit) {
        $sql = "INSERT INTO sales_pos (pos_name, pos_location, pos_account, cash_sale, credit_sale) VALUES (" . DB::_escape($name) . "," . DB::_escape(
          $location
        ) . "," . DB::_escape($account) . ",$cash,$credit)";
        DB::_query($sql, "could not add point of sale");
      }
      /**
       * @static
       *
       * @param $id
       * @param $name
       * @param $location
       * @param $account
       * @param $cash
       * @param $credit
       */
      public static function update($id, $name, $location, $account, $cash, $credit) {
        $sql = "UPDATE sales_pos SET pos_name=" . DB::_escape($name) . ",pos_location=" . DB::_escape($location) . ",pos_account=" . DB::_escape(
          $account
        ) . ",cash_sale =$cash" . ",credit_sale =$credit" . " WHERE id = " . DB::_escape($id);
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
        $sql = "SELECT pos.*, loc.location_name, acc.bank_account_name FROM " . "sales_pos as pos
        LEFT JOIN locations as loc on pos.pos_location=loc.loc_code
        LEFT JOIN bank_accounts as acc on pos.pos_account=acc.id";
        if (!$all) {
          $sql .= " WHERE !pos.inactive";
        }
        return DB::_query($sql, "could not get all POS definitions");
      }
      /**
       * @static
       *
       * @param $id
       *
       * @return \ADV\Core\DB\Query\Result|Array
       */
      public static function get($id) {
        $sql    = "SELECT pos.*, loc.location_name, acc.bank_account_name FROM " . "sales_pos as pos
        LEFT JOIN locations as loc on pos.pos_location=loc.loc_code
        LEFT JOIN bank_accounts as acc on pos.pos_account=acc.id
        WHERE pos.id=" . DB::_escape($id);
        $result = DB::_query($sql, "could not get POS definition");
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
        $sql    = "SELECT pos_name FROM sales_pos WHERE id=" . DB::_escape($id);
        $result = DB::_query($sql, "could not get POS name");
        $row    = DB::_fetchRow($result);
        return $row[0];
      }
      /**
       * @static
       *
       * @param $id
       */
      public static function delete($id) {
        $sql = "DELETE FROM sales_pos WHERE id=" . DB::_escape($id);
        DB::_query($sql, "The point of sale record could not be deleted");
      }
      /**
       * @static
       *
       * @param      $name
       * @param null $selected_id
       * @param bool $spec_option
       * @param bool $submit_on_change
       *
       * @internal param $label
       * @return string
       */
      public static function select($name, $selected_id = null, $spec_option = false, $submit_on_change = false) {
        $sql = "SELECT id, pos_name, inactive FROM sales_pos";
        return Forms::selectBox(
          $name,
          $selected_id,
          $sql,
          'id',
          'pos_name',
          array(
               'select_submit' => $submit_on_change,
               'async'         => true,
               'spec_option'   => $spec_option,
               'spec_id'       => -1,
               'order'         => array('pos_name')
          )
        );
      }
      /**
       * @static
       *
       * @param      $label
       * @param      $name
       * @param null $selected_id
       * @param bool $spec_option
       * @param bool $submit_on_change
       */
      public static function row($label, $name, $selected_id = null, $spec_option = false, $submit_on_change = false) {
        JS::_defaultFocus($name);
        echo '<tr>';
        if ($label != null) {
          echo "<td class='label'>$label</td>\n";
        }
        echo "<td>";
        echo Sales_Point::select($name, $selected_id, $spec_option, $submit_on_change);
        echo "</td></tr>\n";
      }
      /**
       * @static
       * @return bool
       */
      public static function can_process() {
        if (strlen($_POST['name']) == 0) {
          Event::error(_("The POS name cannot be empty."));
          JS::_setFocus('pos_name');
          return false;
        }
        if (!Input::_hasPost('cash') && !Input::_hasPost('credit')) {
          Event::error(_("You must allow cash or credit sale."));
          JS::_setFocus('credit');
          return false;
        }
        return true;
      }
    }
  }
