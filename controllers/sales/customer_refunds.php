<?php
  use ADV\Core\JS;
  use ADV\Core\Table;
  use ADV\Core\Ajax;
  use ADV\App\Forms;
  use ADV\App\Display;
  use ADV\App\Dates;
  use ADV\App\Debtor\Debtor;
  use ADV\App\Validation;
  use ADV\Core\Input\Input;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::_openWindow(950, 500);
  JS::_headerFile('/js/payalloc.js');
  Page::start(_($help_context = "Customer Refund Entry"), SA_SALESREFUND, Input::_request('frame'));
  Validation::check(Validation::CUSTOMERS, _("There are no customers defined in the system."));
  Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
  if (!isset($_POST['debtor_id']) && Session::_getGlobal('debtor_id')) {
    $customer = new Debtor(Session::_getGlobal('debtor_id'));
  }
  if (!isset($_POST['DateBanked'])) {
    $_POST['DateBanked'] = Dates::_newDocDate();
    if (!Dates::_isDateInFiscalYear($_POST['DateBanked'])) {
      $_POST['DateBanked'] = Dates::_endFiscalYear();
    }
  }
  if (isset($_GET[ADDED_ID])) {
    $refund_id = $_GET[ADDED_ID];
    Event::success(_("The customer refund has been successfully entered."));
    Display::submenu_print(_("&Print This Receipt"), ST_CUSTREFUND, $refund_id . "-" . ST_CUSTREFUND, 'prtopt');
    Display::link_params("/sales/search/transactions", _("Show Invoices"));
    Display::note(GL_UI::view(ST_CUSTREFUND, $refund_id, _("&View the GL Journal Entries for this Customer Refund")));
    Page::footer_exit();
  }
  // validate inputs
  if (isset($_POST['AddRefundItem'])) {
    if (!Debtor_Payment::can_process(ST_CUSTREFUND)) {
      unset($_POST['AddRefundItem']);
    }
  }
  if (isset($_POST['_DateBanked_changed'])) {
    JS::_setfocus('_DataBanked_changed');
  }
  if (Forms::isListUpdated('debtor_id') || Forms::isListUpdated('bank_account')) {
    $_SESSION['alloc']->read();
    Ajax::_activate('alloc_tbl');
  }
  if (isset($_POST['AddRefundItem'])) {
    $cust_currency = Bank_Currency::for_debtor($_POST['debtor_id']);
    $bank_currency = Bank_Currency::for_company($_POST['bank_account']);
    $comp_currency = Bank_Currency::for_company();
    if ($comp_currency != $bank_currency && $bank_currency != $cust_currency) {
      $rate = 0;
    } else {
      $rate = Validation::input_num('_ex_rate');
    }
    Dates::_newDocDate($_POST['DateBanked']);
    $refund_id                   = Debtor_Refund::add(
      0,
      $_POST['debtor_id'],
      $_POST['branch_id'],
      $_POST['bank_account'],
      $_POST['DateBanked'],
      $_POST['ref'],
      Validation::input_num('amount'),
      Validation::input_num('discount'),
      $_POST['memo_'],
      $rate,
      Validation::input_num('charge')
    );
    $_SESSION['alloc']->trans_no = $refund_id;
    $_SESSION['alloc']->write();
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$refund_id");
  }
  Forms::start();
  Table::startOuter('standard width60 pad5');
  Table::section(1);
  Debtor::newselect();
  if (!isset($_POST['bank_account'])) // first page call
  {
    $_SESSION['alloc'] = new GL_Allocation(ST_CUSTREFUND, 0);
  }
  if (isset($customer) && count($customer->branches) == 0) {
    Validation::check(Validation::BRANCHES, _("No Branches for Customer") . $_POST["debtor_id"], $_POST['debtor_id']);
  } elseif (!isset($_POST['branch_id'])) {
    Debtor_Branch::row(_("Branch:"), $_POST['debtor_id'], 'branch_id', null, false, true, true);
  } else {
    Forms::hidden('branch_id', ANY_NUMERIC);
  }
  Debtor_Payment::read_customer_data($customer->id, true);
  Session::_setGlobal('debtor_id', $customer->id);
  $display_discount_percent = Num::_percentFormat($_POST['payment_discount'] * 100) . "%";
  Table::section(2);
  Bank_Account::row(_("Into Bank Account:"), 'bank_account', null, true);
  Forms::textRow(_("Reference:"), 'ref', null, 20, 40);
  Table::section(3);
  Forms::dateRow(_("Date of Deposit:"), 'DateBanked', '', true, 0, 0, 0, null, true);
  $comp_currency = Bank_Currency::for_company();
  $cust_currency = Bank_Currency::for_debtor($customer->id);
  $bank_currency = Bank_Currency::for_company($_POST['bank_account']);
  if ($cust_currency != $bank_currency) {
    GL_ExchangeRate::display($bank_currency, $cust_currency, $_POST['DateBanked'], ($bank_currency == $comp_currency));
  }
  Forms::AmountRow(_("Bank Charge:"), 'charge');
  Table::endOuter(1);
  if ($cust_currency == $bank_currency) {
    Ajax::_start_div('alloc_tbl');
    GL_Allocation::show_allocatable(true);
    Ajax::_end_div();
  }
  Table::start('padded width60');
  Forms::AmountRow(_("Amount:"), 'amount');
  Forms::textareaRow(_("Memo:"), 'memo_', null, 22, 4);
  Table::end(1);
  if ($cust_currency != $bank_currency) {
    Event::warning(_("Amount and discount are in customer's currency."));
  }
  echo "<br>";
  Forms::submitCenter('AddRefundItem', _("Add Refund"), true, '', 'default');
  echo "<br>";
  Forms::end();
  Page::end(!Input::_request('frame'));
