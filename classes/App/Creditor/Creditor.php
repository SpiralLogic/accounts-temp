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
  namespace ADV\App\Creditor;

  use ADV\App\UI;
  use ADV\App\Forms;
  use ADV\App\Validation;
  use ADV\App\User;
  use ADV\Core\DB\DB;
  use ADV\Core\Session;
  use ADV\Core\Input\Input;
  use ADV\App\Dates;
  use ADV\Core\Dialog;
  use DB_Company;
  use ADV\Core\Num;
  use ADV\Core\JS;
  use ADV\App\Contact\Contact;

  /** **/
  class Creditor extends \Contact_Company
  {
    /**
     * @static
     *
     * @param $terms
     *
     * @return array|string
     */
    public static function search($terms) {
      $data  = [];
      $terms = preg_replace("/[^a-zA-Z 0-9]+/", " ", $terms);
      $sql   = static::$staticDB->_select('creditor_id as id', 'name as label', 'name as value', "IF(name LIKE " . static::$staticDB->_quote(trim($terms) . '%') . ",0,5) as weight"
      )                         ->from('suppliers'
        )                       ->where('name LIKE ', trim($terms) . "%")->orWhere('name LIKE ', trim($terms))
                                ->orWhere('name LIKE', '%' . str_replace(' ', '%', trim($terms)) . "%");
      if (is_numeric($terms)) {
        $sql->orWhere('creditor_id LIKE', "$terms%");
      }
      $sql->orderby('weight,name')->limit(20);
      $results = static::$staticDB->_fetch();
      foreach ($results as $result) {
        $data[] = @array_map('htmlspecialchars_decode', $result);
      }
      return $data;
    }
    /** @var */
    /** @var */
    public $id = 0, $creditor_id = 0; //
    /** @var */
    public $name = ''; //
    /** @var */
    /** @var */
    public $tax_id, $gst_no; //
    /** @var */
    /** @var */
    public $contact; //
    /** @var */
    /** @var */
    public $post_address, $address; //
    /** @var string * */
    public $city = "";
    /** @var string * */
    public $state = "";
    /** @var string * */
    public $postcode = "";
    /** @var string * */
    public $phone = "";
    /** @var string * */
    public $phone2 = "";
    /** @var string * */
    public $supp_phone;
    /** @var string * */
    public $fax = "";
    /** @var string * */
    public $notes = "";
    /** @var int * */
    public $inactive = 0;
    /** @var */
    public $website;
    /** @var string * */
    public $email = "";
    /** @var string * */
    /** @var string * */
    public $account_no = '';
    /** @var */
    public $bank_account;
    /** @var string * */
    public $tax_group_id = 1;
    /** @var */
    public $purchase_account;
    /** @var */
    public $payable_account;
    /** @var */
    public $payment_discount_account;
    public $type = CT_SUPPLIER;
    /** @var string * */
    public $supp_ref = '';
    /** @var Contact[] * */
    public $contacts = [];
    /** @var */
    public $defaultContact;
    /** @var */
    public $supp_city;
    public $supp_address;
    /** @var */
    public $supp_state;
    /** @var */
    public $supp_postcode;
    /** @var string * */
    protected $_table = 'suppliers';
    /** @var string * */
    protected $_id_column = 'creditor_id';
    protected $_classname = 'Supplier';
    /** @var DB */
    protected static $staticDB;
    /**
     * @param int|null $id
     */
    public function __construct($id = null) {
      static::$staticDB  = \ADV\Core\DB\DB::i();
      $this->creditor_id =& $this->id;
      parent::__construct($id);
      $this->gst_no     = & $this->tax_id;
      $this->address    = & $this->post_address;
      $this->supp_phone = & $this->phone2;
    }
    /**
     * @return array
     */
    public function getEmailAddresses() {
      return $this->email ? array('Accounts' => array($this->id => array($this->name, $this->email))) : false;
    }
    /**
     * @param array|null $changes
     *
     * @return array|bool|int|null|void
     */
    public function save($changes = null) {
      $this->supp_ref = $this->name;
      if (!parent::save($changes)) {
        $this->setDefaults();
        return false;
      }
      foreach ($this->contacts as $contact) {
        if ($contact instanceof Contact) {
          $contact->save(array('parent_id' => $this->id));
        }
      }
      $this->setDefaults();
      return true;
    }
    public function delete() {
      // TODO: Implement delete() method.
    }
    /**
     * @param null $changes
     *
     * @return array|null|void
     */
    protected function setFromArray($changes = null) {
      parent::setFromArray($changes);
      if (isset($changes['contacts']) && is_array($changes['contacts'])) {
        foreach ($changes['contacts'] as $contact) {
          $this->contacts[] = new Contact(CT_SUPPLIER, $contact);
        }
      } else {
        $this->contacts = [];
      }
      $this->discount     = User::_numeric($this->discount) / 100;
      $this->supp_ref     = substr($this->name, 0, 29) ? : '';
      $this->credit_limit = str_replace(',', '', $this->credit_limit);
    }
    /**
     * @return void
     */
    protected function setDefaults() {
      $this->contacts[]     = new Contact(CT_SUPPLIER, array('parent_id' => $this->id));
      $this->defaultContact = (count($this->contacts) > 0) ? reset($this->contacts)->id : 0;
    }
    /**
     * @return bool
     */
    protected function canProcess() {
      if (strlen($this->name) == 0) {
        return $this->status(false, "The supplier name cannot be empty.", 'name');
      }
      if (strlen($this->creditor_id) == 0) {
        $data['debtor_ref'] = substr($this->name, 0, 29);
      }
      if (!Validation::is_num($this->credit_limit, 0)) {
        return $this->status(false, "The credit limit must be numeric and not less than zero.", 'credit_limit');
      }
      if (!Validation::is_num($this->discount, 0, 100)) {
        return $this->status(false, 'Processing', "The discount percentage must be numeric and is expected to be less than 100% and greater than or equal to 0.", 'discount'
        );
      }
      return true;
    }
    /**
     * @return mixed|void
     */
    protected function countTransactions() {
      // TODO: Implement countTransactions() method.
    }
    /**
     * @return bool|\ADV\Core\Status
     */
    protected function defaults() {
      parent::defaults();
      $this->credit_limit             = Num::_priceFormat(0);
      $company_record                 = DB_Company::_get_prefs();
      $this->curr_code                = $company_record["curr_default"];
      $this->payable_account          = $company_record["creditors_act"];
      $this->purchase_account         = $company_record["default_cogs_act"];
      $this->payment_discount_account = $company_record['pyt_discount_act'];
    }
    /**
     * @return bool|\ADV\Core\Status
     */
    protected function init() {
      parent::init();
      $this->setDefaults();
      $this->id = (int)$this->id;
    }
    /**
     * @return void
     */
    protected function _getContacts() {
      $this->contacts = [];
      static::$staticDB->_select()->from('contacts')->where('parent_id=', $this->id)->andWhere('parent_type=', CT_SUPPLIER)->orderby('name DESC');
      $contacts = static::$staticDB->_fetch()->asClassLate('Contact', array(CT_SUPPLIER));
      if (count($contacts)) {
        foreach ($contacts as $contact) {
          $this->contacts[] = $contact;
        }
        $this->defaultContact = reset($this->contacts)->id;
      }
    }
    /**
     * @static
     * @return void
     */
    public static function addEditDialog() {
      $customerBox = new Dialog('Supplier Edit', 'supplierBox', '');
      $customerBox->addButtons(array('Close' => '$(this).dialog("close");'));
      $customerBox->addBeforeClose('$("#creditor_id").trigger("change")');
      $customerBox->setOptions(array(
                                    'autoOpen'   => false,
                                    'modal'      => true,
                                    'width'      => '850',
                                    'height'     => '715',
                                    'resizeable' => true
                               )
      );
      $customerBox->show();
      $js
        = <<<JS
                            var val = $("#creditor_id").val();
                            $("#supplierBox").html("<iframe src='/contacts/manage/suppliers?frame=1&id="+val+"' width='100%' height='595' scrolling='no' style='border:none' frameborder='0'></iframe>").dialog('open');
JS;
      JS::_addLiveEvent('#creditor_id_label', 'click', $js);
    }
    /**
     * @param bool|int|null $id
     * @param array         $extra
     *
     * @return array|bool
     */
    protected function read($id = false, $extra = []) {
      if (!parent::read($id)) {
        return $this->status->get();
      }
      $this->_getContacts();
      $this->discount     = $this->discount * 100;
      $this->credit_limit = Num::_priceFormat($this->credit_limit);
      $this->setDefaults();
      return $this;
    }
    /**
     * @static
     *
     * @param      $creditor_id
     * @param null $to
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get_to_trans($creditor_id, $to = null) {
      if ($to == null) {
        $todate = date("Y-m-d");
      } else {
        $todate = Dates::_dateToSql($to);
      }
      $past_due1 = DB_Company::_get_pref('past_due_days') ? : 30;
      $past_due2 = 2 * $past_due1;
      // removed - creditor_trans.alloc from all summations
      $value = "(creditor_trans.ov_amount + creditor_trans.ov_gst + creditor_trans.ov_discount)";
      $due   = "IF (creditor_trans.type=" . ST_SUPPINVOICE . " OR creditor_trans.type=" . ST_SUPPCREDIT . ",creditor_trans.due_date,creditor_trans.tran_date)";
      $sql
              = "SELECT suppliers.name, suppliers.curr_code, payment_terms.terms,
        Sum($value) AS Balance,
        Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= 0,$value,0)) AS Due,
        Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past_due1,$value,0)) AS Overdue1,
        Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past_due2,$value,0)) AS Overdue2
        FROM suppliers,
             payment_terms,
             creditor_trans
        WHERE
             suppliers.payment_terms = payment_terms.terms_indicator
             AND suppliers.creditor_id = " . static::$staticDB->_quote($creditor_id) . "
             AND creditor_trans.tran_date <= '$todate'
             AND suppliers.creditor_id = creditor_trans.creditor_id
        GROUP BY
             suppliers.name,
             payment_terms.terms,
             payment_terms.days_before_due,
             payment_terms.day_in_following_month";
      $result = static::$staticDB->_query($sql, "The supplier details could not be retrieved");
      if (static::$staticDB->_numRows($result) == 0) {
        /*Because there is no balance - so just retrieve the header information about the customer - the choice is do one query to get the balance and transactions for those customers who have a balance and two queries for those who don't have a balance OR always do two queries - I opted for the former */
        $nil_balance = true;
        $sql
                     = "SELECT suppliers.name, suppliers.curr_code, suppliers.creditor_id, payment_terms.terms FROM suppliers,
                 payment_terms WHERE
                 suppliers.payment_terms = payment_terms.terms_indicator
                 AND suppliers.creditor_id = " . static::$staticDB->_escape($creditor_id);
        $result      = static::$staticDB->_query($sql, "The customer details could not be retrieved");
      } else {
        $nil_balance = false;
      }
      $supp = static::$staticDB->_fetch($result);
      if ($nil_balance == true) {
        $supp["Balance"]  = 0;
        $supp["Due"]      = 0;
        $supp["Overdue1"] = 0;
        $supp["Overdue2"] = 0;
      }
      return $supp;
    }
    /**
     *   Get how much we owe the supplier for the period
     *
     * @param $creditor_id
     * @param $date_from
     * @param $date_to
     *
     * @return mixed
     */
    public static function get_oweing($creditor_id, $date_from, $date_to) {
      $date_from = Dates::_dateToSql($date_from);
      $date_to   = Dates::_dateToSql($date_to);
      // Sherifoz 22.06.03 Also get the description
      $sql
               = "SELECT


     SUM((trans.ov_amount + trans.ov_gst + trans.ov_discount)) AS Total


     FROM creditor_trans as trans
     WHERE trans.ov_amount != 0
        AND trans . tran_date >= '$date_from'
        AND trans . tran_date <= '$date_to'
        AND trans.creditor_id = " . static::$staticDB->_escape($creditor_id) . "
        AND trans.type = " . ST_SUPPINVOICE;
      $result  = static::$staticDB->_query($sql);
      $results = static::$staticDB->_fetch($result);
      return $results['Total'];
    }
    /**
     * @static
     *
     * @param $creditor_id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get($creditor_id) {
      $sql    = "SELECT * FROM suppliers WHERE creditor_id=" . static::$staticDB->_escape($creditor_id);
      $result = static::$staticDB->_query($sql, "could not get supplier");
      return static::$staticDB->_fetch($result);
    }
    /**
     * @static
     *
     * @param $creditor_id
     *
     * @return mixed
     */
    public static function get_name($creditor_id) {
      $sql    = "SELECT name AS name FROM suppliers WHERE creditor_id=" . static::$staticDB->_escape($creditor_id);
      $result = static::$staticDB->_query($sql, "could not get supplier");
      $row    = static::$staticDB->_fetchRow($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param $creditor_id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get_accounts_name($creditor_id) {
      $sql    = "SELECT payable_account,purchase_account,payment_discount_account FROM suppliers WHERE creditor_id=" . static::$staticDB->_escape($creditor_id);
      $result = static::$staticDB->_query($sql, "could not get supplier");
      return static::$staticDB->_fetch($result);
    }
    /**
     * @static
     *
     * @param null  $value
     * @param array $options options are<br> (bool)row, (string)cell_params, (int|null)rowspan
     *
     * @return void
     */
    public static function newselect($creditor_id = null, $options = []) {
      $o     = [
        'row'         => true, //
        'cell_params' => [], //
        'rowspan'     => null, //
        'label'       => 'Supplier:', //
        'cells'       => true, //
        'cell_class'  => null
      ];
      $o     = array_merge($o, $options);
      $value = null;
      $focus = false;
      if (!$creditor_id && Input::_hasPost(['creditor', 'creditor_id'])) {
        $value       = $_POST['creditor'];
        $creditor_id = $_POST['creditor_id'];
        JS::_setFocus('stock_id');
      } elseif (!$creditor_id) {
        $creditor_id = Session::_getGlobal('creditor_id');
        $value       = Creditor::get_name($creditor_id);
      }
      if ($creditor_id) {
        $_POST['creditor_id'] = $creditor_id;
      } else {
        JS::_setFocus('creditor');
        $focus = true;
      }
      if ($o['row']) {
        echo '<tr>';
      }
      Forms::hidden('creditor_id');
      UI::search('creditor', [
                             'cells'             => true, //
                             'url'               => 'Creditor', ///
                             'label_cell_params' => ['rowspan' => $o['rowspan'], 'class' => 'nowrap label ' . $o['cell_class']], //
                             'idField'           => 'creditor_id',
                             'label'             => $o['label'], //
                             'name'              => 'creditor', //
                             'input_cell_params' => $o['cell_params'], //
                             'focus'             => $focus, //
                             'value'             => $value,
                             ]
      );
      if ($o['row']) {
        echo "</tr>\n";
      }
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_option
     * @param bool $submit_on_change
     * @param bool $all
     *
     * @internal param bool $editkey
     *
     * @return string
     */
    public static function select($name, $selected_id = null, $spec_option = false, $submit_on_change = false, $all = false) {
      $sql  = "SELECT creditor_id, supp_ref, curr_code, inactive FROM suppliers ";
      $mode = DB_Company::_get_pref('no_supplier_list');
      return Forms::selectBox($name, $selected_id, $sql, 'creditor_id', 'name', array(
                                                                                     'format'        => 'Forms::addCurrFormat',
                                                                                     'order'         => array('supp_ref'),
                                                                                     'search_box'    => $mode != 0,
                                                                                     'type'          => 1,
                                                                                     'spec_option'   => $spec_option === true ? _("All Suppliers") : $spec_option,
                                                                                     'spec_id'       => ALL_TEXT,
                                                                                     'select_submit' => $submit_on_change,
                                                                                     'async'         => false,
                                                                                     'sel_hint'      => $mode ? _('Press Space tab to filter by name fragment') : _('Select supplier'),
                                                                                     'show_inactive' => $all
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
     * @param bool $all
     * @param bool $editkey
     *
     * @return void
     */
    public static function cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false) {
      echo "<td class='label'>";
      if ($label != null) {
        echo "<label for='$name'>$label</label>";
      }
      echo Creditor::select($name, $selected_id, $all_option, $submit_on_change, $all, $editkey);
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
     * @param bool $all
     * @param bool $editkey
     *
     * @return void
     */
    public static function row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false) {
      echo "<tr><td class='label'><label for='$name'>$label</label></td><td>";
      echo Creditor::select($name, $selected_id, $all_option, $submit_on_change, $all, $editkey);
      echo "</td></tr>\n";
    }
    /**
     * @param $db
     */
    public static function setDB($db) {
      static::$staticDB = $db;
    }
  }

  Creditor::setDB(\ADV\Core\DB\DB::i());
