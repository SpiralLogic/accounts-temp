<?php
  namespace ADV\Controllers\Sales;

  use GL_Account;
  use ADV\App\Debtor\Debtor;
  use DB_Company;
  use GL_ExchangeRate;
  use Debtor_Branch;
  use ADV\Core\Num;
  use GL_UI;
  use ADV\Core\Event;
  use Debtor_Payment;
  use Bank_Currency;
  use Bank_Account;
  use GL_Allocation;
  use Sales_Branch;
  use ADV\App\Ref;
  use ADV\App\Display;
  use ADV\App\Validation;
  use ADV\App\Dates;
  use ADV\App\Forms;
  use ADV\Core\Input\Input;
  use ADV\Core\Table;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Payment extends \ADV\App\Controller\Action
  {
    public $date_banked;
    public $debtor_id;
    protected $security = SA_SALESPAYMNT;
    protected function before() {
      if (REQUEST_GET && $this->Input->get('account', 'amount', 'memo', 'date')) {
        $_POST['bank_acount'] = $this->Input->get('account');
        $_POST['amount']      = $this->Input->get('amount');
        $_POST['memo_']       = $this->Input->get('memo');
        $_POST['DateBanked']  = $this->Input->get('date');
        $_POST['charge']      = $this->Input->get('fee', Input::NUMERIC);
      }
      $this->JS->openWindow(900, 500);
      $this->JS->footerFile('/js/payalloc.js');
      $this->debtor_id = & $this->Input->postGetGlobal('debtor_id');
      if (Forms::isListUpdated('branch_id') || !$_POST['debtor_id']) {
        $br              = Sales_Branch::get($this->Input->post('branch_id'));
        $this->debtor_id = $br['debtor_id'];
        $this->Ajax->activate('debtor_id');
      }
      $this->Session->setGlobal('debtor_id', $this->debtor_id);
      $this->date_banked = & $this->Input->post('DateBanked', null, Dates::_newDocDate());
      if (!Dates::_isDateInFiscalYear($this->date_banked)) {
        $this->date_banked = Dates::_endFiscalYear();
      }
      // validate inputs
      if (isset($_POST['_debtor_id_button'])) {
        $this->Ajax->activate('branch_id');
      }
      if (isset($_POST['_DateBanked_changed'])) {
        $this->Ajax->activate('_ex_rate');
      }
      if ($this->Input->hasPost('debtor_id') || Forms::isListUpdated('bank_account')) {
        $this->Ajax->activate('_page_body');
      }
      if (!isset($_POST['bank_account'])) // first page call
      {
        $_SESSION['alloc'] = new GL_Allocation(ST_CUSTPAYMENT, 0);
      }
      if (!Forms::isListUpdated('bank_account') && !isset($_POST['bank_account'])) {
        $_POST['bank_account'] = Bank_Account::get_customer_default($this->debtor_id);
      }
      $this->setTitle("Customer Payment Entry");
    }
    /**
     * @return bool
     */
    protected function addPaymentItem() {
      if (!$this->can_process(ST_CUSTPAYMENT)) {
        return false;
      }
      $cust_currency = Bank_Currency::for_debtor($this->debtor_id);
      $bank_currency = Bank_Currency::for_company($_POST['bank_account']);
      $comp_currency = Bank_Currency::for_company();
      if ($comp_currency != $bank_currency && $bank_currency != $cust_currency) {
        $rate = 0;
      } else {
        $rate = Validation::input_num('_ex_rate');
      }
      if ($this->Input->hasPost('createinvoice')) {
        GL_Allocation::create_miscorder(
          new Debtor($this->debtor_id), $_POST['branch_id'], $this->date_banked, $_POST['memo_'], $_POST['ref'], Validation::input_num('amount'), Validation::input_num('discount')
        );
      }
      $payment_no = Debtor_Payment::add(
        0, $this->debtor_id, $_POST['branch_id'], $_POST['bank_account'], $this->date_banked, $_POST['ref'], Validation::input_num('amount'), Validation::input_num('discount'), $_POST['memo_'], $rate, Validation::input_num('charge')
      );
      if (!$payment_no) {
        return false;
      }
      $_SESSION['alloc']->trans_no = $payment_no;
      $_SESSION['alloc']->write();
      Event::success(_("The customer payment has been successfully entered."));
      Display::submenu_print(_("&Print This Receipt"), ST_CUSTPAYMENT, $payment_no . "-" . ST_CUSTPAYMENT, 'prtopt');
      Display::link_params("/sales/search/transactions", _("Show Invoices"));
      GL_UI::view(ST_CUSTPAYMENT, $payment_no, _("&View the GL Journal Entries for this Customer Payment"));
      //	Display::link_params( "/sales/allocations/customer_allocate.php", _("&Allocate this Customer Payment"), "trans_no=$payment_no&trans_type=12");
      Display::link_params("/sales/payment", _("Enter Another &Customer Payment"));
      $this->Ajax->activate('_page_body');
      $this->Page->endExit();
      return true;
    }
    protected function index() {
      $this->runAction();
      Forms::start();
      Table::startOuter('standard width90 pad2');
      Table::section(1);
      Debtor::newselect();
      Forms::refRow(_("Reference:"), 'ref', null, Ref::get_next(ST_CUSTPAYMENT));
      Debtor_Payment::read_customer_data($this->debtor_id);
      $display_discount_percent = Num::_percentFormat($_POST['payment_discount'] * 100) . "%";
      Table::section(2);
      Debtor_Branch::row(_("Branch:"), $this->debtor_id, 'branch_id', null, false, true, true);
      Bank_Account::row(_("Into Bank Account:"), 'bank_account', null, true);
      Table::section(3);
      Forms::dateRow(_("Date of Deposit:"), 'DateBanked', '', true, 0, 0, 0, null, true);
      $comp_currency = Bank_Currency::for_company();
      $cust_currency = Bank_Currency::for_debtor($this->debtor_id);
      $bank_currency = Bank_Currency::for_company($_POST['bank_account']);
      if ($cust_currency != $bank_currency) {
        GL_ExchangeRate::display($bank_currency, $cust_currency, $this->date_banked, ($bank_currency == $comp_currency));
      }
      Forms::AmountRow(_("Bank Charge:"), 'charge', 0, null, ['$']);
      Table::endOuter(1);
      $this->Ajax->start_div('alloc_tbl');
      if ($cust_currency == $bank_currency) {
        $_SESSION['alloc']->read();
        GL_Allocation::show_allocatable(false);
      }
      $this->Ajax->end_div();
      Table::start('padded width70');
      Table::label(_("Customer prompt payment discount :"), $display_discount_percent);
      Forms::AmountRow(_("Amount of Discount:"), 'discount', 0, null, ['$']);
      if ($this->User->hasAccess(SS_SALES) && !$this->Input->post('TotalNumberOfAllocs')) {
        //    Forms::checkRow(_("Create invoice and apply for this payment: "), 'createinvoice');
      }
      Forms::AmountRow(_("Amount:"), 'amount', null, null, ['$']);
      Forms::textareaRow(_("Memo:"), 'memo_', null, 22, 4);
      Table::end(1);
      if ($cust_currency != $bank_currency) {
        Event::warning(_("Amount and discount are in customer's currency."));
      }
      Forms::submitCenter(FORM_ACTION, 'addPaymentItem', true, 'Add Payment', 'default');
      Forms::end();
    }
    /**
     * @internal param $prefix
     * @return bool|mixed
     */
    protected function runValidation() {
      Validation::check(Validation::CUSTOMERS, _("There are no customers defined in the system."));
      Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
      if ($this->debtor_id) {
        Validation::check(Validation::BRANCHES, _("No Branches for Customer") . $this->debtor_id, $this->debtor_id);
      }
    }
    /**
     * @param $type
     *
     * @return bool
     */
    protected function can_process($type) {
      if (!$this->Input->post('debtor_id')) {
        Event::error(_("There is no customer selected."));
        $this->JS->setFocus('debtor_id');
        return false;
      }
      if (!$this->Input->post('branch_id')) {
        Event::error(_("This customer has no branch defined."));
        $this->JS->setFocus('branch_id');
        return false;
      }
      if (!isset($_POST['DateBanked']) || !Dates::_isDate($_POST['DateBanked'])) {
        Event::error(_("The entered date is invalid. Please enter a valid date for the payment."));
        $this->JS->setFocus('DateBanked');
        return false;
      } elseif (!Dates::_isDateInFiscalYear($_POST['DateBanked'])) {
        Event::error(_("The entered date is not in fiscal year."));
        $this->JS->setFocus('DateBanked');
        return false;
      }
      if (!Ref::is_valid($_POST['ref'])) {
        Event::error(_("You must enter a reference."));
        $this->JS->setFocus('ref');
        return false;
      }
      if (!Ref::is_new($_POST['ref'], $type)) {
        $_POST['ref'] = Ref::get_next($type);
      }
      if (!Validation::post_num('amount', 0)) {
        Event::error(_("The entered amount is invalid or negative and cannot be processed."));
        $this->JS->setFocus('amount');
        return false;
      }
      if (isset($_POST['charge']) && !Validation::post_num('charge', 0)) {
        Event::error(_("The entered amount is invalid or negative and cannot be processed."));
        $this->JS->setFocus('charge');
        return false;
      }
      if (isset($_POST['charge']) && Validation::input_num('charge') > 0) {
        $charge_acct = DB_Company::_get_pref('bank_charge_act');
        if (GL_Account::get($charge_acct) == false) {
          Event::error(_("The Bank Charge Account has not been set in System and General GL Setup."));
          $this->JS->setFocus('charge');
          return false;
        }
      }
      if (isset($_POST['_ex_rate']) && !Validation::post_num('_ex_rate', 0.000001)) {
        Event::error(_("The exchange rate must be numeric and greater than zero."));
        $this->JS->setFocus('_ex_rate');
        return false;
      }
      if ($_POST['discount'] == "") {
        $_POST['discount'] = 0;
      }
      if (!Validation::post_num('discount')) {
        Event::error(_("The entered discount is not a valid number."));
        $this->JS->setFocus('discount');
        return false;
      }
      if ($type == ST_CUSTPAYMENT && !$this->User->salesmanid) {
        Event::error(_("You do not have a salesman id, this is needed to create an invoice."));
        return false;
      }
      // if ($type == ST_CUSTPAYMENT &&(Validation::input_num('amount') - Validation::input_num('discount') < 0)) {
      if ($type == ST_CUSTPAYMENT && Validation::input_num('discount', 0, 0) < 0) {
        Event::error(_("The balance of the amount and discount is zero or negative. Please enter valid amounts."));
        $this->JS->setFocus('discount');
        return false;
      }
      if ($type == ST_CUSTREFUND && Validation::input_num('amount') >= 0) {
        Event::error(_("The balance of the amount and discount is zero or positive. Please enter valid amounts."));
        $this->JS->setfocus('[name="amount"]');
        return false;
      }
      $_SESSION['alloc']->amount = Validation::input_num('amount');
      if (isset($_POST["TotalNumberOfAllocs"])) {
        return GL_Allocation::check();
      }
      return true;
    }
  }

