<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  if (REQUEST_GET) {
    if (Input::_get('account')) {
      $account         = Input::_get('amount') > 0 ? 'ToBankAccount' : 'FromBankAccount';
      $_POST[$account] = Input::_get('account');
    }
    if (Input::_get('amount')) {
      $_POST['amount'] = abs(Input::_get('amount'));
    }
    if (Input::_get('memo')) {
      $_POST['memo_'] = Input::_get('memo');
    }
    if (Input::_get('date')) {
      $_POST['date_'] = Input::_get('date');
    }
  }
  JS::_openWindow(950, 500);
  Page::start(_($help_context = "Transfer between Bank Accounts"), SA_BANKTRANSFER);
  Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
  if (isset($_GET[ADDED_ID])) {
    $trans_no   = $_GET[ADDED_ID];
    $trans_type = ST_BANKTRANSFER;
    Event::success(_("Transfer has been entered"));
    Display::note(GL_UI::view($trans_type, $trans_no, _("&View the GL Journal Entries for this Transfer")));
    Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter & Another Transfer"));
    Page::footer_exit();
  }
  if (isset($_POST['_date__changed'])) {
    Ajax::_activate('_ex_rate');
  }
  if (isset($_POST['AddPayment'])) {
    if (check_valid_entries() == true) {
      handle_add_deposit();
    }
  }
  gl_payment_controls();
  Page::end();
  function gl_payment_controls() {
    $home_currency = Bank_Currency::for_company();
    Forms::start();
    Table::startOuter('standard');
    Table::section(1);
    Bank_Account::row(_("From Account:"), 'FromBankAccount', null, true);
    Bank_Account::row(_("To Account:"), 'ToBankAccount', null, true);
    Forms::dateRow(_("Transfer Date:"), 'date_', '', null, 0, 0, 0, null, true);
    $from_currency = Bank_Currency::for_company($_POST['FromBankAccount']);
    $to_currency   = Bank_Currency::for_company($_POST['ToBankAccount']);
    if ($from_currency != "" && $to_currency != "" && $from_currency != $to_currency) {
      Forms::AmountRow(_("Amount:"), 'amount', null, null, $from_currency);
      Forms::AmountRow(_("Bank Charge:"), 'charge', null, null, $from_currency);
      GL_ExchangeRate::display($from_currency, $to_currency, $_POST['date_']);
    } else {
      Forms::AmountRow(_("Amount:"), 'amount');
      Forms::AmountRow(_("Bank Charge:"), 'charge');
    }
    Table::section(2);
    Forms::refRow(_("Reference:"), 'ref', '', Ref::get_next(ST_BANKTRANSFER));
    Forms::textareaRow(_("Memo:"), 'memo_', null, 40, 4);
    Table::endOuter(1); // outer table
    Forms::submitCenter('AddPayment', _("Enter Transfer"), true, '', 'default');
    Forms::end();
  }

  /**
   * @return bool
   */
  function check_valid_entries() {
    if (!Dates::_isDate($_POST['date_'])) {
      Event::error(_("The entered date is invalid ."));
      JS::_setFocus('date_');
      return false;
    }
    if (!Dates::_isDateInFiscalYear($_POST['date_'])) {
      Event::error(_("The entered date is not in fiscal year . "));
      JS::_setFocus('date_');
      return false;
    }
    if (!Validation::post_num('amount', 0)) {
      Event::error(_("The entered amount is invalid or less than zero ."));
      JS::_setFocus('amount');
      return false;
    }
    if (isset($_POST['charge']) && !Validation::post_num('charge', 0)) {
      Event::error(_("The entered amount is invalid or less than zero ."));
      JS::_setFocus('charge');
      return false;
    }
    if (isset($_POST['charge']) && Validation::input_num('charge') > 0 && DB_Company::_get_pref('bank_charge_act') == '') {
      Event::error(_("The Bank Charge Account has not been set in System and General GL Setup ."));
      JS::_setFocus('charge');
      return false;
    }
    if (!Ref::is_valid($_POST['ref'])) {
      Event::error(_("You must enter a reference ."));
      JS::_setFocus('ref');
      return false;
    }
    if (!Ref::is_new($_POST['ref'], ST_BANKTRANSFER)) {
      $_POST['ref'] = Ref::get_next(ST_BANKTRANSFER);
    }
    if ($_POST['FromBankAccount'] == $_POST['ToBankAccount']) {
      Event::error(_("The source and destination bank accouts cannot be the same ."));
      JS::_setFocus('ToBankAccount');
      return false;
    }
    return true;
  }

  function handle_add_deposit() {
    $trans_no = GL_Bank::add_bank_transfer(
      $_POST['FromBankAccount'], //
      $_POST['ToBankAccount'], //
      $_POST['date_'], //
      Validation::input_num('amount'), //
      $_POST['ref'], //
      $_POST['memo_'], //
      Validation::input_num('charge')
    );
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$trans_no");
  }
