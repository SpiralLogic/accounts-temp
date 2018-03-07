<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\Purchases;

  use ADV\App\Creditor\Creditor;
  use GL_UI;
  use ADV\Core\HTML;
  use ADV\App\Validation;
  use Creditor_Payment;
  use ADV\Core\Event;
  use ADV\App\Display;
  use GL_ExchangeRate;
  use ADV\App\Ref;
  use Bank_Account;
  use Bank_Currency;
  use ADV\App\Forms;
  use ADV\App\Dates;
  use GL_Allocation;
  use ADV\Core\Table;
  use ADV\Core\JS;

  /** **/
  class Payment extends \ADV\App\Controller\Action
  {
    protected $supplier_currency;
    protected $bank_currency;
    protected $company_currency;
    protected $creditor_id;
    protected $security = SA_SUPPLIERPAYMNT;
    public $bank_account;
    protected function before() {
      JS::_openWindow(950, 500);
      JS::_footerFile('/js/payalloc.js');
      if (REQUEST_GET) {
        if ($this->Input->hasGet('account', 'amount', 'memo', 'date')) {
          $_POST['bank_account'] = $this->Input->get('account');
          $_POST['amount']       = abs($this->Input->get('amount'));
          $_POST['memo_']        = $this->Input->get('memo');
          $_POST['date_']        = $this->Input->get('date');
        }
      }
      $this->creditor_id = & $this->Input->postGetGlobal('creditor_id', null, -1);
      $this->Session->setGlobal('creditor_id', $this->creditor_id);
      if (!$this->bank_account) // first page call
      {
        $_SESSION['alloc'] = new GL_Allocation(ST_SUPPAYMENT, 0);
      }
      $this->bank_account = & $this->Input->postGetGlobal('bank_account', null, -1);
      if (!isset($_POST['date_'])) {
        $_POST['date_'] = Dates::_newDocDate();
        if (!Dates::_isDateInFiscalYear($_POST['date_'])) {
          $_POST['date_'] = Dates::_endFiscalYear();
        }
      }
      if (isset($_POST['_date__changed'])) {
        $this->Ajax->activate('_ex_rate');
      }
      if ($this->Input->post(FORM_CONTROL) == 'creditor' || Forms::isListUpdated('bank_account')) {
        $_SESSION['alloc']->read();
        $this->Ajax->activate('alloc_tbl');
      }
      $this->company_currency  = Bank_Currency::for_company();
      $this->supplier_currency = Bank_Currency::for_creditor($this->creditor_id);
      $this->bank_currency     = Bank_Currency::for_company($_POST['bank_account']);
      $this->setTitle("Supplier Payment Entry");
    }
    protected function index() {
      if (isset($_POST['ProcessSuppPayment'])) {
        $this->processSupplierPayment();
      }
      Forms::start();
      Table::startOuter('standard width80 pad5');
      Table::section(1);
      Creditor::newselect();
      Bank_Account::row(_("Bank Account:"), 'bank_account', null, true);
      Table::section(2);
      Forms::refRow(_("Reference:"), 'ref', '', Ref::get_next(ST_SUPPAYMENT));
      Forms::dateRow(_("Date Paid") . ":", 'date_', '', true, 0, 0, 0, null, true);
      Table::section(3);
      if ($this->bank_currency != $this->supplier_currency) {
        GL_ExchangeRate::display($this->bank_currency, $this->supplier_currency, $_POST['date_'], true);
      }
      Forms::AmountRow(_("Bank Charge:"), 'charge');
      Table::endOuter(1); // outer table
      $this->Ajax->start_div('alloc_tbl');
      if ($this->bank_currency == $this->supplier_currency) {
        $_SESSION['alloc']->read();
        GL_Allocation::show_allocatable(false);
      }
      $this->Ajax->end_div();
      Table::start('padded width60');
      Forms::AmountRow(_("Amount of Discount:"), 'discount');
      Forms::AmountRow(_("Amount of Payment:"), 'amount');
      Forms::textareaRow(_("Memo:"), 'memo_', null, 22, 4);
      Table::end(1);
      if ($this->bank_currency != $this->supplier_currency) {
        Event::warning("The amount and discount are in the bank account's currency.");
      }
      Forms::submitCenter('ProcessSuppPayment', _("Enter Payment"), true, '', 'default');
      Forms::end();
    }
    /**
     * @return bool
     */
    protected function processSupplierPayment() {
      if (!Creditor_Payment::can_process()) {
        return false;
      }
      if ($this->company_currency != $this->bank_currency && $this->bank_currency != $this->supplier_currency) {
        $rate = 0;
      } else {
        $rate = Validation::input_num('_ex_rate');
      }
      $payment_id = Creditor_Payment::add(
        $this->creditor_id, $_POST['date_'], $_POST['bank_account'], Validation::input_num('amount'), Validation::input_num('discount'), $_POST['ref'], $_POST['memo_'], $rate, Validation::input_num('charge')
      );
      if (!$payment_id) {
        return false;
      }
      Dates::_newDocDate($_POST['date_']);
      $_SESSION['alloc']->trans_no = $payment_id;
      $_SESSION['alloc']->write();
      //unset($this->creditor_id);
      unset($_POST['bank_account'], $_POST['date_'], $_POST['currency'], $_POST['memo_'], $_POST['amount'], $_POST['discount'], $_POST['ProcessSuppPayment']);
      Event::success(_("Payment has been sucessfully entered"));
      Display::submenu_print(_("&Print This Remittance"), ST_SUPPAYMENT, $payment_id . "-" . ST_SUPPAYMENT, 'prtopt');
      Display::submenu_print(_("&Email This Remittance"), ST_SUPPAYMENT, $payment_id . "-" . ST_SUPPAYMENT, null, 1);
      Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter Another Invoice"), "New=1", true, 'class="button"');
      echo
      HTML::br();
      Display::note(GL_UI::view(ST_SUPPAYMENT, $payment_id, _("View the GL &Journal Entries for this Payment"), false, 'button'));
      // Display::link_params($path_to_root . "/purchases/allocations/supplier_allocate.php", _("&Allocate this Payment"), "trans_no=$payment_id&trans_type=22");
      Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter another supplier &payment"), "creditor_id=" . $this->creditor_id, true, 'class="button"');
      $this->Ajax->activate('_page_body');
      $this->Page->endExit();
      return true;
    }
    protected function runValidation() {
      Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
      Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
    }
  }

