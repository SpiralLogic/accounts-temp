<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Printer Locations"), SA_PRINTERS);
  list($Mode, $selected_id) = Page::simple_mode(true);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    $error = 0;
    if (empty($_POST['name'])) {
      $error = 1;
      Event::error(_("Printer name cannot be empty."));
      JS::_setFocus('name');
    } elseif (empty($_POST['host'])) {
      Event::notice(_("You have selected printing to server at user IP."));
    } elseif (!Validation::post_num('tout', 0, 60)) {
      $error = 1;
      Event::error(_("Timeout cannot be less than zero nor longer than 60 (sec)."));
      JS::_setFocus('tout');
    }
    if ($error != 1) {
      Printer::write_def($selected_id, Input::_post('name'), Input::_post('descr'), Input::_post('queue'), Input::_post('host'), Validation::input_num('port', 0), Validation::input_num('tout', 0));
      Event::success(
        $selected_id == -1 ? _('New printer definition has been created') : _('Selected printer definition has been updated')
      );
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    // PREVENT DELETES IF DEPENDENT RECORDS IN print_profiles
    $sql    = "SELECT COUNT(*) FROM print_profiles WHERE printer = " . DB::_escape($selected_id);
    $result = DB::_query($sql, "check printers relations failed");
    $myrow  = DB::_fetchRow($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this printer definition, because print profile have been created using it."));
    } else {
      $sql = "DELETE FROM printers WHERE id=" . DB::_escape($selected_id);
      DB::_query($sql, "could not delete printer definition");
      Event::notice(_('Selected printer definition has been deleted'));
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    unset($_POST);
  }
  $result = Printer::getAll();
  Forms::start();
  Table::start('padded grid');
  $th = array(_("Name"), _("Description"), _("Host"), _("Printer Queue"), '', '');
  Table::header($th);
  $k = 0; //row colour counter
  while ($myrow = DB::_fetch($result)) {
    Cell::label($myrow['name']);
    Cell::label($myrow['description']);
    Cell::label($myrow['host']);
    Cell::label($myrow['queue']);
    Forms::buttonEditCell("Edit" . $myrow['id'], _("Edit"));
    Forms::buttonDeleteCell("Delete" . $myrow['id'], _("Delete"));
    echo '</tr>';
  } //END WHILE LIST LOOP
  Table::end();
  Forms::end();
  echo '<br>';
  Forms::start();
  Table::start('standard');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      $myrow          = Printer::get($selected_id);
      $_POST['name']  = $myrow['name'];
      $_POST['descr'] = $myrow['description'];
      $_POST['queue'] = $myrow['queue'];
      $_POST['tout']  = $myrow['timeout'];
      $_POST['host']  = $myrow['host'];
      $_POST['port']  = $myrow['port'];
    }
    Forms::hidden('selected_id', $selected_id);
  } else {
    if (!isset($_POST['host'])) {
      $_POST['host'] = 'localhost';
    }
    if (!isset($_POST['port'])) {
      $_POST['port'] = '515';
    }
  }
  Forms::textRow(_("Printer Name") . ':', 'name', null, 20, 20);
  Forms::textRow(_("Printer Description") . ':', 'descr', null, 40, 60);
  Forms::textRow(_("Host name or IP") . ':', 'host', null, 30, 40);
  Forms::textRow(_("Port") . ':', 'port', null, 5, 5);
  Forms::textRow(_("Printer Queue") . ':', 'queue', null, 20, 20);
  Forms::textRow(_("Timeout") . ':', 'tout', null, 5, 5);
  Table::end(1);
  Forms::submitAddUpdateCenter($selected_id == -1, '', 'both');
  Forms::end();
  Page::end();


