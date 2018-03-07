<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Bank Accounts"), SA_BANKACCOUNT);
  list($Mode, $selected_id) = Page::simple_mode();
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    //initialise no input errors assumed initially before we test
    $input_error = 0;
    //first off validate inputs sensible
    if (strlen($_POST['bank_account_name']) == 0) {
      $input_error = 1;
      Event::error(_("The bank account name cannot be empty."));
      JS::_setFocus('bank_account_name');
    }
    if ($input_error != 1) {
      if ($selected_id != -1) {
        Bank_Account::update(
          $selected_id,
          $_POST['account_code'],
          $_POST['account_type'],
          $_POST['bank_account_name'],
          $_POST['bank_name'],
          $_POST['bank_account_number'],
          $_POST['bank_address'],
          $_POST['BankAccountCurrency'],
          $_POST['dflt_curr_act']
        );
        Event::success(_('Bank account has been updated'));
      } else {
        Bank_Account::add(
          $_POST['account_code'],
          $_POST['account_type'],
          $_POST['bank_account_name'],
          $_POST['bank_name'],
          $_POST['bank_account_number'],
          $_POST['bank_address'],
          $_POST['BankAccountCurrency'],
          $_POST['dflt_curr_act']
        );
        Event::success(_('New bank account has been added'));
      }
      $Mode = MODE_RESET;
    }
  } elseif ($Mode == MODE_DELETE) {
    //the link to delete a selected record was clicked instead of the submit button
    $cancel_delete = 0;
    $acc           = DB::_escape($selected_id);
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'bank_trans'
    $sql    = "SELECT COUNT(*) FROM bank_trans WHERE bank_act=$acc";
    $result = DB::_query($sql, "check failed");
    $myrow  = DB::_fetchRow($result);
    if ($myrow[0] > 0) {
      $cancel_delete = 1;
      Event::error(_("Cannot delete this bank account because transactions have been created using this account."));
    }
    $sql    = "SELECT COUNT(*) FROM sales_pos WHERE pos_account=$acc";
    $result = DB::_query($sql, "check failed");
    $myrow  = DB::_fetchRow($result);
    if ($myrow[0] > 0) {
      $cancel_delete = 1;
      Event::error(_("Cannot delete this bank account because POS definitions have been created using this account."));
    }
    if (!$cancel_delete) {
      Bank_Account::delete($selected_id);
      Event::notice(_('Selected bank account has been deleted'));
    } //end if Delete bank account
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id                  = -1;
    $_POST['bank_name']           = $_POST['bank_account_name'] = '';
    $_POST['bank_account_number'] = $_POST['bank_address'] = '';
  }
  /* Always show the list of accounts */
  $sql
    = "SELECT account.*, gl_account.account_name
    FROM bank_accounts account, chart_master gl_account
    WHERE account.account_code = gl_account.account_code";
  if (!Input::_hasPost('show_inactive')) {
    $sql .= " AND !account.inactive";
  }
  $sql .= " ORDER BY account_code, bank_curr_code";
  $result = DB::_query($sql, "could not get bank accounts");
  Forms::start();
  Table::start('padded grid width80');
  $th = array(
    _("Account Name"),
    _("Type"),
    _("Currency"),
    _("GL Account"),
    _("Bank"),
    _("Number"),
    _("Bank Address"),
    _("Dflt"),
    '',
    ''
  );
  Forms::inactiveControlCol($th);
  Table::header($th);
  $k                  = 0;
  $bank_account_types = Bank_Account::$types;
  while ($myrow = DB::_fetch($result)) {
    Cell::label($myrow["bank_account_name"], ' class="nowrap"');
    Cell::label($bank_account_types[$myrow["account_type"]], ' class="nowrap"');
    Cell::label($myrow["bank_curr_code"], ' class="nowrap"');
    Cell::label($myrow["account_code"] . " " . $myrow["account_name"], ' class="nowrap"');
    Cell::label($myrow["bank_name"], ' class="nowrap"');
    Cell::label($myrow["bank_account_number"], ' class="nowrap"');
    Cell::label($myrow["bank_address"]);
    if ($myrow["dflt_curr_act"]) {
      Cell::label(_("Yes"));
    } else {
      Cell::label(_("No"));
    }
    Forms::inactiveControlCell($myrow["id"], $myrow["inactive"], 'bank_accounts', 'id');
    Forms::buttonEditCell("Edit" . $myrow["id"], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow["id"], _("Delete"));
    echo '</tr>';
  }
  Forms::inactiveControlRow($th);
  Table::end(1);
  $is_editing = $selected_id != -1;
  Table::start('standard');
  if ($is_editing) {
    if ($Mode == MODE_EDIT) {
      $myrow                        = Bank_Account::get($selected_id);
      $_POST['account_code']        = $myrow["account_code"];
      $_POST['account_type']        = $myrow["account_type"];
      $_POST['bank_name']           = $myrow["bank_name"];
      $_POST['bank_account_name']   = $myrow["bank_account_name"];
      $_POST['bank_account_number'] = $myrow["bank_account_number"];
      $_POST['bank_address']        = $myrow["bank_address"];
      $_POST['BankAccountCurrency'] = $myrow["bank_curr_code"];
      $_POST['dflt_curr_act']       = $myrow["dflt_curr_act"];
    }
    Forms::hidden('selected_id', $selected_id);
    Forms::hidden('account_code');
    Forms::hidden('account_type');
    Forms::hidden('BankAccountCurrency', $_POST['BankAccountCurrency']);
    JS::_setFocus('bank_account_name');
  }
  Forms::textRow(_("Bank Account Name:"), 'bank_account_name', null, 50, 100);
  if ($is_editing) {
    $bank_account_types = Bank_Account::$types;
    Table::label(_("Account Type:"), $bank_account_types[$_POST['account_type']]);
  } else {
    Bank_Account::type_row(_("Account Type:"), 'account_type', null);
  }
  if ($is_editing) {
    Table::label(_("Bank Account Currency:"), $_POST['BankAccountCurrency']);
  } else {
    GL_Currency::row(_("Bank Account Currency:"), 'BankAccountCurrency', null);
  }
  Forms::yesnoListRow(_("Default currency account:"), 'dflt_curr_act');
  if ($is_editing) {
    Table::label(_("Bank Account GL Code:"), $_POST['account_code']);
  } else {
    GL_UI::all_row(_("Bank Account GL Code:"), 'account_code', null);
  }
  Forms::textRow(_("Bank Name:"), 'bank_name', null, 50, 60);
  Forms::textRow(_("Bank Account Number:"), 'bank_account_number', null, 30, 60);
  Forms::textareaRow(_("Bank Address:"), 'bank_address', null, 40, 5);
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();
