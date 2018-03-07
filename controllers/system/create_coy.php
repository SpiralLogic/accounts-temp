<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Create/Update Company"), SA_CREATECOMPANY);
  if (isset($_GET['selected_id'])) {
    $selected_id = $_GET['selected_id'];
  } elseif (isset($_POST['selected_id'])) {
    $selected_id = $_POST['selected_id'];
  } else {
    $selected_id = -1;
  }
  if (isset($_GET['c']) && $_GET['c'] == 'df') {
    handle_delete();
    $selected_id = -1;
  }
  if (isset($_GET['c']) && $_GET['c'] == 'u') {
    if (handle_submit($selected_id)) {
      $selected_id = -1;
    }
  }
  display_companies();
  Display::link_params($_SERVER['DOCUMENT_URI'], _("Create a new company"));
  display_company_edit($selected_id);
  Page::end();
  /**
   * @param $selected_id
   *
   * @return bool
   */
  function check_data(&$selected_id) {
    if ($_POST['name'] == "" || $_POST['host'] == "" || $_POST['dbuser'] == "" || $_POST['dbname'] == "") {
      return false;
    }
    if ($selected_id == -1 && (!isset($_GET['ul']) || $_GET['ul'] != 1)) {
      Event::error(_("When creating a new company, you must provide a Database script file."));
      return false;
    }
    foreach (Config::_getAll('db') as $id => $con) {
      if ($id != $selected_id && $_POST['host'] == $con['host'] && $_POST['dbname'] == $con['dbname']
      ) {
      }
    }
    return true;
  }

  /**
   * @param $selected_id
   *
   * @return bool
   */
  function handle_submit(&$selected_id) {
    $comp_subdirs = Config::_get('company_subdirs');
    $error        = false;
    if (!check_data($selected_id)) {
      return false;
    }
    $id                          = $_GET['id'];
    $connections                 = Config::_getAll('db');
    $new                         = !isset($connections[$id]);
    $db_connection['name']       = $_POST['name'];
    $db_connection['host']       = $_POST['host'];
    $db_connection['dbuser']     = $_POST['dbuser'];
    $db_connection['dbpassword'] = $_POST['dbpassword'];
    $db_connection['dbname']     = $_POST['dbname'];
    Config::_set($id, $db_connection, 'db');
    if ((bool) $_POST['def'] == true) {
      Config::_set('default.company', $id);
    }
    if (isset($_GET['ul']) && $_GET['ul'] == 1) {
      $conn = Config::_get('db.' . $id);
      if (($db = Utils::create($conn)) == 0) {
        Event::error(_("Error creating Database: ") . $conn['dbname'] . _(", Please create it manually"));
        $error = true;
      } else {
        $filename = $_FILES['uploadfile']['tmp_name'];
        if (is_uploaded_file($filename)) {
          if (!Utils::import($filename, $conn, $id)) {
            Event::error(_('Cannot create new company due to bugs in sql file.'));
            $error = true;
          } else {
            if (isset($_POST['admpassword']) && $_POST['admpassword'] != "") {
              DB::_query("UPDATE users set password = '" . md5($_POST['admpassword']) . "' WHERE user_id = 'admin'");
            }
          }
        } else {
          Event::error(_("Error uploading Database Script, please upload it manually"));
          $error = true;
        }
      }
      if ($error) {
        remove_connection($id);
        return false;
      }
    } else {
      if ($_GET['c'] = 'u') {
        $conn = Config::_get('db.' . $id);
        if (($db = Utils::create($conn)) == 0) {
          Event::error(_("Error connecting to Database: ") . $conn['dbname'] . _(", Please correct it"));
        } elseif ($_POST['admpassword'] != "") {
          DB::_query("UPDATE users set password = '" . md5($_POST['admpassword']) . "' WHERE user_id = 'admin'");
        }
      }
    }
    if ($new) {
      create_comp_dirs(PATH_COMPANY . "$id", $comp_subdirs = Config::_get('company_subdirs'));
    }
    Event::success($new ? _('New company has been created.') : _('Company has been updated.'));
    return true;
  }

  function handle_delete() {
    $id = $_GET['id'];
    // First make sure all company directories from the one under removal are writable.
    // Without this after operation we end up with changed per-company owners!
    for ($i = $id; $i < count(Config::_getAll('db')); $i++) {
      if (!is_dir(PATH_COMPANY . DS . $i) || !is_writable(PATH_COMPANY . DS . $i)) {
        Event::error(_('Broken company subdirectories system. You have to remove this company manually.'));
        return;
      }
    }
    // make sure config file is writable
    // rename directory to temporary name to ensure all
    // other subdirectories will have right owners even after
    // unsuccessfull removal.
    $cdir    = PATH_COMPANY . DS . $id;
    $tmpname = PATH_COMPANY . 'old_' . $id;
    if (!@rename($cdir, $tmpname)) {
      Event::error(_('Cannot rename subdirectory to temporary name.'));
      return;
    }
    // 'shift' company directories names
    for ($i = $id + 1; $i < count(Config::_getAll('db')); $i++) {
      if (!rename(PATH_COMPANY . DS . $i, PATH_COMPANY . DS . ($i - 1))) {
        Event::error(_("Cannot rename company subdirectory"));
        return;
      }
    }
    $err = remove_connection($id);
    if ($err == 0) {
      Event::error(_("Error removing Database: ") . _(", please remove it manually"));
    }
    if (Config::_get('default.company') == $id) {
      Config::_set('default.company', 1);
    }
    // finally remove renamed company directory
    @Files::flushDir($tmpname, true);
    if (!@rmdir($tmpname)) {
      Event::error(_("Cannot remove temporary renamed company data directory ") . $tmpname);
      return;
    }
    Event::notice(_("Selected company as been deleted"));
  }

  function display_companies() {
    $coyno = User::_i()->company;
    echo "
            <script language='javascript'>
            function deleteCompany(id)
            {
                if (!confirm('" . _("Are you sure you want to delete company no. ") . "'+id))

                    return
                document.location.replace('create_coy.php?c=df&id='+id)
            }
            </script>";
    Table::start('padded grid');
    $th = array(
      _("Company"),
      _("Database Host"),
      _("Database User"),
      _("Database Name"),
      _("Table Pref"),
      _("Default"),
      "",
      ""
    );
    Table::header($th);
    $k    = 0;
    $conn = Config::_getAll('db');
    $n    = count($conn);
    for ($i = 0; $i < $n; $i++) {
      if ($i == Config::_get('default.company')) {
        $what = _("Yes");
      } else {
        $what = _("No");
      }
      if ($i == $coyno) {
        echo "<tr class='stockmankobg'>";
      } else {
      }
      Cell::label($conn[$i]['name']);
      Cell::label($conn[$i]['host']);
      Cell::label($conn[$i]['dbuser']);
      Cell::label($conn[$i]['dbname']);
      Cell::label($what);
      $edit   = _("Edit");
      $delete = _("Delete");
      if (User::_graphic_links()) {
        $edit   = Forms::setIcon(ICON_EDIT, $edit);
        $delete = Forms::setIcon(ICON_DELETE, $delete);
      }
      Cell::label("<a href='" . $_SERVER['DOCUMENT_URI'] . "?selected_id=$i'>$edit</a>");
      Cell::label($i == $coyno ? '' : "<a href=''>$delete</a>");
      echo '</tr>';
    }
    Table::end();
    Event::warning(_("The marked company is the current company which cannot be deleted."), 0, 0, "class='currentfg'");
  }

  /**
   * @param $selected_id
   */
  function display_company_edit($selected_id) {
    if ($selected_id != -1) {
      $n = $selected_id;
    } else {
      $n = count(Config::_getAll('db'));
    }
    Forms::start(true);
    echo "
            <script language='javascript'>
            function updateCompany()
            {
                if (document.forms[0].uploadfile.value!='' && document.forms[0].dbname.value!='') {
                    document.forms[0].action='create_coy.php?c=u&ul=1&id=" . $n . "&fn=' + document.forms[0].uploadfile.value
                } else {
                    document.forms[0].action='create_coy.php?c=u&id=" . $n . "'
                }
                document.forms[0].Forms::submit()
            }
            </script>";
    Table::start('standard');
    if ($selected_id != -1) {
      $conn                = Config::_get('db.' . $selected_id);
      $_POST['name']       = $conn['name'];
      $_POST['host']       = $conn['host'];
      $_POST['dbuser']     = $conn['dbuser'];
      $_POST['dbpassword'] = $conn['dbpassword'];
      $_POST['dbname']     = $conn['dbname'];
      if ($selected_id == Config::_get('default.company')) {
        $_POST['def'] = true;
      } else {
        $_POST['def'] = false;
      }
      $_POST['dbcreate'] = false;
      Forms::hidden('selected_id', $selected_id);
      Forms::hidden('dbpassword', $_POST['dbpassword']);
    }
    Forms::textRowEx(_("Company"), 'name', 30);
    Forms::textRowEx(_("Host"), 'host', 30);
    Forms::textRowEx(_("Database User"), 'dbuser', 30);
    if ($selected_id == -1) {
      Forms::textRowEx(_("Database Password"), 'dbpassword', 30);
    }
    Forms::textRowEx(_("Database Name"), 'dbname', 30);
    Forms::yesnoListRow(_("Default"), 'def', null, "", "", false);
    Forms::fileRow(_("Database Script"), "uploadfile");
    Forms::textRowEx(_("New script Admin Password"), 'admpassword', 20);
    Table::end();
    Event::warning(_("Choose from Database scripts in SQL folder. No Database is created without a script."), 0, 1);
    echo "<div class='center'><input type='button' style='width:150px' value='" . _("Save") . "'></div>";
    Forms::end();
  }

