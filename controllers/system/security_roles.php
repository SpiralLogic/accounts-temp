<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Access setup"), SA_SECROLES);
  $new_role = Input::_post('role') == '' || Input::_post('cancel') || Input::_post('clone');
  // Following compare function is used for sorting areas
  // in such a way that security areas defined by module/plugin
  // is properly placed under related section regardless of
  // unique extension number, with order inside sections preserved.
  //
  /**
   * @param $area1
   * @param $area2
   *
   * @return int
   */
  function comp_areas($area1, $area2) {
    $sec_comp = ($area1[0] & 0xff00) - ($area2[0] & 0xff00);
    return $sec_comp == 0 ? ($area1[2] - $area2[2]) : $sec_comp;
  }

  /**
   * @param $areas
   *
   * @return array
   */
  function sort_areas($areas) {
    $old_order = 0;
    foreach ($areas as $key => $area) {
      $areas[$key][] = $old_order++;
    }
    uasort($areas, 'comp_areas');
    return $areas;
  }

  if (Forms::isListUpdated('role')) {
    Ajax::_activate('details');
    Ajax::_activate('controls');
  }
  function clear_data() {
    unset($_POST);
  }

  if (Input::_post('addupdate')) {
    $input_error = 0;
    if ($_POST['description'] == '') {
      $input_error = 1;
      Event::error(_("Role description cannot be empty."));
      JS::_setFocus('description');
    } elseif ($_POST['name'] == '') {
      $input_error = 1;
      Event::error(_("Role name cannot be empty."));
      JS::_setFocus('name');
    }
    // prevent accidental editor lockup by removing SA_SECROLES
    if (Input::_post('role') == User::_i()->access) {
      if (!isset($_POST['Area' . User::_i()->Security['areas'][SA_SECROLES][0]]) || !isset($_POST[Section . SS_SETUP])
      ) {
        Event::error(_("Access level edition in Company setup section have to be enabled for your account."));
        $input_error = 1;
        JS::_setFocus(
          !isset($_POST['Section' . SS_SETUP]) ? 'Section' . SS_SETUP : 'Area' . User::_i()->Security['areas'][SA_SECROLES][0]
        );
      }
    }
    if ($input_error == 0) {
      $sections = [];
      $areas    = [];
      foreach ($_POST as $p => $val) {
        if (substr($p, 0, 4) == 'Area') {
          $a = substr($p, 4);
          if (($a & ~0xffff) && (($a & 0xff00) < (99 << 8))) {
            $sections[] = $a & ~0xff; // add extended section for plugins
          }
          $areas[] = $a;
        }
        if (substr($p, 0, 7) == 'Section') {
          $sections[] = substr($p, 7);
        }
      }
      //		$areas = sort_areas($areas);
      $sections = array_values($sections);
      if ($new_role) {
        Security::add_role($_POST['name'], $_POST['description'], $sections, $areas);
        Event::success(_("New security role has been added."));
      } else {
        Security::update_role($_POST['role'], $_POST['name'], $_POST['description'], $sections, $areas);
        DB::_updateRecordStatus($_POST['role'], Input::_post('inactive'), 'security_roles', 'id');
        Event::success(_("Security role has been updated."));
      }
      $new_role = true;
      clear_data();
      Ajax::_activate('_page_body');
    }
  }
  if (Input::_post('delete')) {
    if (Security::check_role_used(Input::_post('role'))) {
      Event::error(_("This role is currently assigned to some users and cannot be deleted"));
    } else {
      Security::delete(Input::_post('role'));
      Event::notice(_("Security role has been sucessfully deleted."));
      unset($_POST['role']);
    }
    Ajax::_activate('_page_body');
  }
  if (Input::_post('cancel')) {
    unset($_POST['role']);
    Ajax::_activate('_page_body');
  }
  if (!isset($_POST['role']) || Input::_post('clone') || Forms::isListUpdated('role')) {
    $id    = Input::_post('role');
    $clone = Input::_post('clone');
    unset($_POST);
    if ($id) {
      $row                  = Security::get_role($id);
      $_POST['description'] = $row['description'];
      $_POST['name']        = $row['role'];
      //	if ($row['inactive']
      //		$_POST['inactive'] = 1;
      $_POST['inactive'] = $row['inactive'];
      $access            = $row['areas'];
      $sections          = $row['sections'];
    } else {
      $_POST['description'] = $_POST['name'] = '';
      unset($_POST['inactive']);
      $access = $sections = [];
    }
    foreach ($access as $a) {
      $_POST['Area' . $a] = 1;
    }
    foreach ($sections as $s) {
      $_POST['Section' . $s] = 1;
    }
    if ($clone) {
      JS::_setFocus('name');
      Ajax::_activate('_page_body');
    } else {
      $_POST['role'] = $id;
    }
  }
  Forms::start();
  Table::start('noborder');
  echo '<tr>';
  Security::roles_cells(_("Role:") . "&nbsp;", 'role', null, true, true, Input::_hasPost('show_inactive'));
  $new_role = Input::_post('role') == '';
  Forms::checkCells(_("Show inactive:"), 'show_inactive', null, true);
  echo '</tr>';
  Table::end();
  echo "<hr>";
  if (Input::_post('_show_inactive_update')) {
    Ajax::_activate('role');
    JS::_setFocus('role');
  }
  if (Forms::findPostPrefix('_Section')) {
    Ajax::_activate('details');
  }
  Ajax::_start_div('details');
  Table::start('standard');
  Forms::textRow(_("Role name:"), 'name', null, 20, 22);
  Forms::textRow(_("Role description:"), 'description', null, 50, 52);
  Forms::recordStatusListRow(_("Current status:"), 'inactive');
  Table::end(1);
  Table::start('padded grid width40');
  $k   = $j = 0; //row colour counter
  $ext = $sec = $m = -1;
  foreach (sort_areas(User::_i()->Security['areas']) as $area => $parms) {
    // system setup areas are accessable only for site admins i.e.
    // admins of first registered company
    if ((($parms[0] & 0xff00) == SS_SADMIN)) {
      continue;
    }
    $newsec = ($parms[0] >> 8) & 0xff;
    $newext = $parms[0] >> 16;
    if ($newsec != $sec || (($newext != $ext) && ($newsec > 99))) { // features set selection
      $ext = $newext;
      $sec = $newsec;
      $m   = $parms[0] & ~0xff;
      //			if(!isset($security_sections[$m]))
      //			 Event::error(sprintf("Bad section %X:", $m));
      Table::label(
        User::_i()->Security['sections'][$m] . ':',
        Forms::checkbox(null, 'Section' . $m, null, true, _("On/off set of features")),
        "class='left tablehead'",
        "class='tablehead'"
      );
    }
    if (Input::_hasPost('Section' . $m)) {
      Forms::checkRow($parms[1], 'Area' . $parms[0], null, false, '', "class='center'");
      echo '</tr>';
    } else {
      Forms::hidden('Area' . $parms[0]);
    }
  }
  Table::end(1);
  Ajax::_end_div();
  Ajax::_start_div('controls');
  if ($new_role) {
    Forms::submitCenterBegin('Update', _("Update view"), '', null);
    Forms::submitCenterEnd('addupdate', _("Insert New Role"), '', 'default');
  } else {
    Forms::submitCenterBegin('addupdate', _("Save Role"), '', 'default');
    Forms::submit('Update', _("Update view"), true, '', null);
    Forms::submit('clone', _("Clone This Role"), true, '', true);
    Forms::submit('delete', _("Delete This Role"), true, '', true);
    Forms::submitCenterEnd('cancel', _("Cancel"), _("Cancel Edition"), 'cancel');
  }
  Ajax::_end_div();
  Forms::end();
  Page::end();

