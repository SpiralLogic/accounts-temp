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
  JS::_headerFile('budget.js');
  Page::start(_($help_context = "Budget Entry"), SA_BUDGETENTRY);
  Validation::check(Validation::GL_ACCOUNT_GROUPS, _("There are no account groups defined. Please define at least one account group before entering accounts."));
  if (isset($_POST['add']) || isset($_POST['delete'])) {
    DB::_begin();
    for ($i = 0, $da = $_POST['begin']; Dates::_isGreaterThan($_POST['end'], $da); $i++) {
      if (isset($_POST['add'])) {
        add_update_gl_budget_trans($da, $_POST['account'], $_POST['dim1'], $_POST['dim2'], Validation::input_num('amount' . $i));
      } else {
        delete_gl_budget_trans($da, $_POST['account'], $_POST['dim1'], $_POST['dim2']);
      }
      $da = Dates::_addMonths($da, 1);
    }
    DB::_commit();
    if (isset($_POST['add'])) {
      Event::success(_("The Budget has been saved."));
    } else {
      Event::notice(_("The Budget has been deleted."));
    }
    //Display::meta_forward($_SERVER['DOCUMENT_URI']);
    Ajax::_activate('budget_tbl');
  }
  if (isset($_POST['submit']) || isset($_POST['update'])) {
    Ajax::_activate('budget_tbl');
  }
  Forms::start();
  if (Validation::check(Validation::GL_ACCOUNTS)) {
    $dim = DB_Company::_get_pref('use_dimension');
    Table::start('standard');
    GL_UI::fiscalyears_row(_("Fiscal Year:"), 'fyear', null);
    GL_UI::all_row(_("Account Code:"), 'account', null);
    if (!isset($_POST['dim1'])) {
      $_POST['dim1'] = 0;
    }
    if (!isset($_POST['dim2'])) {
      $_POST['dim2'] = 0;
    }
    if ($dim == 2) {
      Dimensions::select_row(_("Dimension") . " 1", 'dim1', $_POST['dim1'], true, null, false, 1);
      Dimensions::select_row(_("Dimension") . " 2", 'dim2', $_POST['dim2'], true, null, false, 2);
    } else {
      if ($dim == 1) {
        Dimensions::select_row(_("Dimension"), 'dim1', $_POST['dim1'], true, null, false, 1);
        Forms::hidden('dim2', 0);
      } else {
        Forms::hidden('dim1', 0);
        Forms::hidden('dim2', 0);
      }
    }
    Forms::submitRow('submit', _("Get"), true, '', '', true);
    Table::end(1);
    Ajax::_start_div('budget_tbl');
    Table::start('standard');
    $showdims = (($dim == 1 && $_POST['dim1'] == 0) || ($dim == 2 && $_POST['dim1'] == 0 && $_POST['dim2'] == 0));
    if ($showdims) {
      $th = array(_("Period"), _("Amount"), _("Dim. incl."), _("Last Year"));
    } else {
      $th = array(_("Period"), _("Amount"), _("Last Year"));
    }
    Table::header($th);
    $year = $_POST['fyear'];
    if (Input::_post('update') == '') {
      $sql            = "SELECT * FROM fiscal_year WHERE id=" . DB::_escape($year);
      $result         = DB::_query($sql, "could not get current fiscal year");
      $fyear          = DB::_fetch($result);
      $_POST['begin'] = Dates::_sqlToDate($fyear['begin']);
      $_POST['end']   = Dates::_sqlToDate($fyear['end']);
    }
    Forms::hidden('begin');
    Forms::hidden('end');
    $total = $btotal = $ltotal = 0;
    for ($i = 0, $date_ = $_POST['begin']; Dates::_isGreaterThan($_POST['end'], $date_); $i++) {
      echo '<tr>';
      if (Input::_post('update') == '') {
        $_POST['amount' . $i] = Num::_format(get_only_budget_trans_from_to($date_, $date_, $_POST['account'], $_POST['dim1'], $_POST['dim2']), 0);
      }
      Cell::label($date_);
      Forms::amountCells(null, 'amount' . $i, null, 15, null, 0);
      if ($showdims) {
        $d = GL_Trans::get_budget_from_to($date_, $date_, $_POST['account'], $_POST['dim1'], $_POST['dim2']);
        Cell::label(Num::_format($d, 0), ' class="alignright nowrap"');
        $btotal += $d;
      }
      $lamount = GL_Trans::get_from_to(Dates::_addYears($date_, -1), Dates::_addYears(Dates::_endMonth($date_), -1), $_POST['account'], $_POST['dim1'], $_POST['dim2']);
      $total += Validation::input_num('amount' . $i);
      $ltotal += $lamount;
      Cell::label(Num::_format($lamount, 0), ' class="alignright nowrap"');
      $date_ = Dates::_addMonths($date_, 1);
      echo '</tr>';
    }
    echo '<tr>';
    Cell::label("<span class='bold'>" . _("Total") . "</span>");
    Cell::label(Num::_format($total, 0), 'class="alignright bold" ', 'Total');
    if ($showdims) {
      Cell::label("<span class='bold'>" . Num::_format($btotal, 0) . "</span>", ' class="alignright nowrap"');
    }
    Cell::label("<span class='bold'>" . Num::_format($ltotal, 0) . "</span>", ' class="alignright nowrap"');
    echo '</tr>';
    Table::end(1);
    Ajax::_end_div();
    Forms::submitCenterBegin('update', _("Update"), '', null);
    Forms::submit('add', _("Save"), true, '', 'default');
    Forms::submitCenterEnd('delete', _("Delete"), '', true);
  }
  Forms::end();
  Page::end();
  /**
   * @param $date_
   * @param $account
   * @param $dimension
   * @param $dimension2
   *
   * @return bool
   */
  function exists_gl_budget($date_, $account, $dimension, $dimension2) {
    $sql    = "SELECT account FROM budget_trans WHERE account=" . DB::_escape($account) . " AND tran_date='$date_' AND
        dimension_id=" . DB::_escape($dimension) . " AND dimension2_id=" . DB::_escape($dimension2);
    $result = DB::_query($sql, "Cannot retreive a gl transaction");
    return (DB::_numRows($result) > 0);
  }

  /**
   * @param $date_
   * @param $account
   * @param $dimension
   * @param $dimension2
   * @param $amount
   */
  function add_update_gl_budget_trans($date_, $account, $dimension, $dimension2, $amount) {
    $date = Dates::_dateToSql($date_);
    if (exists_gl_budget($date, $account, $dimension, $dimension2)) {
      $sql = "UPDATE budget_trans SET amount=" . DB::_escape($amount) . " WHERE account=" . DB::_escape($account) . " AND dimension_id=" . DB::_escape(
        $dimension
      ) . " AND dimension2_id=" . DB::_escape($dimension2) . " AND tran_date='$date'";
    } else {
      $sql
        = "INSERT INTO budget_trans (tran_date,
            account, dimension_id, dimension2_id, amount, memo_) VALUES ('$date',
            " . DB::_escape($account) . ", " . DB::_escape($dimension) . ", " . DB::_escape($dimension2) . ", " . DB::_escape($amount) . ", '')";
    }
    DB::_query($sql, "The GL budget transaction could not be saved");
  }

  /**
   * @param $date_
   * @param $account
   * @param $dimension
   * @param $dimension2
   */
  function delete_gl_budget_trans($date_, $account, $dimension, $dimension2) {
    $date = Dates::_dateToSql($date_);
    $sql  = "DELETE FROM budget_trans WHERE account=" . DB::_escape($account) . " AND dimension_id=" . DB::_escape($dimension) . " AND dimension2_id=" . DB::_escape(
      $dimension2
    ) . " AND tran_date='$date'";
    DB::_query($sql, "The GL budget transaction could not be deleted");
  }

  /**
   * @param     $from_date
   * @param     $to_date
   * @param     $account
   * @param int $dimension
   * @param int $dimension2
   *
   * @return mixed
   */
  function get_only_budget_trans_from_to($from_date, $to_date, $account, $dimension = 0, $dimension2 = 0) {
    $from = Dates::_dateToSql($from_date);
    $to   = Dates::_dateToSql($to_date);
    $sql
            = "SELECT SUM(amount) FROM budget_trans
        WHERE account=" . DB::_escape($account) . " AND tran_date >= '$from' AND tran_date <= '$to'
         AND dimension_id = " . DB::_escape($dimension) . " AND dimension2_id = " . DB::_escape($dimension2);
    $result = DB::_query($sql, "No budget accounts were returned");
    $row    = DB::_fetchRow($result);
    return $row[0];
  }
