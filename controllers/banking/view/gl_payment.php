<?php
  use ADV\App\Dimensions;
  use ADV\Core\Table;
  use ADV\Core\Cell;

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
  // get the pay-from bank payment info
  $result = Bank_Trans::get(ST_BANKPAYMENT, $trans_no);
  if (DB::_numRows($result) != 1) {
    Event::error("duplicate payment bank transaction found");
  }
  $from_trans       = DB::_fetch($result);
  $company_currency = Bank_Currency::for_company();
  $show_currencies  = false;
  if ($from_trans['bank_curr_code'] != $company_currency) {
    $show_currencies = true;
  }
  Table::start('standard width90');
  if ($show_currencies) {
    $colspan1 = 5;
    $colspan2 = 8;
  } else {
    $colspan1 = 3;
    $colspan2 = 6;
  }
  echo "<tr class='tablerowhead top'><th colspan=6>";
  Display::heading(_("Bank Payment") . " #$trans_no");
  echo "</td></tr>";
  echo '<tr>';
  Cell::labelled(_("From Bank Account"), $from_trans['bank_account_name']);
  if ($show_currencies) {
    Cell::labelled(_("Currency"), $from_trans['bank_curr_code']);
  }
  Cell::labelled(_("Amount"), Num::_format($from_trans['amount'], User::_price_dec()));
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("Pay To"), Bank::payment_person_name($from_trans['person_type_id'], $from_trans['person_id']));
  Cell::labelled(_("Date"), Dates::_sqlToDate($from_trans['trans_date']));
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("Reference"), $from_trans['ref']);
  Cell::labelled(_("Payment Type"), Bank_Trans::$types[$from_trans['account_type']]);
  echo '</tr>';
  DB_Comments::display_row(ST_BANKPAYMENT, $trans_no);
  Table::end(1);
  $voided = Voiding::is_voided(ST_BANKPAYMENT, $trans_no, _("This payment has been voided."));
  $items  = GL_Trans::get_many(ST_BANKPAYMENT, $trans_no);
  if (DB::_numRows($items) == 0) {
    Event::warning(_("There are no items for this payment."));
  } else {
    Display::heading(_("Items for this Payment"));
    if ($show_currencies) {
      Display::heading(_("Item Amounts are Shown in :") . " " . $company_currency);
    }
    echo "<br>";
    Table::start('padded grid width90');
    $dim = DB_Company::_get_pref('use_dimension');
    if ($dim == 2) {
      $th = array(
        _("Account Code"),
        _("Account Description"),
        _("Dimension") . " 1",
        _("Dimension") . " 2",
        _("Amount"),
        _("Memo")
      );
    } else {
      if ($dim == 1) {
        $th = array(
          _("Account Code"),
          _("Account Description"),
          _("Dimension"),
          _("Amount"),
          _("Memo")
        );
      } else {
        $th = array(
          _("Account Code"),
          _("Account Description"),
          _("Amount"),
          _("Memo")
        );
      }
    }
    Table::header($th);
    $k            = 0; //row colour counter
    $total_amount = 0;
    while ($item = DB::_fetch($items)) {
      if ($item["account"] != $from_trans["account_code"]) {
        Cell::label($item["account"]);
        Cell::label($item["account_name"]);
        if ($dim >= 1) {
          Cell::label(Dimensions::get_string($item['dimension_id'], true));
        }
        if ($dim > 1) {
          Cell::label(Dimensions::get_string($item['dimension2_id'], true));
        }
        Cell::amount($item["amount"]);
        Cell::label($item["memo_"]);
        echo '</tr>';
        $total_amount += $item["amount"];
      }
    }
    Table::label(_("Total"), Num::_format($total_amount, User::_price_dec()), "colspan=" . (2 + $dim) . " class='alignright'", "class='alignright'", 2);
    Table::end(1);
    if (!$voided) {
      GL_Allocation::from($from_trans['person_type_id'], $from_trans['person_id'], 1, $trans_no, -$from_trans['amount']);
    }
  }
  Page::end(true);
