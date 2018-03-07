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
  use ADV\App\Reporting;
  use ADV\App\Dates;
  use ADV\App\Debtor\Debtor;
  use ADV\Core\DB\DB;
  use ADV\Core\Cell;
  use ADV\Core\Table;

  JS::_openWindow(950, 600);
  Page::start(_($help_context = "Create and Print Recurrent Invoices"), SA_SALESINVOICE);
  if (isset($_GET['recurrent'])) {
    $date = Dates::_today();
    if (Dates::_isDateInFiscalYear($date)) {
      $invs   = [];
      $sql    = "SELECT * FROM recurrent_invoices WHERE id=" . DB::_escape($_GET['recurrent']);
      $result = DB::_query($sql, "could not get recurrent invoice");
      $myrow  = DB::_fetch($result);
      if ($myrow['debtor_id'] == 0) {
        $cust = Sales_Branch::get_from_group($myrow['group_no']);
        while ($row = DB::_fetch($cust)) {
          $invs[] = Sales_Invoice::create_recurrent($row['debtor_id'], $row['branch_id'], $myrow['order_no'], $myrow['id']);
        }
      } else {
        $invs[] = Sales_Invoice::create_recurrent($myrow['debtor_id'], $myrow['group_no'], $myrow['order_no'], $myrow['id']);
      }
      if (count($invs) > 0) {
        $min = min($invs);
        $max = max($invs);
      } else {
        $min = $max = 0;
      }
      Event::success(sprintf(_("%s recurrent invoice(s) created, # $min - # $max."), count($invs)));
      if (count($invs) > 0) {
        $ar = array(
          'PARAM_0' => $min . "-" . ST_SALESINVOICE,
          'PARAM_1' => $max . "-" . ST_SALESINVOICE,
          'PARAM_2' => "",
          'PARAM_3' => 0,
          'PARAM_4' => 0,
          'PARAM_5' => "",
          'PARAM_6' => ST_SALESINVOICE
        );
        Event::warning(Reporting::print_link(_("&Print Recurrent Invoices # $min - # $max"), 107, $ar), 0, 1);
        $ar['PARAM_3'] = 1;
        Event::warning(Reporting::print_link(_("&Email Recurrent Invoices # $min - # $max"), 107, $ar), 0, 1);
      }
    } else {
      Event::error(_("The entered date is not in fiscal year."));
    }
  }
  $sql    = "SELECT * FROM recurrent_invoices ORDER BY description, group_no, debtor_id";
  $result = DB::_query($sql, "could not get recurrent invoices");
  Table::start('padded grid width70');
  $th = array(
    _("Description"),
    _("Template No"),
    _("Customer"),
    _("Branch") . "/" . _("Group"),
    _("Days"),
    _("Monthly"),
    _("Begin"),
    _("End"),
    _("Last Created"),
    ""
  );
  Table::header($th);
  $k     = 0;
  $today = Dates::_addDays(Dates::_today(), 1);
  $due   = false;
  while ($myrow = DB::_fetch($result)) {
    $begin     = Dates::_sqlToDate($myrow["begin"]);
    $end       = Dates::_sqlToDate($myrow["end"]);
    $last_sent = Dates::_sqlToDate($myrow["last_sent"]);
    if ($myrow['monthly'] > 0) {
      $due_date = Dates::_beginMonth($last_sent);
    } else {
      $due_date = $last_sent;
    }
    $due_date = Dates::_addMonths($due_date, $myrow['monthly']);
    $due_date = Dates::_addDays($due_date, $myrow['days']);
    $overdue  = Dates::_isGreaterThan($today, $due_date) && Dates::_isGreaterThan($today, $begin) && Dates::_isGreaterThan($end, $today);
    if ($overdue) {
      echo "<tr class='overduebg'>";
      $due = true;
    } else {
    }
    Cell::label($myrow["description"]);
    Cell::label(Debtor::viewTrans(30, $myrow["order_no"]));
    if ($myrow["debtor_id"] == 0) {
      Cell::label("");
      Cell::label(Sales_Group::get_name($myrow["group_no"]));
    } else {
      Cell::label(Debtor::get_name($myrow["debtor_id"]));
      Cell::label(Sales_Branch::get_name($myrow['group_no']));
    }
    Cell::label($myrow["days"]);
    Cell::label($myrow['monthly']);
    Cell::label($begin);
    Cell::label($end);
    Cell::label($last_sent);
    if ($overdue) {
      Cell::label("<a href='/sales/create_recurrent_invoices.php?recurrent=" . $myrow["id"] . "'>" . _("Create Invoices") . "</a>");
    } else {
      Cell::label("");
    }
    echo '</tr>';
  }
  Table::end();
  if ($due) {
    Event::warning(_("Marked items are due."), 1, 0, "class='overduefg'");
  } else {
    Event::warning(_("No recurrent invoices are due."), 1, 0);
  }
  echo '<br>';
  Page::end();
