<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Work Centres"), SA_WORKCENTRES);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    //initialise no input errors assumed initially before we test
    $input_error = 0;
    if (strlen($_POST['name']) == 0) {
      $input_error = 1;
      Event::error(_("The work centre name cannot be empty."));
      JS::_setFocus('name');
    }
    if ($input_error != 1) {
      if ($selected_id != -1) {
        WO_WorkCentre::update($selected_id, $_POST['name'], $_POST['description']);
        Event::success(_('Selected work center has been updated'));
      } else {
        WO_WorkCentre::add($_POST['name'], $_POST['description']);
        Event::success(_('New work center has been added'));
      }
      $Mode = MODE_RESET;
    }
  }
  /**
   * @param $selected_id
   *
   * @return bool
   */
  function can_delete($selected_id) {
    $sql    = "SELECT COUNT(*) FROM bom WHERE workcentre_added=" . DB::_escape($selected_id);
    $result = DB::_query($sql, "check can delete work centre");
    $myrow  = DB::_fetchRow($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this work centre because BOMs have been created referring to it."));
      return false;
    }
    $sql    = "SELECT COUNT(*) FROM wo_requirements WHERE workcentre=" . DB::_escape($selected_id);
    $result = DB::_query($sql, "check can delete work centre");
    $myrow  = DB::_fetchRow($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this work centre because work order requirements have been created referring to it."));
      return false;
    }
    return true;
  }

  if ($Mode == MODE_DELETE) {
    if (can_delete($selected_id)) {
      WO_WorkCentre::delete($selected_id);
      Event::notice(_('Selected work center has been deleted'));
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav         = Input::_post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result = WO_WorkCentre::getAll(Input::_hasPost('show_inactive'));
  Forms::start();
  Table::start('padded grid width50');
  $th = array(_("Name"), _("description"), "", "");
  Forms::inactiveControlCol($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::_fetch($result)) {
    Cell::label($myrow["name"]);
    Cell::label($myrow["description"]);
    Forms::inactiveControlCell($myrow["id"], $myrow["inactive"], 'workcentres', 'id');
    Forms::buttonEditCell("Edit" . $myrow['id'], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow['id'], _("Delete"));
    echo '</tr>';
  }
  Forms::inactiveControlRow($th);
  Table::end(1);
  Table::start('standard');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing status code
      $myrow                = WO_WorkCentre::get($selected_id);
      $_POST['name']        = $myrow["name"];
      $_POST['description'] = $myrow["description"];
    }
    Forms::hidden('selected_id', $selected_id);
  }
  Forms::textRowEx(_("Name:"), 'name', 40);
  Forms::textRowEx(_("Description:"), 'description', 50);
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();

