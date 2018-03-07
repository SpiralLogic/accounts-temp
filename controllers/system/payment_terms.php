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
  use ADV\App\Page;
  use ADV\Core\Cell;
  use ADV\Core\Table;
  use ADV\App\Forms;
  use ADV\Core\DB\DB;
  use ADV\Core\Input\Input;
  use ADV\Core\JS;
  use ADV\Core\Event;

  Page::start(_($help_context = "Payment Terms"), SA_PAYTERMS);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    $inpug_error = 0;
    if (!is_numeric($_POST['DayNumber'])) {
      $inpug_error = 1;
      Event::error(_("The number of days or the day in the following month must be numeric."));
      JS::_setFocus('DayNumber');
    } elseif (strlen($_POST['terms']) == 0) {
      $inpug_error = 1;
      Event::error(_("The Terms description must be entered."));
      JS::_setFocus('terms');
    }
    if ($_POST['DayNumber'] == '') {
      $_POST['DayNumber'] = 0;
    }
    if ($inpug_error != 1) {
      if ($selected_id != -1) {
        if (Input::_hasPost('DaysOrFoll')) {
          $sql = "UPDATE payment_terms SET terms=" . DB::_escape($_POST['terms']) . ",
					day_in_following_month=0,
					days_before_due=" . DB::_escape($_POST['DayNumber']) . "
					WHERE terms_indicator = " . DB::_escape($selected_id);
        } else {
          $sql = "UPDATE payment_terms SET terms=" . DB::_escape($_POST['terms']) . ",
					day_in_following_month=" . DB::_escape($_POST['DayNumber']) . ",
					days_before_due=0
					WHERE terms_indicator = " . DB::_escape($selected_id);
        }
        $note = _('Selected payment terms have been updated');
      } else {
        if (Input::_hasPost('DaysOrFoll')) {
          $sql
            = "INSERT INTO payment_terms (terms,
					days_before_due, day_in_following_month)
					VALUES (" . DB::_escape($_POST['terms']) . ", " . DB::_escape($_POST['DayNumber']) . ", 0)";
        } else {
          $sql
            = "INSERT INTO payment_terms (terms,
					days_before_due, day_in_following_month)
					VALUES (" . DB::_escape($_POST['terms']) . ",
					0, " . DB::_escape($_POST['DayNumber']) . ")";
        }
        $note = _('New payment terms have been added');
      }
      //run the sql from either of the above possibilites
      DB::_query($sql, "The payment term could not be added or updated");
      Event::success($note);
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    // PREVENT DELETES IF DEPENDENT RECORDS IN debtors
    $sql    = "SELECT COUNT(*) FROM debtors WHERE payment_terms = " . DB::_escape($selected_id);
    $result = DB::_query($sql, "check failed");
    $myrow  = DB::_fetchRow($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this payment term, because customer accounts have been created referring to this term."));
    } else {
      $sql    = "SELECT COUNT(*) FROM suppliers WHERE payment_terms = " . DB::_escape($selected_id);
      $result = DB::_query($sql, "check failed");
      $myrow  = DB::_fetchRow($result);
      if ($myrow[0] > 0) {
        Event::error(_("Cannot delete this payment term, because supplier accounts have been created referring to this term"));
      } else {
        //only delete if used in neither customer or supplier accounts
        $sql = "DELETE FROM payment_terms WHERE terms_indicator=" . DB::_escape($selected_id);
        DB::_query($sql, "could not delete a payment terms");
        Event::notice(_('Selected payment terms have been deleted'));
      }
    }
    //end if payment terms used in customer or supplier accounts
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav         = Input::_post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $sql = "SELECT * FROM payment_terms";
  if (!Input::_hasPost('show_inactive')) {
    $sql .= " WHERE !inactive";
  }
  $result = DB::_query($sql, "could not get payment terms");
  Forms::start();
  Table::start('padded grid');
  $th = array(_("Description"), _("Following Month On"), _("Due After (Days)"), "", "");
  Forms::inactiveControlCol($th);
  Table::header($th);
  $k = 0; //row colour counter
  while ($myrow = DB::_fetch($result)) {
    if ($myrow["day_in_following_month"] == 0) {
      $full_text = _("N/A");
    } else {
      $full_text = $myrow["day_in_following_month"];
    }
    if ($myrow["days_before_due"] == 0) {
      $after_text = _("N/A");
    } else {
      $after_text = $myrow["days_before_due"] . " " . _("days");
    }
    Cell::label($myrow["terms"]);
    Cell::label($full_text);
    Cell::label($after_text);
    Forms::inactiveControlCell($myrow["terms_indicator"], $myrow["inactive"], 'payment_terms', "terms_indicator");
    Forms::buttonEditCell("Edit" . $myrow["terms_indicator"], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow["terms_indicator"], _("Delete"));
    echo '</tr>';
  } //END WHILE LIST LOOP
  Forms::inactiveControlRow($th);
  Table::end(1);
  Table::start('standard');
  $day_in_following_month = $days_before_due = 0;
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing payment terms
      $sql
                              = "SELECT * FROM payment_terms
			WHERE terms_indicator=" . DB::_escape($selected_id);
      $result                 = DB::_query($sql, "could not get payment term");
      $myrow                  = DB::_fetch($result);
      $_POST['terms']         = $myrow["terms"];
      $days_before_due        = $myrow["days_before_due"];
      $day_in_following_month = $myrow["day_in_following_month"];
      unset($_POST['DayNumber']);
    }
    Forms::hidden('selected_id', $selected_id);
  }
  Forms::textRow(_("Terms Description:"), 'terms', null, 40, 40);
  Forms::checkRow(_("Due After A Given No. Of Days:"), 'DaysOrFoll', $day_in_following_month == 0);
  if (!isset($_POST['DayNumber'])) {
    if ($days_before_due != 0) {
      $_POST['DayNumber'] = $days_before_due;
    } else {
      $_POST['DayNumber'] = $day_in_following_month;
    }
  }
  Forms::textRowEx(_("Days (Or Day In Following Month):"), 'DayNumber', 3);
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();


