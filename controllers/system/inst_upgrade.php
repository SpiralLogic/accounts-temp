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
  use ADV\Core\Input\Input;
  use ADV\Core\DB\DB;
  use ADV\Core\Cell;
  use ADV\Core\Table;
  use ADV\App\Forms;
  use ADV\Core\Ajax;
  use ADV\App\UserPrefs;
  use ADV\App\User;
  use ADV\App\Users;
  use ADV\Core\DB\Utils;
  use ADV\Core\Event;
  use ADV\Core\Config;
  use ADV\App\Page;

  Page::start(_($help_context = "Software Upgrade"), SA_SOFTWAREUPGRADE);
  //
  //	Checks $field existence in $table with given field $properties
  //	$table - table name without prefix
  // $field - optional field name
  // $properties - optional properties of field defined by MySQL:
  //		'Type', 'Null', 'Key', 'Default', 'Extra'
  //
  $installers = get_installers();
  if (Input::_post('Upgrade')) {
    $ret = true;
    foreach (Config::_getAll('db') as $conn) {
      // connect to database
      if (!($db = db_open($conn))) {
        Event::error(_("Cannot connect to database for company") . " '" . $conn['name'] . "'");
        continue;
      }
      // create security backup
      Utils::backup($conn, 'no', 'Security backup before upgrade');
      // apply all upgrade data
      foreach ($installers as $i => $inst) {
        $ret = upgrade_step($i, $conn);
        if (!$ret) {
          Event::error(
            sprintf(_("Database upgrade to version %s failed for company '%s'."), $inst->version, $conn['name']) . '<br>' . _(
              'You should restore company database from latest backup file'
            )
          );
        }
      }
      // 		db_close($conn); ?
      if (!$ret) {
        break;
      }
    }
    if ($ret) { // re-read the prefs
      $user             = Users::get_by_login(User::_i()->username);
      User::_i()->prefs = new UserPrefs($user);
      Event::success(_('All companies data has been successfully updated'));
    }
    Ajax::_activate('_page_body');
  }
  Forms::start();
  Table::start('padded grid');
  $th = array(
    _("Version"),
    _("Description"),
    _("Sql file"),
    _("Install"),
    _("Force upgrade")
  );
  Table::header($th);
  $k       = 0; //row colour counter
  $partial = 0;
  foreach ($installers as $i => $inst) {
    echo '<tr>';
    Cell::label($inst->version);
    Cell::label($inst->description);
    Cell::label($inst->sql ? $inst->sql : '<i>' . _('None') . '</i>', 'class=center');
    // this is checked only for first (site admin) company,
    // but in fact we should always upgrade all data sets after
    // source upgrade.
    $check = $inst->installed('');
    if ($check === true) {
      Cell::label(_("Installed"));
    } else {
      if (!$check) {
        Forms::checkCells(null, 'install_' . $i, 0);
      } else {
        Cell::label("<span class=redfg>" . sprintf(_("Partially installed (%s)"), $check) . "</span>");
        $partial++;
      }
    }
    Forms::checkCells(null, 'force_' . $i, 0);
    echo '</tr>';
  }
  Table::end(1);
  if ($partial != 0) {
    Event::warning(
      _(
        "Database upgrades marked as partially installed cannot be installed automatically.
You have to clean database manually to enable them, or try to perform forced upgrade."
      )
    );
    echo "<br>";
  }
  Forms::submitCenter('Upgrade', _('Upgrade system'), true, _('Save database and perform upgrade'), 'process');
  Forms::end();
  Page::end();
  /**
   * @param      $pref
   * @param      $table
   * @param null $field
   * @param null $properties
   *
   * @return int
   */
  function check_table($pref, $table, $field = null, $properties = null) {
    $tables = @DB::_query("SHOW TABLES LIKE '" . $pref . $table . "'");
    if (!DB::_numRows($tables)) {
      return 1;
    } // no such table or error
    $fields = @DB::_query("SHOW COLUMNS FROM " . $pref . $table);
    if (!isset($field)) {
      return 0;
    } // table exists
    while ($row = DB::_fetchAssoc($fields)) {
      if ($row['Field'] == $field) {
        if (!isset($properties)) {
          return 0;
        }
        foreach ($properties as $property => $value) {
          if ($row[$property] != $value) {
            return 3;
          } // failed type/length check
        }
        return 0; // property check ok.
      }
    }
    return 2; // field not found
  }

  //
  //	Creates table of installer objects sorted by version.
  //
  /**
   * @return array
   */
  function get_installers() {
    $patchdir = ROOT_DOC . "sql/";
    $upgrades = [];
    $datadir  = @opendir($patchdir);
    if ($datadir) {
      while (false !== ($fname = readdir($datadir))) { // check all php files but index.php
        if (!is_dir($patchdir . $fname) && ($fname != 'index.php') && stristr($fname, '.php') != false
        ) {
          unset($install);
          include_once($patchdir . $fname);
          if (isset($install)) // add installer if found
          {
            $upgrades[$install->version] = $install;
          }
        }
      }
      ksort($upgrades); // sort by file name
      $upgrades = array_values($upgrades);
    }
    return $upgrades;
  }

  //
  //	Apply one differential data set.
  //
  /**
   * @param $index
   * @param $conn
   *
   * @return bool
   */
  function upgrade_step($index, $conn) {
    global $installers;
    $inst  = $installers[$index];
    $ret   = true;
    $force = Input::_post('force_' . $index);
    if ($force || Input::_post('install_' . $index)) {
      $state = $inst->installed();
      if (!$state || $force) {
        if (!$inst->pre_check($force)) {
          return false;
        }
        $sql = $inst->sql;
        if ($sql != '') {
          $ret &= Utils::import(ROOT_DOC . 'upgrade' . DS . 'sql' . DS . $sql, $conn, $force);
        }
        $ret &= $inst->install($force);
      } else {
        if ($state !== true) {
          Event::error(
            _("Upgrade cannot be done because database has been already partially upgraded. Please downgrade database to clean previous version or try forced upgrade.")
          );
          $ret = false;
        }
      }
    }
    return $ret;
  }

  /**
   * @param $conn
   *
   * @return bool|resource
   */
  function db_open($conn) {
    $db = mysql_connect($conn["host"], $conn["dbuser"], $conn["dbpassword"]);
    if (!$db) {
      return false;
    }
    if (!mysql_select_db($conn["dbname"], $db)) {
      return false;
    }
    return $db;
  }


