<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::_openWindow(950, 500);
  Page::start('', SA_SUPPTRANSVIEW, true);
  if (isset($_GET["trans_no"])) {
    $trans_no = $_GET["trans_no"];
  }
  $receipt           = Creditor_Trans::get($trans_no, ST_SUPPAYMENT);
  $company_currency  = Bank_Currency::for_company();
  $show_currencies   = false;
  $show_both_amounts = false;
  if (($receipt['bank_curr_code'] != $company_currency) || ($receipt['SupplierCurrCode'] != $company_currency)) {
    $show_currencies = true;
  }
  if ($receipt['bank_curr_code'] != $receipt['SupplierCurrCode']) {
    $show_currencies   = true;
    $show_both_amounts = true;
  }
  echo "<div class='center'>";
  Table::start('standard width90');
  echo "<tr class='tablerowhead top'><th colspan=6>";
  Display::heading(_("Payment to Supplier") . " #$trans_no");
  echo "</td></tr>";
  echo '<tr>';
  Cell::labelled(_("To Supplier"), $receipt['supplier_name']);
  Cell::labelled(_("From Bank Account"), $receipt['bank_account_name']);
  Cell::labelled(_("Date Paid"), Dates::_sqlToDate($receipt['tran_date']));
  echo '</tr>';
  echo '<tr>';
  if ($show_currencies) {
    Cell::labelled(_("Payment Currency"), $receipt['bank_curr_code']);
  }
  Cell::labelled(_("Amount"), Num::_format(-$receipt['BankAmount'], User::_price_dec()));
  Cell::labelled(_("Payment Type"), Bank_Trans::$types[$receipt['BankTransType']]);
  echo '</tr>';
  echo '<tr>';
  if ($show_currencies) {
    Cell::labelled(_("Supplier's Currency"), $receipt['SupplierCurrCode']);
  }
  if ($show_both_amounts) {
    Cell::labelled(_("Amount"), Num::_format(-$receipt['Total'], User::_price_dec()));
  }
  Cell::labelled(_("Reference"), $receipt['ref']);
  echo '</tr>';
  DB_Comments::display_row(ST_SUPPAYMENT, $trans_no);
  Table::end(1);
  $voided = Voiding::is_voided(ST_SUPPAYMENT, $trans_no, _("This payment has been voided."));
  // now display the allocations for this payment
  if (!$voided) {
    GL_Allocation::from(PT_SUPPLIER, $receipt['creditor_id'], ST_SUPPAYMENT, $trans_no, -$receipt['Total']);
  }
  if (Input::_get('frame')) {
    return;
  }
  Page::end(true);

