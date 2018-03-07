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
  use ADV\Core\Input\Input;
  use ADV\Core\DB\DB;
  use ADV\Core\Table;
  use ADV\App\Forms;
  use ADV\Core\Cell;
  use ADV\Core\JS;
  use ADV\Core\Event;

  Page::start(_($help_context = "Inventory Movement Types"), SA_INVENTORYMOVETYPE);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    //initialise no input errors assumed initially before we test
    $input_error = 0;
    if (strlen($_POST['name']) == 0) {
      $input_error = 1;
      Event::error(_("The inventory movement type name cannot be empty."));
      JS::_setFocus('name');
    }
    if ($input_error != 1) {
      if ($selected_id != -1) {
        Inv_Movement::update_type($selected_id, $_POST['name']);
        Event::success(_('Selected movement type has been updated'));
      } else {
        Inv_Movement::add_type($_POST['name']);
        Event::success(_('New movement type has been added'));
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
    $sql
            = "SELECT COUNT(*) FROM stock_moves
		WHERE type=" . ST_INVADJUST . " AND person_id=" . DB::_escape($selected_id);
    $result = DB::_query($sql, "could not query stock moves");
    $myrow  = DB::_fetchRow($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this inventory movement type because item transactions have been created referring to it."));
      return false;
    }
    return true;
  }

  if ($Mode == MODE_DELETE) {
    if (can_delete($selected_id)) {
      Inv_Movement::delete($selected_id);
      Event::notice(_('Selected movement type has been deleted'));
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav         = Input::_post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result = Inv_Movement::get_all_types(Input::_hasPost('show_inactive'));
  Forms::start();
  Table::start('padded grid width30');
  $th = array(_("Description"), "", "");
  Forms::inactiveControlCol($th);
  Table::header($th);
  $k = 0;
  while ($myrow = DB::_fetch($result)) {
    Cell::label($myrow["name"]);
    Forms::inactiveControlCell($myrow["id"], $myrow["inactive"], 'movement_types', 'id');
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
      $myrow         = Inv_Movement::get_type($selected_id);
      $_POST['name'] = $myrow["name"];
    }
    Forms::hidden('selected_id', $selected_id);
  }
  Forms::textRow(_("Description:"), 'name', null, 50, 50);
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();


