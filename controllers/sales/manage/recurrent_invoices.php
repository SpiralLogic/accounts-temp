<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::_openWindow(950, 600);
  Page::start(_($help_context = "Recurrent Invoices"), SA_SRECURRENT);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    $input_error = 0;
    if (strlen($_POST['description']) == 0) {
      $input_error = 1;
      Event::error(_("The area description cannot be empty."));
      JS::_setFocus('description');
    }
    if ($input_error != 1) {
      if ($selected_id != -1) {
        $sql  = "UPDATE recurrent_invoices SET
 			description=" . DB::_escape($_POST['description']) . ",
 			order_no=" . DB::_escape($_POST['order_no']) . ",
 			debtor_id=" . DB::_escape($_POST['debtor_id']) . ",
 			group_no=" . DB::_escape($_POST['group_no']) . ",
 			days=" . Validation::input_num('days', 0) . ",
 			monthly=" . Validation::input_num('monthly', 0) . ",
 			begin='" . Dates::_dateToSql($_POST['begin']) . "',
 			end='" . Dates::_dateToSql($_POST['end']) . "'
 			WHERE id = " . DB::_escape($selected_id);
        $note = _('Selected recurrent invoice has been updated');
      } else {
        $sql  = "INSERT INTO recurrent_invoices (description, order_no, debtor_id,
 			group_no, days, monthly, begin, end, last_sent) VALUES (" . DB::_escape($_POST['description']) . ", " . DB::_escape($_POST['order_no']) . ", " . DB::_escape($_POST['debtor_id']) . ", " . DB::_escape($_POST['group_no']) . ", " . Validation::input_num('days', 0) . ", " . Validation::input_num(
          'monthly',
          0
        ) . ", '" . Dates::_dateToSql($_POST['begin']) . "', '" . Dates::_dateToSql($_POST['end']) . "', '" . Dates::_dateToSql(addYears($_POST['begin'], -5)) . "')";
        $note = _('New recurrent invoice has been added');
      }
      DB::_query($sql, "The recurrent invoice could not be updated or added");
      Event::notice($note);
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    $cancel_delete = 0;
    if ($cancel_delete == 0) {
      $sql = "DELETE FROM recurrent_invoices WHERE id=" . DB::_escape($selected_id);
      DB::_query($sql, "could not delete recurrent invoice");
      Event::notice(_('Selected recurrent invoice has been deleted'));
    } //end if Delete area
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    unset($_POST);
  }
  $sql    = "SELECT * FROM recurrent_invoices ORDER BY description, group_no, debtor_id";
  $result = DB::_query($sql, "could not get recurrent invoices");
  Forms::start();
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
    "",
    ""
  );
  Table::header($th);
  $k = 0;
  while ($myrow = DB::_fetch($result)) {
    $begin     = Dates::_sqlToDate($myrow["begin"]);
    $end       = Dates::_sqlToDate($myrow["end"]);
    $last_sent = Dates::_sqlToDate($myrow["last_sent"]);
    Cell::label($myrow["description"]);
    Cell::label(Debtor::viewTrans(ST_SALESORDER, $myrow["order_no"]));
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
    Forms::buttonEditCell("Edit" . $myrow["id"], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow["id"], _("Delete"));
    echo '</tr>';
  }
  Table::end();
  Forms::end();
  echo '<br>';
  Forms::start();
  Table::start('standard');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing area
      $sql                  = "SELECT * FROM recurrent_invoices WHERE id=" . DB::_escape($selected_id);
      $result               = DB::_query($sql, "could not get recurrent invoice");
      $myrow                = DB::_fetch($result);
      $_POST['description'] = $myrow["description"];
      $_POST['order_no']    = $myrow["order_no"];
      $_POST['debtor_id']   = $myrow["debtor_id"];
      $_POST['group_no']    = $myrow["group_no"];
      $_POST['days']        = $myrow["days"];
      $_POST['monthly']     = $myrow["monthly"];
      $_POST['begin']       = Dates::_sqlToDate($myrow["begin"]);
      $_POST['end']         = Dates::_sqlToDate($myrow["end"]);
    }
    Forms::hidden("selected_id", $selected_id);
  }
  Forms::textRowEx(_("Description:"), 'description', 50);
  Sales_UI::templates_row(_("Template:"), 'order_no');
  Debtor::row(_("Customer:"), 'debtor_id', null, " ", true);
  if ($_POST['debtor_id'] > 0) {
    Debtor_Branch::row(_("Branch:"), $_POST['debtor_id'], 'group_no', null, false);
  } else {
    Sales_UI::groups_row(_("Sales Group:"), 'group_no', null, " ");
  }
  Forms::SmallAmountRow(_("Days:"), 'days', 0, null, null, 0);
  Forms::SmallAmountRow(_("Monthly:"), 'monthly', 0, null, null, 0);
  Forms::dateRow(_("Begin:"), 'begin');
  Forms::dateRow(_("End:"), 'end', null, null, 0, 0, 5);
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();
