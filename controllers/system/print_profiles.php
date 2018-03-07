<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Printing Profiles"), SA_PRINTPROFILE);
  $selected_id = Input::_post('profile_id', null, '');
  if (Input::_post('submit')) {
    $error = 0;
    if ($_POST['profile_id'] == '' && empty($_POST['name'])) {
      $error = 1;
      Event::error(_("Printing profile name cannot be empty."));
      JS::_setFocus('name');
    }
    if (!$error) {
      $prof = array('' => Input::_post('Prn')); // store default value/profile name
      foreach (get_reports() as $rep => $descr) {
        $val        = Input::_post('Prn' . $rep);
        $prof[$rep] = $val;
      }
      if ($_POST['profile_id'] == '') {
        $_POST['profile_id'] = Input::_post('name');
      }
      Printer::update_profile($_POST['profile_id'], $prof);
      if ($selected_id == '') {
        Event::success(_('New printing profile has been created'));
        clear_form($selected_id);
      } else {
        Event::success(_('Printing profile has been updated'));
      }
    }
  }
  if (Input::_post('delete')) {
    if (!check_delete(Input::_post('name'))) {
      Printer::delete_profile($selected_id);
      Event::notice(_('Selected printing profile has been deleted'));
      clear_form($selected_id);
    }
  }
  if (Input::_post('_profile_id_update')) {
    Ajax::_activate('_page_body');
  }
  Forms::start();
  Table::start();
  Reports_UI::print_profiles_row(_('Select printing profile') . ':', 'profile_id', null, _('New printing profile'), true);
  Table::end();
  echo '<hr>';
  Table::start();
  if (Input::_post('profile_id') == '') {
    Forms::textRow(_("Printing Profile Name") . ':', 'name', null, 30, 30);
  } else {
    Cell::labelled(_("Printing Profile Name") . ':', Input::_post('profile_id'));
  }
  Table::end(1);
  $result = Printer::get_profile(Input::_post('profile_id'));
  $prints = [];
  while ($myrow = DB::_fetch($result)) {
    $prints[$myrow['report']] = $myrow['printer'];
  }
  Table::start('padded grid');
  $th = array(_("Report Id"), _("Description"), _("Printer"));
  Table::header($th);
  $k    = 0;
  $unkn = 0;
  foreach (get_reports() as $rep => $descr) {
    Cell::label($rep == '' ? '-' : $rep, 'class=center');
    Cell::label($descr == '' ? '???<sup>1)</sup>' : _($descr));
    $_POST['Prn' . $rep] = isset($prints[$rep]) ? $prints[$rep] : '';
    echo '<td>';
    echo Reports_UI::printers('Prn' . $rep, null, $rep == '' ? _('Browser support') : _('Default'));
    echo '</td>';
    if ($descr == '') {
      $unkn = 1;
    }
    echo '</tr>';
  }
  Table::end();
  if ($unkn) {
    Event::warning('<sup>1)</sup>&nbsp;-&nbsp;' . _("no title was found in this report definition file."), 0, 1, '');
  } else {
    echo '<br>';
  }
  Ajax::_start_div('controls');
  if (Input::_post('profile_id') == '') {
    Forms::submitCenter('submit', _("Add New Profile"), true, '', 'default');
  } else {
    Forms::submitCenterBegin('submit', _("Update Profile"), _('Update printer profile'), 'default');
    Forms::submitCenterEnd('delete', _("Delete Profile"), _('Delete printer profile (only if not used by any user)'), true);
  }
  Ajax::_end_div();
  Forms::end();
  Page::end();
  // Returns array of defined reports
  //
  function get_reports() {
    if (Config::_get('debug.enabled') || !isset($_SESSION['reports'])) {
      // to save time, store in session.
      $paths   = array(
        ROOT_DOC . 'controllers' . DS . 'reporting' . DS,
        PATH_COMPANY . 'reporting/'
      );
      $reports = array('' => _('Default printing destination'));
      foreach ($paths as $dirno => $path) {
        $repdir = opendir($path);
        while (false !== ($fname = readdir($repdir))) {
          // reports have filenames in form rep(repid).php
          // where repid must contain at least one digit (reports_main.php is not ;)
          if (is_file($path . $fname) //				&& preg_match('/.*[^0-9]([0-9]+)[.]php/', $fname, $match))
            && preg_match('/rep(.*[0-9]+.*)[.]php/', $fname, $match)
          ) {
            $repno = $match[1];
            $title = '';
            $line  = file_get_contents($path . $fname);
            if (preg_match('/.*(ADVReport\()\s*_\([\'"]([^\'"]*)/', $line, $match)) {
              $title = trim($match[2]);
            } else // for any 3rd party printouts without ADVReport() class use
            {
              if (preg_match('/.*(\$Title).*[\'"](.*)[\'"].+/', $line, $match)) {
                $title = trim($match[2]);
              }
            }
            $reports[$repno] = $title;
          }
        }
        closedir();
      }
      ksort($reports);
      $_SESSION['reports'] = $reports;
    }
    return $_SESSION['reports'];
  }

  /**
   * @param $selected_id
   */
  function clear_form(&$selected_id) {
    $selected_id   = '';
    $_POST['name'] = '';
    Ajax::_activate('_page_body');
  }

  /**
   * @param $name
   *
   * @return int
   */
  function check_delete($name) {
    // check if selected profile is used by any user
    if ($name == '') {
      return 0;
    } // cannot delete system default profile
    $sql = "SELECT * FROM users WHERE print_profile=" . DB::_escape($name);
    $res = DB::_query($sql, 'cannot check printing profile usage');
    return DB::_numRows($res);
  }

