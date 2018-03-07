<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start('', SA_BANKTRANSVIEW, true);
  if (isset($_GET["trans_no"])) {
    $trans_no = $_GET["trans_no"];
  }
  $result = Bank_Trans::get(ST_BANKTRANSFER, $trans_no);
  if (DB::_numRows($result) != 2) {
    Event::error("Bank transfer does not contain two records");
  }
  $trans1 = DB::_fetch($result);
  $trans2 = DB::_fetch($result);
  if ($trans1["amount"] < 0) {
    $from_trans = $trans1; // from trans is the negative one
    $to_trans   = $trans2;
  } else {
    $from_trans = $trans2;
    $to_trans   = $trans1;
  }
  $company_currency  = Bank_Currency::for_company();
  $show_currencies   = false;
  $show_both_amounts = false;
  if (($from_trans['bank_curr_code'] != $company_currency) || ($to_trans['bank_curr_code'] != $company_currency)) {
    $show_currencies = true;
  }
  if ($from_trans['bank_curr_code'] != $to_trans['bank_curr_code']) {
    $show_currencies   = true;
    $show_both_amounts = true;
  }
  Table::start('padded width90');
  echo "<tr class='tablerowhead top'><th colspan=6>";
  Display::heading(SysTypes::$names[ST_BANKTRANSFER] . " #$trans_no");
  echo "</td></tr>";
  echo '<tr>';
  Cell::labelled(_("From Bank Account"), $from_trans['bank_account_name']);
  if ($show_currencies) {
    Cell::labelled(_("Currency"), $from_trans['bank_curr_code']);
  }
  Cell::labelled(_("Amount"), Num::_format(-$from_trans['amount'], User::_price_dec()), '', "class='alignright'");
  if ($show_currencies) {
    echo '</tr>';
    echo '<tr>';
  }
  Cell::labelled(_("To Bank Account"), $to_trans['bank_account_name']);
  if ($show_currencies) {
    Cell::labelled(_("Currency"), $to_trans['bank_curr_code']);
  }
  if ($show_both_amounts) {
    Cell::labelled(_("Amount"), Num::_format($to_trans['amount'], User::_price_dec()), '', "class='alignright'");
  }
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("Date"), Dates::_sqlToDate($from_trans['trans_date']));
  Cell::labelled(_("Transfer Type"), Bank_Trans::$types[$from_trans['account_type']]);
  Cell::labelled(_("Reference"), $from_trans['ref']);
  echo '</tr>';
  DB_Comments::display_row(ST_BANKTRANSFER, $trans_no);
  Table::end(1);
  Voiding::is_voided(ST_BANKTRANSFER, $trans_no, _("This transfer has been voided."));
  Page::end(true);
