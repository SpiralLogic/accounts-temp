<?php
  use ADV\Core\DB\DB;
  use ADV\App\Forms;
  use ADV\App\User;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Debtor_Branch extends \ADV\App\DB\Base
  {
    const ACCOUNTS = 'accounts';
    const DELIVERY = 'delivery';
    /** @var string * */
    public $post_address = '';
    /** @var int * */
    public $branch_id = 0;
    /** @var string * */
    public $br_name = "New Address";
    /** @var string * */
    public $br_address = '';
    /** @var string * */
    public $city = '';
    /** @var string * */
    public $state = '';
    /** @var string * */
    public $postcode = '';
    /** @var int * */
    public $area = DEFAULT_AREA;
    /** @var */
    public $br_post_address;
    /** @var */
    public $debtor_id;
    /** @var string * */
    public $branch_ref = self::DELIVERY;
    /** @var string * */
    public $contact_name = "";
    /** @var */
    public $default_location;
    /** @var int * */
    public $default_ship_via = DEFAULT_SHIP_VIA;
    /** @var int * */
    public $disable_trans = 0;
    /** @var string * */
    public $phone = '';
    /** @var string * */
    public $phone2 = '';
    /** @var string * */
    public $fax = '';
    /** @var string * */
    public $website = '';
    /** @var string * */
    public $email = '';
    /** @var int * */
    public $inactive = 0;
    /** @var string * */
    public $notes = '';
    /** @var int * */
    public $group_no = 1;
    /** @var */
    public $payment_discount_account;
    /** @var */
    public $receivables_account;
    /** @var string * */
    public $sales_account = "";
    /** @var */
    public $sales_discount_account;
    /** @var */
    public $salesman;
    /** @var int * */
    public $tax_group_id = DEFAULT_TAX_GROUP;
    /** @var string * */
    protected $_table = 'branches';
    /** @var string * */
    protected $_id_column = 'branch_id';
    /**
     * @param int|null $id
     */
    public function __construct($id = null) {
      $this->id = & $this->branch_id;
      parent::__construct($id);
      $this->name         = & $this->br_name;
      $this->address      = & $this->br_address;
      $this->post_address = & $this->br_post_address;
    }
    /**
     * @return string
     */
    public function getAddress() {
      $address = $this->br_address . "\n";
      if ($this->city) {
        $address .= $this->city;
      }
      if ($this->state) {
        $address .= ", " . strtoupper($this->state);
      }
      if ($this->postcode) {
        $address .= ", " . $this->postcode;
      }
      return $address;
    }
    /**
     * @return array|bool|null
     */
    protected function canProcess() {
      if (strlen($this->br_name) < 1) {
        return $this->status(false, 'Branch name can not be empty');
      }
      return true;
    }
    /**
     * @param null $changes
     *
     * @return array|bool|int|null|void
     */
    public function save($changes = null) {
      unset($changes['address'], $changes['name'], $changes['br_postaddress']);
      return parent::save($changes);
    }
    /**
     * @return void
     */
    protected function countTransactions() {
    }
    /**
     * @return void
     */
    protected function defaults() {
      parent::defaults();
      $company_record                 = DB_Company::_get_prefs();
      $this->default_location         = Config::_get('default.location');
      $this->sales_discount_account   = $company_record['default_sales_discount_act'];
      $this->receivables_account      = $company_record['debtors_act'];
      $this->payment_discount_account = $company_record['default_prompt_payment_act'];
      $this->salesman                 = User::_i()->salesmanid ? : 1;
    }
    /**
     * @return array|null
     */
    protected function init() {
      $this->defaults();
      return $this->status(true, 'Now working with a new Branch');
    }
    /**
     * @param null $changes
     *
     * @return array|null|void
     */
    protected function setFromArray($changes = null) {
      parent::setFromArray($changes);
      if (!empty($this->city)) {
        $this->br_name = $this->city . " " . strtoupper($this->state);
      }
      if ($this->branch_ref != self::ACCOUNTS) {
        $this->branch_ref = self::DELIVERY;
      }
    }
    /**
     * @param null  $id
     * @param array $extra
     *
     * @internal param bool|int|null $params
     * @return array|bool|null
     */
    protected function read($id = null, $extra = []) {
      if (!$id) {
        return $this->status(false, 'No Branch parameters provided');
      }
      $this->defaults();
      if (!is_array($id)) {
        $id = array('branch_id' => $id);
      }
      $sql = DB::_select('b.*', 'a.description', 's.salesman_name', 't.name AS tax_group_name')->from('branches b, debtors c, areas a, salesman s, tax_groups t')->where(
        array(
             'b.debtor_id=c.debtor_id',
             'b.tax_group_id=t.id',
             'b.area=a.area_code',
             'b.salesman=s.salesman_code'
        )
      );
      foreach ($id as $key => $value) {
        $sql->where("b.$key=", $value);
      }
      DB::_fetch()->intoClass($this);
      return $this->status(true, 'Read Branch from Database');
    }
    /**
     * @static
     *
     * @param      $debtor_id
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_option
     * @param bool $enabled
     * @param bool $submit_on_change
     * @param bool $editkey
     *
     * @return string
     */
    public static function select($debtor_id, $name, $selected_id = null, $spec_option = true, $enabled = true, $submit_on_change = false, $editkey = false) {
      $sql
             = "SELECT branch_id, br_name FROM branches
            WHERE branch_ref <> '" . self::ACCOUNTS . "' AND inactive <> 1  AND debtor_id='" . $debtor_id . "' ";
      $where = $enabled ? array("disable_trans = 0") : [];
      return Forms::selectBox(
        $name, $selected_id, $sql, 'branch_id', 'br_name', array(
                                                                'where'         => $where,
                                                                'order'         => array('br_name'),
                                                                'spec_option'   => $spec_option === true ? _('All branches') : $spec_option,
                                                                'spec_id'       => ALL_TEXT,
                                                                'select_submit' => $submit_on_change,
                                                                'sel_hint'      => _('Select customer branch')
                                                           )
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $debtor_id
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $enabled
     * @param bool $submit_on_change
     * @param bool $editkey
     *
     * @return void
     */
    public static function cells($label, $debtor_id, $name, $selected_id = null, $all_option = true, $enabled = true, $submit_on_change = false, $editkey = false) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Debtor_Branch::select($debtor_id, $name, $selected_id, $all_option, $enabled, $submit_on_change, $editkey);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $debtor_id
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $enabled
     * @param bool $submit_on_change
     * @param bool $editkey
     *
     * @return void
     */
    public static function row($label, $debtor_id, $name, $selected_id = null, $all_option = true, $enabled = true, $submit_on_change = false, $editkey = false) {
      echo "<tr><td class='label'>$label</td>";
      Debtor_Branch::cells(null, $debtor_id, $name, $selected_id, $all_option, $enabled, $submit_on_change, $editkey);
      echo "</tr>";
    }
  }
