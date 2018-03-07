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
  Page::start(_($help_context = "Bank Statement"), SA_BANKTRANSVIEW);
  Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
  // Ajax updates
  //
  if (Input::_post('Show')) {
    Ajax::_activate('trans_tbl');
  }
  Forms::start();
  Table::start('noborder');
  echo '<tr>';
  Bank_Account::cells(_("Account:"), 'bank_account', null);
  Forms::dateCells(_("From:"), 'TransAfterDate', '', null, -30);
  Forms::dateCells(_("To:"), 'TransToDate');
  Forms::submitCells('Show', _("Show"), '', '', 'default');
  echo '</tr>';
  Table::end();
  Forms::end();
  $date_after = Dates::_dateToSql($_POST['TransAfterDate']);
  $date_to    = Dates::_dateToSql($_POST['TransToDate']);
  if (!isset($_POST['bank_account'])) {
    $_POST['bank_account'] = "";
  }
  $sql
          = "SELECT bank_trans.* FROM bank_trans
    WHERE bank_trans.bank_act = " . DB::_escape($_POST['bank_account']) . "
    AND trans_date >= '$date_after'
    AND trans_date <= '$date_to'
    ORDER BY trans_date,bank_trans.id";
  $result = DB::_query($sql, "The transactions for '" . $_POST['bank_account'] . "' could not be retrieved");
  Ajax::_start_div('trans_tbl');
  $act = Bank_Account::get($_POST["bank_account"]);
  Display::heading($act['bank_account_name'] . " - " . $act['bank_curr_code']);
  Table::start('padded grid');
  $th = array(
    _("Type"),
    _("#"),
    _("Reference"),
    _("Date"),
    _("Debit"),
    _("Credit"),
    _("Balance"),
    _("Person/Item"),
    ""
  );
  Table::header($th);
  $sql        = "SELECT SUM(amount) FROM bank_trans WHERE bank_act=" . DB::_escape($_POST['bank_account']) . "
    AND trans_date < '$date_after'";
  $before_qty = DB::_query($sql, "The starting balance on hand could not be calculated");
  echo "<tr class='inquirybg'>";
  Cell::label("<span class='bold'>" . _("Opening Balance") . " - " . $_POST['TransAfterDate'] . "</span>", "colspan=4");
  $bfw_row = DB::_fetchRow($before_qty);
  $bfw     = $bfw_row[0];
  Cell::debitOrCredit($bfw);
  Cell::label("");
  Cell::label("", "colspan=2");
  echo '</tr>';
  $running_total = $bfw;
  $j             = 1;
  $k             = 0; //row colour counter
  while ($myrow = DB::_fetch($result)) {
    $running_total += $myrow["amount"];
    $trandate = Dates::_sqlToDate($myrow["trans_date"]);
    Cell::label(SysTypes::$names[$myrow["type"]]);
    Cell::label(GL_UI::viewTrans($myrow["type"], $myrow["trans_no"]));
    Cell::label(GL_UI::viewTrans($myrow["type"], $myrow["trans_no"], $myrow['ref']));
    Cell::label($trandate);
    Cell::debitOrCredit($myrow["amount"]);
    Cell::amount($running_total);
    Cell::label(Bank::payment_person_name($myrow["person_type_id"], $myrow["person_id"]));
    Cell::label(GL_UI::view($myrow["type"], $myrow["trans_no"]));
    echo '</tr>';
    if ($j == 12) {
      $j = 1;
      Table::header($th);
    }
    $j++;
  }
  //end of while loop
  echo "<tr class='inquirybg'>";
  Cell::label("<span class='bold'>" . _("Ending Balance") . " - " . $_POST['TransToDate'] . "</span>", "colspan=4");
  Cell::debitOrCredit($running_total);
  Cell::label("");
  Cell::label("", "colspan=2");
  echo '</tr>';
  Table::end(2);
  Ajax::_end_div();
  Page::end();
