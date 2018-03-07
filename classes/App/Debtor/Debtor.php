<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Debtor;

  use Debtor_Branch;
  use ADV\App\DB\Collection;
  use ADV\Core\Status;
  use ADV\App\Display;
  use ADV\App\Forms;
  use ADV\App\Validation;
  use ADV\App\User;
  use ADV\Core\Cell;
  use ADV\Core\Table;
  use ADV\App\UI;
  use ADV\Core\Session;
  use ADV\Core\Input\Input;
  use ADV\App\Dates;
  use ADV\Core\Dialog;
  use DB_Company;
  use ADV\Core\Num;
  use Bank_Currency;
  use ADV\Core\JS;
  use ADV\App\Contact\Contact;
  use Debtor_Account;

  /** **/
  class Debtor extends \Contact_Company
  {
    /** @var int * */
    public $id = 0;
    /** @var string * */
    public $name = '';
    /** @var */
    public $sales_type = 1;
    /** @var string * */
    public $debtor_ref = '';
    public $type = CT_CUSTOMER;
    /** @var */
    public $credit_status = 1;
    /** @var int * */
    public $payment_discount = 0;
    /** @var int * */
    public $defaultBranch = 0;
    /** @var int * */
    public $defaultContact = 0;
    /** @var \ADV\App\DB\Collection[\Debtor_Branches] * */
    public $branches = [];
    /** @var \ADV\App\DB\Collection[\ADV\App\Contact\Contact] * */
    public $contacts = [];
    /** @var \Debtor_Account * */
    public $accounts;
    /** @var */
    public $transactions;
    /** @var null * */
    public $webid = null;
    /** @var */
    public $email;
    /** @var */
    public $inactive = 0;
    /** @var */
    public $debtor_id = 0;
    /** @var */
    public $notes = '';
    /** @var string * */
    protected $_table = 'debtors';
    /** @var string * */
    protected $_id_column = 'debtor_id';
    protected $_classname = 'Customer';
    /** @var \ADV\Core\DB\DB */
    protected static $staticDB;
    /**
     * @param int|null $id
     */
    public function __construct($id = null) {
      $this->id =& $this->debtor_id;
      parent::__construct($id);
      $this->debtor_ref = substr($this->name, 0, 60);
    }
    /**
     * @param bool $string
     *
     * @return array|string
     */
    public function getStatus($string = false) {
      return parent::getStatus()->get();
    }
    /**
     * @param null $details
     *
     * @return \Debtor_Branch
     */
    public function addBranch($details = null) {
      $branch            = new Debtor_Branch($details);
      $branch->debtor_id = $this->id;
      $branch->save();
      $this->branches[$branch->branch_id] = $branch;
      $this->status(Status::INFO, 'Added new branch');
      return $branch;
    }
    /**
     * @return array|null
     */
    public function delete() {
      if ($this->countTransactions() > 0) {
        return $this->status(false, "This customer cannot be deleted because there are transactions that refer to it.");
      }
      if ($this->_countOrders() > 0) {
        return $this->status(false, "Cannot delete the customer record because orders have been created against it.");
      }
      if ($this->_countBranches() > 0) {
        return $this->status(false, "Cannot delete this customer because there are branch records set up against it.");
      }
      if ($this->_countContacts() > 0) {
        return $this->status(false, "Cannot delete this customer because there are contact records set up against it.");
      }
      $sql = "DELETE FROM debtors WHERE debtor_id=" . $this->id;
      static::$staticDB->_query($sql, "cannot delete customer");
      unset($this->id);
      $this->init();
      return $this->status(true, "Customer deleted.");
    }
    /**
     * @param $branch_id
     *
     * @return \ADV\Core\Traits\Status|bool
     */
    public function deleteBranch($branch_id) {
      if (!isset($this->branches[$branch_id])) {
        return $this->status(false, "The customer branch doesn't exist");
      }
      if (count($this->branches) == 1) {
        return $this->status(false, "The customer must have at least one branch");
      }
      unset($this->branches[$branch_id]);
      if (isset($this->branches[$branch_id])) {
        return $this->status(false, "The customer branch could not be deleted");
      }
      return $this->status(true, "The customer branch has been deleted");
    }
    /**
     * @return array|bool
     */
    public function getEmailAddresses() {
      $emails = [];
      if (!empty($this->accounts->email)) {
        $emails['Accounts'][$this->accounts->id] = array('Accounts', $this->accounts->email);
      }
      foreach ($this->contacts as $id => $contact) {
        if ($id > 0 && !empty($contact->email)) {
          $emails['Contacts'][$id] = array($contact->name, $contact->email);
        }
      }
      foreach ($this->branches as $id => $branch) {
        if ($id > 0 && !empty($branch->email)) {
          $emails['Branches'][$id] = array($branch->name, $branch->email);
        }
      }
      return (count($emails) > 0) ? $emails : false;
    }
    /**
     * @return array
     */
    public function getTransactions() {
      if ($this->id == 0) {
        return [];
      }
      $sql
               = "SELECT debtor_trans.*, sales_orders.customer_ref,
                        (debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
                        debtor_trans.ov_freight_tax + debtor_trans.ov_discount)
                        AS TotalAmount, debtor_trans.alloc AS Allocated
                        FROM debtor_trans LEFT OUTER JOIN sales_orders ON debtor_trans.order_ = sales_orders.order_no
                     WHERE debtor_trans.debtor_id = " . static::$staticDB->_escape($this->id) . "
                      AND sales_orders.debtor_id = " . static::$staticDB->_escape($this->id) . "
                         AND debtor_trans.type <> " . ST_CUSTDELIVERY . "
                         AND (debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
                        debtor_trans.ov_freight_tax + debtor_trans.ov_discount) != 0
                         ORDER BY debtor_trans.branch_id, debtor_trans.tran_date";
      $result  = static::$staticDB->_query($sql);
      $results = [];
      while ($row = static::$staticDB->_fetchAssoc($result)) {
        $results[] = $row;
      }
      return $results;
    }
    /**
     * @param array|null $changes
     *
     * @return array|bool|int|null|void
     */
    public function save($changes = null) {
      $data['debtor_ref']       = substr($this->name, 0, 29);
      $data['discount']         = User::_numeric($this->discount) / 100;
      $data['payment_discount'] = User::_numeric($this->payment_discount) / 100;
      $data['credit_limit']     = User::_numeric($this->credit_limit);
      if (!parent::save($changes)) {
        $this->setDefaults();
        return false;
      }
      $this->accounts->save(array('debtor_id' => $this->id));
      $this->branches->save($changes['branches']);
      $this->setDefaults();
      return true;
    }
    /**
     * @param null $changes
     *
     * @return array|null|void
     */
    protected function setFromArray($changes = null) {
      if (isset($changes['branches']) && is_array($changes['branches'])) {
        $this->branches->load($changes['branches']);
        unset($changes['branches']);
      }
      if (isset($changes['contacts']) && is_array($changes['contacts'])) {
        $this->contacts->load($changes['contacts']);
        unset($changes['contacts']);
      }
      parent::setFromArray($changes);
      if (isset($changes['accounts']) && is_array($changes['accounts'])) {
        $this->accounts = new Debtor_Account($changes['accounts']);
      }
      $this->credit_limit = str_replace(',', '', $this->credit_limit);
    }
    /**
     * @return array|bool|null
     */
    protected function canProcess() {
      if (!$this->name) {
        return $this->status(false, "The customer name cannot be empty.", 'name');
      }
      if (strlen($this->debtor_ref) == 0) {
        $this->debtor_ref = substr($this->name, 0, 29);
      }
      if (!Validation::is_num($this->credit_limit, 0)) {
        return $this->status(false, "The credit limit must be numeric and not less than zero.", 'credit_limit');
      }
      if (!Validation::is_num($this->payment_discount, 0, 100)) {
        return $this->status(
          false, "The payment discount must be numeric and is expected to be less than 100% and greater than or equal to 0.", 'payment_discount'
        );
      }
      if (!Validation::is_num($this->discount, 0, 100)) {
        return $this->status(
          false, "The discount percentage must be numeric and is expected to be less than 100% and greater than or equal to 0.", 'discount'
        );
      }
      if (!Validation::is_num($this->webid, 1)) {
        $this->webid = null;
      }
      if ($this->id != 0) {
        $previous = new Debtor($this->id);
        if ((filter_var($this->credit_limit, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) != filter_var(
          $previous->credit_limit, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION
        ) || $this->payment_terms != $previous->payment_terms) && !User::_i()->hasAccess(SA_CUSTOMER_CREDIT)
        ) {
          return $this->status(false, "You don't have access to alter credit limits", 'credit_limit');
        }
      }
      return true;
    }
    /**
     * @return int
     */
    protected function _countBranches() {
      static::$staticDB->_select('COUNT(*)')->from('branches')->where('debtor_id=', $this->id);
      return static::$staticDB->_numRows();
    }
    /**
     * @return int
     */
    protected function _countContacts() {
      static::$staticDB->_select('COUNT(*)')->from('contacts')->where('debtor_id=', $this->id);
      return static::$staticDB->_numRows();
    }
    /**
     * @return int
     */
    protected function _countOrders() {
      static::$staticDB->_select('COUNT(*)')->from('sales_orders')->where('debtor_id=', $this->id);
      return static::$staticDB->_numRows();
    }
    /**
     * @return int|mixed
     */
    protected function countTransactions() {
      static::$staticDB->_select('COUNT(*)')->from('debtor_trans')->where('debtor_id=', $this->id);
      return (int) static::$staticDB->_numRows();
    }
    /**
     * @return void
     */
    protected function defaults() {
      $this->branches = new Collection(new Debtor_Branch(), ['debtor_id', 'branch_ref']);
      $this->contacts = new Collection(new Contact(CT_CUSTOMER), ['parent_type', 'parent_id'], true);
      parent::defaults();
      $this->curr_code    = Bank_Currency::for_company();
      $this->discount     = $this->payment_discount = Num::_percentFormat(0);
      $this->credit_limit = Num::_priceFormat(DB_Company::_get_pref('default_credit_limit'));
      $this->accounts     = new Debtor_Account;
    }
    protected function _getAccounts() {
      static::$staticDB->_select()->from('branches')->where('debtor_id=', $this->debtor_id)->andWhere('branch_ref=', Debtor_Account::ACCOUNTS);
      $this->accounts = static::$staticDB->_fetch()->asClassLate('Debtor_Account')->one();
      if (!$this->accounts && $this->id > 0 && $this->defaultBranch > 0) {
        $this->accounts          = clone($this->branches[$this->defaultBranch]);
        $this->accounts->br_name = 'Accounts Department';
        $this->accounts->save();
      }
    }
    protected function _getBranches() {
      //$this->branches->getAll(['debtor_id' => $this->id, 'branch_ref' => Debtor_Branch::DELIVERY]);
      $this->branches->getAll(['debtor_id' => $this->id, 'branch_ref' => Debtor_Branch::DELIVERY]);
    }
    /**
     * @return void
     */
    protected function _getContacts() {
      $this->contacts->getAll(['parent_type' => CT_CUSTOMER, 'parent_id' => $this->id]);
    }
    /**
     * @return array|null
     */
    protected function init() {
      $this->defaults();
      $this->branches[0]            = new Debtor_Branch;
      $this->branches[0]->debtor_id = 0;
      $this->accounts->debtor_id = $this->id = 0;
      $this->setDefaults();
      return $this->status(true, 'Now working with a new customer');
    }
    /**
     * @param bool|int|null $id
     * @param array         $extra
     *
     * @return array|bool
     */
    protected function read($id = null, $extra = []) {
      if (!parent::read($id)) {
        return $this->status->get();
      }
      $this->_getBranches();
      $this->_getAccounts();
      $this->_getContacts();
      $this->discount         = $this->discount * 100;
      $this->payment_discount = $this->payment_discount * 100;
      $this->credit_limit     = Num::_priceFormat($this->credit_limit);
      $this->setDefaults();
      return true;
    }
    /**
     * @return void
     */
    protected function setDefaults() {
      $this->defaultContact = count($this->contacts) ? $this->contacts->first()->id : 0;
      $this->defaultBranch  = $this->branches->first()->id;
    }
    /**
     * @static
     * @return void
     */
    public static function addEditDialog() {
      $customerBox = new Dialog('Customer Edit', 'customerBox', '');
      $customerBox->addButtons(array('Close' => '$(this).dialog("close");'));
      $customerBox->addBeforeClose('$("#debtor_id").trigger("change")');
      $customerBox->setOptions(
        [
             'autoOpen'   => false,
             'modal'      => true,
             'width'      => '850',
             'height'     => '715',
             'resizeable' => true
        ]
      );
      $customerBox->show();
      $js
        = <<<JS
                            var val = $("#debtor_id").val();
                            $("#customerBox").html("<iframe src='/contacts/manage/customers?frame=1&id="+val+"' width='100%' height='595' scrolling='no' style='border:none' frameborder='0'></iframe>").dialog('open');
JS;
      JS::_addLiveEvent('#debtor_id_label', 'click', $js);
    }
    /**
     * @static
     *
     * @param       $id
     * @param array $options
     *
     * @return void
     */
    public static function addSearchBox($id, $options = []) {
      UI::searchLine($id, '/contacts/search.php', $options);
    }
    /**
     * @static
     *
     * @param $terms
     *
     * @return array
     */
    public static function search($terms) {
      $data  = [];
      $terms = preg_replace("/[^a-zA-Z 0-9]+/", " ", $terms);
      $sql   = static::$staticDB->_select('debtor_id as id', 'name as label', 'name as value', "IF(name LIKE " . static::$staticDB->_quote(trim($terms) . '%') . ",0,5) as weight")
        ->from(
          'debtors'
        )->where('name LIKE ', trim($terms) . "%")->orWhere('name LIKE ', trim($terms))->orWhere('name LIKE', '%' . str_replace(' ', '%', trim($terms)) . "%");
      if (is_numeric($terms)) {
        $sql->orWhere('debtor_id LIKE', "$terms%");
      }
      $sql->orderby('weight,name')->limit(20);
      $results = static::$staticDB->_fetch();
      foreach ($results as $result) {
        $data[] = @array_map('htmlspecialchars_decode', $result);
      }
      return $data;
    }
    /**
     * @static
     *
     * @param       $term
     * @param array $options
     *
     * @return array|string
     */
    public static function searchOrder($term, $options = []) {
      $defaults = array('inactive' => false, 'selected' => '');
      $o        = array_merge($defaults, $options);
      $term     = explode(' ', $term);
      $term1    = static::$staticDB->_escape(trim($term[0]) . '%');
      $term2    = static::$staticDB->_escape(
        '%' . implode(
          ' AND name LIKE ', array_map(
            function ($v) {
              return trim($v);
            }, $term
          )
        ) . '%'
      );
      $where    = ($o['inactive'] ? '' : ' AND inactive = 0 ');
      $sql
                = "(SELECT debtor_id as id, name as label, debtor_id as value, name as description FROM debtors WHERE name LIKE $term1 $where ORDER BY name LIMIT 20)
                                    UNION (SELECT debtor_id as id, name as label, debtor_id as value, name as description FROM debtors
                                    WHERE debtor_ref LIKE $term1 OR name LIKE $term2 OR debtor_id LIKE $term1 $where ORDER BY debtor_id, name LIMIT 20)";
      $result   = static::$staticDB->_query($sql, 'Couldn\'t Get Customers');
      $data     = '';
      while ($row = static::$staticDB->_fetchAssoc($result)) {
        foreach ($row as &$value) {
          $value = htmlspecialchars_decode($value);
        }
        $data[] = $row;
      }
      return $data;
    }
    /**
     * @static
     *
     * @param      $debtor_id
     * @param null $to
     * @param bool $istimestamp
     *
     * @return Array|\ADV\Core\DB\Query\Result
     */
    public static function get_details($debtor_id, $to = null, $istimestamp = false) {
      if ($to == null) {
        $todate = date("Y-m-d");
      } else {
        $todate = ($istimestamp) ? date("Y-m-d", $to) : Dates::_dateToSql($to);
      }
      $customer_record["Balance"]  = 0;
      $customer_record["Due"]      = 0;
      $customer_record["Overdue1"] = 0;
      $customer_record["Overdue2"] = 0;
      if ($debtor_id == 0) {
        return $customer_record;
      }
      $past_due1 = DB_Company::_get_pref('past_due_days');
      $past_due2 = 2 * $past_due1;
      // removed - debtor_trans.alloc from all summations
      $value = "IF(debtor_trans.type=" . ST_CUSTCREDIT . " OR debtor_trans.type=" . ST_BANKDEPOSIT . " OR debtor_trans.type=" . ST_CUSTPAYMENT . ",
        -1, 1) *" . "(debtor_trans.ov_amount + debtor_trans.ov_gst + " . "debtor_trans.ov_freight + debtor_trans.ov_freight_tax + " . "debtor_trans.ov_discount)";
      $due   = "IF (debtor_trans.type=10,debtor_trans.due_date,debtor_trans.tran_date)";
      $sql
              = "SELECT debtors.name, debtors.curr_code, payment_terms.terms,		debtors.credit_limit, credit_status.dissallow_invoices, credit_status.reason_description,
            Sum(" . $value . ") AS Balance,
            Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= 0,$value,0)) AS Due,
            Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past_due1,$value,0)) AS Overdue1,
            Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past_due2,$value,0)) AS Overdue2
            FROM debtors,
                 payment_terms,
                 credit_status,
                 debtor_trans
            WHERE
                 debtors.payment_terms = payment_terms.terms_indicator
                 AND debtors.credit_status = credit_status.id
                 AND debtors.debtor_id = " . static::$staticDB->_escape($debtor_id) . "
                 AND debtor_trans.tran_date <= '$todate'
                 AND debtor_trans.type <> 13
                 AND debtors.debtor_id = debtor_trans.debtor_id
            GROUP BY
                 debtors.name,
                 payment_terms.terms,
                 payment_terms.days_before_due,
                 payment_terms.day_in_following_month,
                 debtors.credit_limit,
                 credit_status.dissallow_invoices,
                 credit_status.reason_description";
      $result = static::$staticDB->_query($sql, "The customer details could not be retrieved");
      if (static::$staticDB->_numRows($result) == 0) {
        /* Because there is no balance - so just retrieve the header information about the customer - the choice is do one query to get the balance and transactions for those customers who have a balance and two queries for those who don't have a balance OR always do two queries - I opted for the former */
        $sql
                = "SELECT debtors.name, debtors.curr_code, debtors.debtor_id, payment_terms.terms,
             debtors.credit_limit, credit_status.dissallow_invoices, credit_status.reason_description
             FROM debtors,
              payment_terms,
              credit_status

             WHERE
              debtors.payment_terms = payment_terms.terms_indicator
              AND debtors.credit_status = credit_status.id
              AND debtors.debtor_id = " . static::$staticDB->_escape($debtor_id);
        $result = static::$staticDB->_query($sql, "The customer details could not be retrieved");
      }
      $customer_record = static::$staticDB->_fetch($result);
      return $customer_record;
    }
    /**
     * @static
     *
     * @param $debtor_id
     *
     * @return Array|\ADV\Core\DB\Query\Result
     */
    public static function get($debtor_id) {
      $sql    = "SELECT * FROM debtors WHERE debtor_id=" . static::$staticDB->_escape($debtor_id);
      $result = static::$staticDB->_query($sql, "could not get customer");
      return static::$staticDB->_fetch($result);
    }
    /**
     * @static
     *
     * @param $debtor_id
     *
     * @return mixed
     */
    public static function get_name($debtor_id) {
      static::$staticDB->_select('name')->from('debtors')->where('debtor_id=', $debtor_id)->fetch()->one();
      $sql    = "SELECT name FROM debtors WHERE debtor_id=" . static::$staticDB->_escape($debtor_id);
      $result = static::$staticDB->_query($sql, "could not get customer");
      $row    = static::$staticDB->_fetchRow($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param $debtor_id
     *
     * @return Array|\ADV\Core\DB\Query\Result
     */
    public static function get_habit($debtor_id) {
      $sql
              = "SELECT debtors.payment_discount,
                 credit_status.dissallow_invoices
                FROM debtors, credit_status
                WHERE debtors.credit_status = credit_status.id
                    AND debtors.debtor_id = " . static::$staticDB->_escape($debtor_id);
      $result = static::$staticDB->_query($sql, "could not query customers");
      return static::$staticDB->_fetch($result);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    public static function get_area($id) {
      $sql    = "SELECT description FROM areas WHERE area_code=" . static::$staticDB->_escape($id);
      $result = static::$staticDB->_query($sql, "could not get sales type");
      $row    = static::$staticDB->_fetchRow($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    public static function get_salesman_name($id) {
      $sql    = "SELECT salesman_name FROM salesman WHERE salesman_code=" . static::$staticDB->_escape($id);
      $result = static::$staticDB->_query($sql, "could not get sales type");
      $row    = static::$staticDB->_fetchRow($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param $debtor_id
     *
     * @return int
     */
    public static function get_credit($debtor_id) {
      $custdet = Debtor::get_details($debtor_id);
      return ($debtor_id > 0 && isset ($custdet['credit_limit'])) ? $custdet['credit_limit'] - $custdet['Balance'] : 0;
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return bool
     */
    public static function is_new($id) {
      $tables = array('branches', 'debtor_trans', 'recurrent_invoices', 'sales_orders');
      return !DB_Company::_key_in_foreign_table($id, $tables, 'debtor_id');
    }
    /**
     * @static
     *
     * @param null  $value
     * @param array $options
     *
     * @return void
     */
    public static function newselect($value = null, $options = []) {
      $o = [
        'row'         => true, //
        'cell_params' => '', //
        'rowspan'     => null, //
        'label'       => 'Customer:', //
        'cells'       => true, //
        'cell_class'  => null,
        'placeholder' => "Customer",
      ];
      $o = array_merge($o, $options);
      if ($o['row']) {
        echo "<tr>";
      }
      if ($o['label']) {
        echo "<td id='customer_id_label' class='label pointer'>Customer: </td>";
      }
      echo "<td class='nowrap'>";
      $focus = false;
      if (!$value && Input::_hasPost('customer')) {
        $value = $_POST['customer'];
        if (!Input::_post('customer')) {
          $_POST['debtor_id'] = null;
        }
      } elseif (!$value) {
        $value = Session::_getGlobal('debtor_id');
        if ($value) {
          $_POST['debtor_id'] = $value;
          $value              = Debtor::get_name($value);
        } else {
          JS::_setFocus('customer');
          $focus = true;
          $value = null;
        }
      }
      Forms::hidden('debtor_id');
      UI::search(
        'customer', array(
                         'url'         => 'Debtor',
                         'name'        => 'customer',
                         'idField'     => 'debtor_id',
                         'focus'       => $focus,
                         'class'       => '',
                         'value'       => $value,
                         'placeholder' => $o['placeholder']
                    )
      );
      echo "</td>";
      if ($o['row']) {
        echo "\n</tr>\n";
      }
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_option
     * @param bool $submit_on_change
     * @param bool $show_inactive
     * @param bool $editkey
     * @param bool $async
     *
     * @return string
     */
    public static function select($name, $selected_id = null, $spec_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false, $async = false) {
      $sql  = "SELECT debtor_id, name as debtor_ref, curr_code, inactive FROM debtors ";
      $mode = DB_Company::_get_pref('no_customer_list');
      return Forms::selectBox(
        $name, $selected_id, $sql, 'debtor_id', 'name', array(
                                                             'format'        => 'Forms::addCurrFormat',
                                                             'order'         => array('name'),
                                                             'search_box'    => $mode != 0,
                                                             'type'          => 1,
                                                             'size'          => 20,
                                                             'spec_option'   => $spec_option === true ? _("All Customers") : $spec_option,
                                                             'spec_id'       => ALL_TEXT,
                                                             'select_submit' => $submit_on_change,
                                                             'async'         => $async,
                                                             'sel_hint'      => $mode ? _('Press Space tab to filter by name fragment; F2 - entry new customer') :
                                                               _('Select customer'),
                                                             'show_inactive' => $show_inactive
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
     * @param bool $submit_on_change
     * @param bool $show_inactive
     * @param bool $editkey
     * @param bool $async
     *
     * @return void
     */
    public static function cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false, $async = false) {
      echo "<td class='nowrap'>";
      if ($label != null) {
        echo "<label for=\"$name\"> $label</label>";
      }
      echo Debtor::select($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey, $async);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $show_inactive
     * @param bool $editkey
     *
     * @return void
     */
    public static function row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false) {
      echo "<tr><td id='customer_id_label' class='label pointer'>$label</td><td class='nowrap'>";
      echo Debtor::select($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey);
      echo "</td>\n</tr>\n";
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $trans_no
     * @param string $label
     * @param bool   $icon
     * @param string $class
     * @param string $id
     *
     * @return null|string
     */
    public static function viewTrans($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
      $viewer = "/sales/view/";
      switch ($type) {
        case ST_SALESINVOICE:
          $viewer .= "view_invoice";
          break;
        case ST_CUSTCREDIT:
          $viewer .= "view_credit";
          break;
        case ST_CUSTPAYMENT:
          $viewer .= "view_receipt";
          break;
        case ST_CUSTREFUND:
          $viewer .= "view_receipt";
          break;
        case ST_CUSTDELIVERY:
          $viewer .= "view_dispatch";
          break;
        case ST_SALESORDER:
        case ST_SALESQUOTE:
          $viewer .= "view_sales_order";
          break;
        default:
          return null;
      }
      if (!is_array($trans_no)) {
        $trans_no = array($trans_no);
      }
      $lbl         = $label;
      $preview_str = '';
      foreach ($trans_no as $trans) {
        if ($label == "") {
          $lbl = $trans;
        }
        if ($preview_str != '') {
          $preview_str .= ',';
        }
        $preview_str .= Display::viewer_link($lbl, $viewer . "?trans_no=$trans&trans_type=$type", $class, $id, $icon);
      }
      return $preview_str;
    }
    /**
     * @param $customer_record
     *
     * @return void
     */
    public static function display_summary($customer_record) {
      //$past_due1 = DB_Company::_get_pref('past_due_days');
      //$past_due2 = 2 * $past_due1;
      if (isset($customer_record["dissallow_invoices"]) && $customer_record["dissallow_invoices"] != 0) {
        echo "<div class='center red font4 bold'>" . _("CUSTOMER ACCOUNT IS ON HOLD") . "</div>";
      }
      Table::start('padded width90');
      $th = array(_("1-30 Days"), _("Terms"), "1-30 Days", "31-60 Days", "61-90 Days", "90+ Days", _("Total Balance"));
      Table::header($th);
      echo '<tr>';
      if (isset($customer_record["curr_code"])) {
        Cell::label($customer_record["curr_code"]);
      } else {
        unset($th[0]);
      }
      if (isset($customer_record["curr_code"])) {
        Cell::label($customer_record["terms"]);
      } else {
        unset($th[0]);
      }
      Cell::amount($customer_record["Balance"] - $customer_record["Due"]);
      Cell::amount($customer_record["Due"] - $customer_record["Overdue1"]);
      Cell::amount($customer_record["Overdue1"] - $customer_record["Overdue2"]);
      Cell::amount($customer_record["Overdue2"]);
      Cell::amount($customer_record["Balance"]);
      echo '</tr>';
      Table::end();
    }
    /**
     * @param $db
     */
    public static function setDB($db) {
      static::$staticDB = $db;
    }
  }

  Debtor::setDB(\ADV\Core\DB\DB::i());
