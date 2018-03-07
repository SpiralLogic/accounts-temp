<?php
  use ADV\App\Voiding;

  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::_openWindow(950, 500);
  Page::start(_($help_context = "Void a Transaction"), SA_VOIDTRANSACTION);
  if (!isset($_POST['date_'])) {
    $_POST['date_'] = Dates::_today();
    if (!Dates::_isDateInFiscalYear($_POST['date_'])) {
      $_POST['date_'] = Dates::_endFiscalYear();
    }
  }
  if (isset($_POST['ConfirmVoiding'])) {
    if ($_SESSION['voiding'] != $_POST['trans_no'] . $_POST['filterType']) {
      unset($_POST['ConfirmVoiding']);
      $_POST['ProcessVoiding'] = true;
    } else {
      handle_void_transaction();
    }
    Ajax::_activate('_page_body');
  }
  if (isset($_POST['ProcessVoiding'])) {
    if (!check_valid_entries()) {
      unset($_POST['ProcessVoiding']);
    }
    Ajax::_activate('_page_body');
  }
  if (isset($_POST['CancelVoiding'])) {
    Ajax::_activate('_page_body');
  }
  voiding_controls();
  Page::end();
  /**
   * @param $type
   * @param $type_no
   *
   * @return bool
   */
  function exist_transaction($type, $type_no) {
    $void_entry = Voiding::has($type, $type_no);
    if ($void_entry > 0) {
      return false;
    }
    switch ($type) {
      case ST_JOURNAL : // it's a journal entry
        if (!GL_Trans::exists($type, $type_no)) {
          return false;
        }
        break;
      case ST_BANKPAYMENT : // it's a payment
      case ST_BANKDEPOSIT : // it's a deposit
      case ST_BANKTRANSFER : // it's a transfer
        if (!Bank_Trans::exists($type, $type_no)) {
          return false;
        }
        break;
      case ST_SALESINVOICE : // it's a customer invoice
      case ST_CUSTCREDIT : // it's a customer credit note
      case ST_CUSTPAYMENT : // it's a customer payment
      case ST_CUSTREFUND : // it's a customer refund
      case ST_CUSTDELIVERY : // it's a customer dispatch
        if (!Debtor_Trans::exists($type, $type_no)) {
          return false;
        }
        break;
      case ST_LOCTRANSFER : // it's a stock transfer
        if (Inv_Transfer::get_items($type_no) == null) {
          return false;
        }
        break;
      case ST_INVADJUST : // it's a stock adjustment
        if (Inv_Adjustment::get($type_no) == null) {
          return false;
        }
        break;
      case ST_PURCHORDER : // it's a PO
      case ST_SUPPRECEIVE : // it's a GRN
        return false;
      case ST_SUPPINVOICE : // it's a suppler invoice
      case ST_SUPPCREDIT : // it's a supplier credit note
      case ST_SUPPAYMENT : // it's a supplier payment
        if (!Creditor_Trans::exists($type, $type_no)) {
          return false;
        }
        break;
      case ST_WORKORDER : // it's a work order
        if (!WO::get($type_no, true)) {
          return false;
        }
        break;
      case ST_MANUISSUE : // it's a work order issue
        if (!WO_Issue::exists($type_no)) {
          return false;
        }
        break;
      case ST_MANURECEIVE : // it's a work order production
        if (!WO_Produce::exists($type_no)) {
          return false;
        }
        break;
      case ST_SALESORDER: // it's a sales order
      case ST_SALESQUOTE: // it's a sales quotation
        return false;
      case ST_COSTUPDATE : // it's a stock cost update
        return false;
        break;
    }
    return true;
  }

  /** **/
  function voiding_controls() {
    Forms::start();
    Table::start('standard');
    if (REQUEST_GET) {
      $_POST['trans_no']   = Input::_get('trans_no');
      $_POST['filterType'] = Input::_get('type');
      $_POST['memo_']      = Input::_get('memo');
    }
    SysTypes::row(_("Transaction Type:"), "filterType", null, true);
    Forms::textRow(_("Transaction #:"), 'trans_no', null, 12, 12);
    Forms::dateRow(_("Voiding Date:"), 'date_');
    Forms::textareaRow(_("Memo:"), 'memo_', null, 30, 4);
    Table::end(1);
    if (!isset($_POST['ProcessVoiding'])) {
      Forms::submitCenter('ProcessVoiding', _("Void Transaction"), true, '', 'default');
    } else {
      if (!exist_transaction($_POST['filterType'], $_POST['trans_no'])) {
        Event::error(_("The entered transaction does not exist or cannot be voided."));
        unset($_POST['trans_no'], $_POST['memo_'], $_POST['date_']);
        Forms::submitCenter('ProcessVoiding', _("Void Transaction"), true, '', 'default');
      } else {
        Event::warning(_("Are you sure you want to void this transaction ? This action cannot be undone."), 0, 1);
        $_SESSION['voiding'] = $_POST['trans_no'] . $_POST['filterType'];
        if ($_POST['filterType'] == ST_JOURNAL) // GL transaction are not included in get_viewTrans_str
        {
          $view_str = GL_UI::view($_POST['filterType'], $_POST['trans_no'], _("View Transaction"));
        } else {
          $view_str = GL_UI::viewTrans($_POST['filterType'], $_POST['trans_no'], _("View Transaction"));
        }
        echo "<div class='center pad5'><span class='redborder  bold font15 pad10'>$view_str</span></div>";
        echo "<br>";
        Forms::submitCenterBegin('ConfirmVoiding', _("Proceed"), '', true);
        Forms::submitCenterEnd('CancelVoiding', _("Cancel"), '', 'cancel');
      }
    }
    Forms::end();
  }

  /**
   * @return bool
   */
  function check_valid_entries() {
    if (DB_AuditTrail::is_closed_trans($_POST['filterType'], $_POST['trans_no'])) {
      Event::error(_("The selected transaction was closed for edition and cannot be voided."));
      JS::_setFocus('trans_no');
      return false;
    }
    if (!Dates::_isDate($_POST['date_'])) {
      Event::error(_("The entered date is invalid."));
      JS::_setFocus('date_');
      return false;
    }
    /*if (!Dates::_isDateInFiscalYear($_POST['date_'])) {
      Event::error(_("The entered date is not in fiscal year."));
      JS::_setFocus('date_');

      return false;
    }*/
    if (!is_numeric($_POST['trans_no']) OR $_POST['trans_no'] <= 0) {
      Event::error(_("The transaction number is expected to be numeric and greater than zero."));
      JS::_setFocus('trans_no');
      return false;
    }
    return true;
  }

  /**
   * @return mixed
   */
  function handle_void_transaction() {
    if (check_valid_entries() == true) {
      unset($_SESSION['voiding']);
      $void_entry = Voiding::get($_POST['filterType'], $_POST['trans_no']);
      $error      = false;
      if ($_POST['filterType'] == ST_SALESINVOICE && !User::_i()->hasAccess(SA_VOIDINVOICE)) {
        $error = _("You don't not have permissions required to delete this transaction.");
      } elseif ($void_entry != null) {
        $error = _("The selected transaction has already been voided.");
      } else {
        $ret = Voiding::void($_POST['filterType'], $_POST['trans_no'], $_POST['date_'], $_POST['memo_']);
        if (!$ret) {
          $error = _("The entered transaction does not exist or cannot be voided.");
        }
      }
      if (!$error) {
        Event::success(_("Selected transaction has been voided."));
      } else {
        Event::error($error);
        unset($_POST['trans_no'], $_POST['memo_'], $_POST['date_']);
        JS::_setFocus('trans_no');
      }
    }
  }

