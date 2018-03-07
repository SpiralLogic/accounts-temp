<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  use ADV\Core\Traits\StaticAccess;
  use ADV\Core\Config;
  use ADV\App\Dates;
  use ADV\Core\Cache;
  use ADV\App\User;
  use ADV\Core\Input\Input;

  /**
   * @method static DB_Company i()
   * @method static _get_pref($pref_name)
   * @method static _get_prefs()
   * @method static _get_current_fiscalyear()
   * @method static _key_in_foreign_table($id, $tables, $stdkey, $escaped = false)
   * @method static _get_base_sales_type()
   */
  class DB_Company extends \ADV\App\DB\Base
  {
    use StaticAccess;

    /** @var int * */
    protected $_id_column = 'coy_code';
    protected $_table = 'company';
    protected $_classname = 'Company';
    public $id = 0;
    public $coy_code;
    public $coy_name;
    public $gst_no;
    public $coy_no;
    public $tax_last;
    public $postal_address;
    public $phone;
    public $fax;
    public $email;
    public $coy_logo;
    public $suburb;
    public $curr_default;
    public $debtors_act;
    public $pyt_discount_act;
    public $bank_charge_act;
    public $exchange_diff_act;
    public $profit_loss_year_act;
    public $retained_earnings_act;
    public $freight_act;
    public $default_sales_act;
    public $default_sales_discount_act;
    public $default_prompt_pament_act;
    public $default_inventory_act;
    public $default_cogs_act;
    public $default_adj_act;
    public $default_inv_sales_act;
    public $default_assembly_act;
    public $payroll_act;
    public $allow_negative_stock;
    public $po_over_receive;
    public $po_over_charge;
    public $default_credit_limit;
    public $default_workorder_required;
    public $default_dim_required;
    public $past_due_days;
    public $use_dimension;
    public $f_year;
    public $no_item_list;
    public $no_customer_list;
    public $no_supplier_list;
    public $base_sales;
    public $foreign_codes;
    public $accumulate_shipping;
    public $legal_text;
    public $default_delivery_required;
    public $version_id;
    public $time_zone;
    public $custom0_name;
    public $custom0_value;
    public $add_pct;
    public $round_to;
    public $login_tout;
    /**
     * @param int $name
     *
     * @internal param int $id
     */
    public function __construct($company = 0) {
      parent::__construct($company);
      $this->id = & $this->coy_code;
    }
    /**
     * @param array|null $changes
     *
     * @return array|bool|int|null|\ADV\Core\Status
     */
    public function save($changes = null) {
      if (is_array($changes)) {
        $this->setFromArray($changes);
      }
      if (!$this->canProcess()) {
        return false;
      }
      if ($this->id == 0) {
        $this->saveNew();
      }
      DB::_begin();
      DB::_update('company')->values((array) $this)->where('coy_code=', $this->id)->exec();
      DB::_commit();
      $_SESSION['config']['company'] = $this;
      return $this->status(true, "Company has been updated.");
    }
    public function delete() {
      // TODO: Implement delete() method.
    }
    /**
     * @return bool
     */
    protected function canProcess() {
      return true;
      // TODO: Implement canProcess() method.
    }
    protected function defaults() {
      // TODO: Implement defaults() method.
    }
    protected function init() {
      // TODO: Implement init() method.
    }
    /**
     * @param int|null $id
     * @param array    $extra
     *
     * @return bool|void
     */
    protected function read($id = null, $extra = []) {
      $id = $id ? : 0;
      DB::_select()->from('company')->where('coy_code=', $id)->fetch()->intoObject($this);
    }
    /**
     * @return bool|int|void
     */
    protected function saveNew() {
      // TODO: Implement saveNew() method.
    }
    /**
     * @static
     *
     * @param $from_date
     * @param $to_date
     * @param $closed
     *
     * @return void
     */
    public function add_fiscalyear($from_date, $to_date, $closed) {
      $from = Dates::_dateToSql($from_date);
      $to   = Dates::_dateToSql($to_date);
      $sql
            = "INSERT INTO fiscal_year (begin, end, closed)
 VALUES (" . DB::_escape($from) . "," . DB::_escape($to) . ", " . DB::_escape($closed) . ")";
      DB::_query($sql, "could not add fiscal year");
    }
    /**
     * @static
     *
     * @param $daysOrFoll
     * @param $terms
     * @param $dayNumber
     *
     * @return void
     */
    public function add_payment_terms($daysOrFoll, $terms, $dayNumber) {
      if ($daysOrFoll) {
        $sql
          = "INSERT INTO payment_terms (terms,
 days_before_due, day_in_following_month)
 VALUES (" . DB::_escape($terms) . ", " . DB::_escape($dayNumber) . ", 0)";
      } else {
        $sql
          = "INSERT INTO payment_terms (terms,
 days_before_due, day_in_following_month)
 VALUES (" . DB::_escape($terms) . ",
 0, " . DB::_escape($dayNumber) . ")";
      }
      DB::_query($sql, "The payment term could not be added");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return void
     */
    public function delete_fiscalyear($id) {
      DB::_begin();
      $sql = "DELETE FROM fiscal_year WHERE id=" . DB::_escape($id);
      DB::_query($sql, "could not delete fiscal year");
      DB::_commit();
    }
    /**
     * @static
     *
     * @param $selected_id
     *
     * @return void
     */
    public function delete_payment_terms($selected_id) {
      DB::_query("DELETE FROM payment_terms WHERE terms_indicator=" . DB::_escape($selected_id) . " could not delete a payment terms");
    }
    /**
     * @static
     * @return null|PDOStatement
     */
    public function getAll_fiscalyears() {
      $sql = "SELECT * FROM fiscal_year ORDER BY begin";
      return DB::_query($sql, "could not get all fiscal years");
    }
    /**
     * @static
     * @return mixed
     */
    public function get_base_sales_type() {
      $sql    = "SELECT base_sales FROM company WHERE coy_code=1";
      $result = DB::_query($sql, "could not get base sales type");
      $myrow  = DB::_fetch($result);
      return $myrow[0];
    }
    /**
     * @static
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public function get_current_fiscalyear() {
      $year   = $this->_get_pref('f_year');
      $sql    = "SELECT * FROM fiscal_year WHERE id=" . DB::_escape($year);
      $result = DB::_query($sql, "could not get current fiscal year");
      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public function get_fiscalyear($id) {
      $sql    = "SELECT * FROM fiscal_year WHERE id=" . DB::_escape($id);
      $result = DB::_query($sql, "could not get fiscal year");
      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $pref_name
     *
     * @return mixed
     */
    public function get_pref($pref_name) {
      $prefs = (array) $this;
      return $prefs[$pref_name];
    }
    /**
     * @static
     * @return array
     */
    public function get_prefs() {
      return (array) $this;
    }
    /**
     * @static
     *
     * @param $selected_id
     * @param $daysOrFoll
     * @param $terms
     * @param $dayNumber
     *
     * @return void
     */
    public function update_payment_terms($selected_id, $daysOrFoll, $terms, $dayNumber) {
      if ($daysOrFoll) {
        $sql = "UPDATE payment_terms SET terms=" . DB::_escape($terms) . ",
 day_in_following_month=0,
 days_before_due=" . DB::_escape($dayNumber) . "
 WHERE terms_indicator = " . DB::_escape($selected_id);
      } else {
        $sql = "UPDATE payment_terms SET terms=" . DB::_escape($terms) . ",
 day_in_following_month=" . DB::_escape($dayNumber) . ",
 days_before_due=0
 WHERE terms_indicator = " . DB::_escape($selected_id);
      }
      DB::_query($sql, "The payment term could not be updated");
    }
    /**
     * @static
     *
     * @param $selected_id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public function get_payment_terms($selected_id) {
      $sql
              = "SELECT *, (t.days_before_due=0) AND (t.day_in_following_month=0) as cash_sale
 FROM payment_terms t WHERE terms_indicator=" . DB::_escape($selected_id);
      $result = DB::_query($sql, "could not get payment term");
      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $show_inactive
     *
     * @return null|PDOStatement
     */
    public function get_payment_terms_all($show_inactive) {
      $sql = "SELECT * FROM payment_terms";
      if (!$show_inactive) {
        $sql .= " WHERE !inactive";
      }
      return DB::_query($sql, "could not get payment terms");
    }
    /**
     *  Return number of records in tables, where some foreign key $id is used.
     * $id - searched key value
     * $tables - array of table names (without prefix); when table name is used as a key, then
     * value is name of foreign key field. For numeric keys $stdkey field name is used.
     * $stdkey - standard name of foreign key.
     * @static
     *
     * @param      $id
     * @param      $tables
     * @param      $stdkey
     * @param bool $escaped
     *
     * @return mixed
     */
    public function key_in_foreign_table($id, $tables, $stdkey, $escaped = false) {
      if (!$escaped) {
        $id = DB::_escape($id);
      }
      if (!is_array($tables)) {
        $tables = array($tables);
      }
      $sqls = [];
      foreach ($tables as $tbl => $key) {
        if (is_numeric($tbl)) {
          $tbl = $key;
          $key = $stdkey;
        }
        $sqls[] = "(SELECT COUNT(*) as cnt FROM `$tbl` WHERE `$key`=" . DB::_escape($id) . ")\n";
      }
      $sql    = "SELECT sum(cnt) FROM (" . implode(' UNION ', $sqls) . ") as counts";
      $result = DB::_query($sql, "check relations for " . implode(',', $tables) . " failed");
      $count  = DB::_fetch($result);
      return $count[0];
    }
    /**
     * @static
     *
     * @param $id
     * @param $closed
     *
     * @return void
     */
    public function update_fiscalyear($id, $closed) {
      $sql = "UPDATE fiscal_year SET closed=" . DB::_escape($closed) . "
 WHERE id=" . DB::_escape($id);
      DB::_query($sql, "could not update fiscal year");
    }
    /**
     * @static
     *
     * @param array|null $data
     *
     * @return void
     */
    public function update_gl_setup(array $data = null) {
      $this->save($data);
    }
    /**
     * @static
     *
     * @param array|null $data
     *
     * @return void
     */
    public function update_setup(array $data = null) {
      if ($this->f_year == null) {
        $this->f_year = 0;
      }
      $this->save($data);
    }
  }
