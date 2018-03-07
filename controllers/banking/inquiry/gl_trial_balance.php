<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  $js = "";
  Page::start(_($help_context = "Trial Balance"), SA_GLANALYTIC);
  // Ajax updates
  //
  if (Input::_post('Show')) {
    Ajax::_activate('balance_tbl');
  }
  gl_inquiry_controls();
  display_trial_balance();
  Page::end();
  function gl_inquiry_controls() {
    Forms::start();
    Table::start('noborder');
    Forms::dateCells(_("From:"), 'TransFromDate', '', null, -30);
    Forms::dateCells(_("To:"), 'TransToDate');
    Forms::checkCells(_("No zero values"), 'NoZero', null);
    Forms::checkCells(_("Only balances"), 'Balance', null);
    Forms::submitCells('Show', _("Show"), '', '', 'default');
    Table::end();
    Forms::end();
  }

  function display_trial_balance() {
    Ajax::_start_div('balance_tbl');
    Table::start('padded grid');
    $tableheader
      = "<tr>
 <td rowspan=2 class='tablehead'>" . _("Account") . "</td>
 <td rowspan=2 class='tablehead'>" . _("Account Name") . "</td>
		<td colspan=2 class='tablehead'>" . _("Brought Forward") . "</td>
		<td colspan=2 class='tablehead'>" . _("This Period") . "</td>
		<td colspan=2 class='tablehead'>" . _("Balance") . "</td>
		</tr><tr>
		<td class='tablehead'>" . _("Debit") . "</td>
 <td class='tablehead'>" . _("Credit") . "</td>
		<td class='tablehead'>" . _("Debit") . "</td>
		<td class='tablehead'>" . _("Credit") . "</td>
 <td class='tablehead'>" . _("Debit") . "</td>
 <td class='tablehead'>" . _("Credit") . "</td>
 </tr>";
    echo $tableheader;
    $k        = 0;
    $accounts = GL_Account::getAll();
    $pdeb     = $pcre = $cdeb = $ccre = $tdeb = $tcre = $pbal = $cbal = $tbal = 0;
    $begin    = Dates::_beginFiscalYear();
    if (Dates::_isGreaterThan($begin, $_POST['TransFromDate'])) {
      $begin = $_POST['TransFromDate'];
    }
    $begin = Dates::_addDays($begin, -1);
    while ($account = DB::_fetch($accounts)) {
      $prev = GL_Trans::get_balance($account["account_code"], 0, 0, $begin, $_POST['TransFromDate'], false, false);
      $curr = GL_Trans::get_balance($account["account_code"], 0, 0, $_POST['TransFromDate'], $_POST['TransToDate'], true, true);
      $tot  = GL_Trans::get_balance($account["account_code"], 0, 0, $begin, $_POST['TransToDate'], false, true);
      if (Input::_hasPost("NoZero") && !$prev['balance'] && !$curr['balance'] && !$tot['balance']) {
        continue;
      }
      $url = "<a href='" . ROOT_URL . "gl/search/account?TransFromDate=" . $_POST["TransFromDate"] . "&TransToDate=" . $_POST["TransToDate"] . "&account=" . $account["account_code"] . "'>" . $account["account_code"] . "</a>";
      Cell::label($url);
      Cell::label($account["account_name"]);
      if (Input::_hasPost('Balance')) {
        Cell::debitOrCredit($prev['balance']);
        Cell::debitOrCredit($curr['balance']);
        Cell::debitOrCredit($tot['balance']);
      } else {
        Cell::amount($prev['debit']);
        Cell::amount($prev['credit']);
        Cell::amount($curr['debit']);
        Cell::amount($curr['credit']);
        Cell::amount($tot['debit']);
        Cell::amount($tot['credit']);
        $pdeb += $prev['debit'];
        $pcre += $prev['credit'];
        $cdeb += $curr['debit'];
        $ccre += $curr['credit'];
        $tdeb += $tot['debit'];
        $tcre += $tot['credit'];
      }
      $pbal += $prev['balance'];
      $cbal += $curr['balance'];
      $tbal += $tot['balance'];
      echo '</tr>';
    }
    //$prev = GL_Trans::get_balance(null, $begin, $_POST['TransFromDate'], false, false);
    //$curr = GL_Trans::get_balance(null, $_POST['TransFromDate'], $_POST['TransToDate'], true, true);
    //$tot = GL_Trans::get_balance(null, $begin, $_POST['TransToDate'], false, true);
    if (!Input::_hasPost('Balance')) {
      echo "<tr class='inquirybg' style='font-weight:bold'>";
      Cell::label(_("Total") . " - " . $_POST['TransToDate'], "colspan=2");
      Cell::amount($pdeb);
      Cell::amount($pcre);
      Cell::amount($cdeb);
      Cell::amount($ccre);
      Cell::amount($tdeb);
      Cell::amount($tcre);
      echo '</tr>';
    }
    echo "<tr class='inquirybg' style='font-weight:bold'>";
    Cell::label(_("Ending Balance") . " - " . $_POST['TransToDate'], "colspan=2");
    Cell::debitOrCredit($pbal);
    Cell::debitOrCredit($cbal);
    Cell::debitOrCredit($tbal);
    echo '</tr>';
    Table::end(1);
    Ajax::_end_div();
  }
