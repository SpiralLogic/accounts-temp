<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  use ADV\Core\JS;
  use ADV\Core\Input\Input;
  use ADV\App\Dates;
  use ADV\App\Forms;
  use ADV\Core\Cell;
  use ADV\App\Display;
  use ADV\Core\Table;

  JS::_openWindow(950, 600);
  $trans_type = $_GET['trans_type'];
  Page::start("", SA_SALESTRANSVIEW, true);
  if (isset($_GET["trans_no"])) {
    $trans_id = $_GET["trans_no"];
  }
  if (isset($_POST)) {
    unset($_POST);
  }
  $receipt = Debtor_Trans::get($trans_id, $trans_type);
  Table::start('standard width90');
  echo "<tr class='tablerowhead top'><th colspan=6>";
  if ($trans_type == ST_CUSTPAYMENT) {
    Display::heading(sprintf(_("Customer Payment #%d"), $trans_id));
  } else {
    Display::heading(sprintf(_("Customer Refund #%d"), $trans_id));
  }
  echo "</td></tr>";
  echo '<tr>';
  Cell::labelled(_("From Customer"), $receipt['DebtorName']);
  Cell::labelled(_("Into Bank Account"), $receipt['bank_account_name']);
  Cell::labelled(_("Date of Deposit"), Dates::_sqlToDate($receipt['tran_date']));
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("Payment Currency"), $receipt['curr_code']);
  Cell::labelled(_("Amount"), Num::_priceFormat($receipt['Total'] - $receipt['ov_discount']));
  Cell::labelled(_("Discount"), Num::_priceFormat($receipt['ov_discount']));
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("Payment Type"), Bank_Trans::$types[$receipt['BankTransType']]);
  Cell::labelled(_("Reference"), $receipt['reference'], 'class="label" colspan=1');
  Forms::end();
  echo '</tr>';
  DB_Comments::display_row($trans_type, $trans_id);
  Table::end(1);
  $voided = Voiding::is_voided($trans_type, $trans_id, _("This customer payment has been voided."));
  if (!$voided && ($trans_type != ST_CUSTREFUND)) {
    GL_Allocation::from(PT_CUSTOMER, $receipt['debtor_id'], ST_CUSTPAYMENT, $trans_id, $receipt['Total']);
  }
  if (Input::_get('frame')) {
    return;
  }
  Display::submenu_print(_("&Print This Receipt"), $trans_type, $_GET['trans_no'], 'prtopt');
  Page::end();

