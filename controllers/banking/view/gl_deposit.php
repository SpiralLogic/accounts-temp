<?php
  use ADV\App\Dimensions;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "View Bank Deposit"), SA_BANKTRANSVIEW, true);
  if (isset($_GET["trans_no"])) {
    $trans_no = $_GET["trans_no"];
  }
  // get the pay-to bank payment info
  $result = Bank_Trans::get(ST_BANKDEPOSIT, $trans_no);
  if (DB::_numRows($result) != 1) {
    Event::error("duplicate payment bank transaction found", "");
  }
  $to_trans         = DB::_fetch($result);
  $company_currency = Bank_Currency::for_company();
  $show_currencies  = false;
  if ($to_trans['bank_curr_code'] != $company_currency) {
    $show_currencies = true;
  }
  echo "<div class='center'>";
  Display::heading(_("GL Deposit") . " #$trans_no");
  echo "<br>";
  Table::start('padded width90');
  if ($show_currencies) {
    $colspan1 = 5;
    $colspan2 = 8;
  } else {
    $colspan1 = 3;
    $colspan2 = 6;
  }
  echo '<tr>';
  Cell::labelled(_("To Bank Account"), $to_trans['bank_account_name'], "class='tablerowhead'");
  if ($show_currencies) {
    Cell::labelled(_("Currency"), $to_trans['bank_curr_code'], "class='tablerowhead'");
  }
  Cell::labelled(_("Amount"), Num::_format($to_trans['amount'], User::_price_dec()), "class='tablerowhead'", "class='alignright'");
  Cell::labelled(_("Date"), Dates::_sqlToDate($to_trans['trans_date']), "class='tablerowhead'");
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("From"), Bank::payment_person_name($to_trans['person_type_id'], $to_trans['person_id']), "class='tablerowhead'", "colspan=$colspan1");
  Cell::labelled(_("Deposit Type"), Bank_Trans::$types[$to_trans['account_type']], "class='tablerowhead'");
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("Reference"), $to_trans['ref'], "class='tablerowhead'", "colspan=$colspan2");
  echo '</tr>';
  DB_Comments::display_row(ST_BANKDEPOSIT, $trans_no);
  Table::end(1);
  Voiding::is_voided(ST_BANKDEPOSIT, $trans_no, _("This deposit has been voided."));
  $items = GL_Trans::get_many(ST_BANKDEPOSIT, $trans_no);
  if (DB::_numRows($items) == 0) {
    Event::warning(_("There are no items for this deposit."));
  } else {
    Display::heading(_("Items for this Deposit"));
    if ($show_currencies) {
      Display::heading(_("Item Amounts are Shown in :") . " " . $company_currency);
    }
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
      if ($item["account"] != $to_trans["account_code"]) {
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
    Table::label(_("Total"), Num::_format($total_amount, User::_price_dec()), "colspan=" . (2 + $dim) . " class='alignright'", "class='alignright'");
    Table::end(1);
    GL_Allocation::from($to_trans['person_type_id'], $to_trans['person_id'], 2, $trans_no, $to_trans['amount']);
  }
  Page::end(true);
