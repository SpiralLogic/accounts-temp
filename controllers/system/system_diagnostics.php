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
  use ADV\Core\Table;
  use ADV\Core\Config;
  use ADV\Core\DB\DB;
  use ADV\App\Page;
  use ADV\Core\Cell;

  Page::start(_($help_context = "System Diagnostics"), SA_SETUPCOMPANY);
  // Type of requirement for positive test result
  $test_level   = array(
    0 => _('Info'),
    1 => _('Optional'),
    2 => _('Recomended'),
    3 => _('Required ')
  );
  $system_tests = array(
    'tst_mysql',
    'tst_php',
    'tst_server',
    'tst_system',
    'tst_browser',
    'tst_debug',
    'tst_logging',
    'tst_subdirs',
    'tst_langs',
    'tst_tmpdir',
    'tst_sessionhandler',
    'tst_sessionpath',
    'tst_config',
    'tst_extconfig'
  );
  Table::start('padded grid width90');
  $th = array(_("Test"), _('Test type'), _("Value"), _("Comments"));
  Table::header($th);
  $k = 0; //row colour counter
  foreach ($system_tests as $test) {
    $result = call_user_func($test);
    if (!$result) {
      continue;
    }
    Cell::label($result['descr']);
    Cell::label($test_level[$result['type']]);
    $res = isset($result['test']) ? implode('<br>', (array)$result['test']) : $result['test'];
    Cell::label($res);
    $comm  = isset($result['comments']) ? implode('<br>', (array)$result['comments']) : '';
    $color = ($result['result'] ? 'green' : ($result['type'] == 3 ? 'red' : ($result['type'] == 2 ? 'orange' : 'green')));
    Cell::label(
      "<span style='color:$color'>" . ($result['result'] ? _('Ok') : '<span class="bold">' . $comm . '</span>') . '</span>'
    );
    echo '</tr>';
  }
  Table::end();
  Page::end();
  /**
   * @return array
   */
  function tst_mysql() {
    $test['descr']    = _('MySQL version') . ' >5.0';
    $test['type']     = 3;
    $test['test']     = DB::_getAttribute(PDO::ATTR_SERVER_VERSION);
    $test['result']   = $test['test'] > '5.0';
    $test['comments'] = _('Upgrade MySQL server to version at least 5.1');
    return $test;
  }

  /**
   * @return array
   */
  function tst_php() {
    $test['descr']    = _('PHP version') . ' >5.4';
    $test['type']     = 3;
    $test['test']     = phpversion();
    $test['result']   = $test['test'] > '5.3';
    $test['comments'] = _('Upgrade PHP to version at least 5.4');
    return $test;
  }

  /**
   * @return array
   */
  function tst_system() {
    $test['descr']  = _('Server system');
    $test['type']   = 0;
    $test['test']   = PHP_OS;
    $test['result'] = true;
    return $test;
  }

  /**
   * @return array
   */
  function tst_sessionpath() {
    $test['descr']  = _('Session save path');
    $test['type']   = 0;
    $test['test']   = session_save_path();
    $test['result'] = true;
    return $test;
  }

  /**
   * @return array
   */
  function tst_sessionhandler() {
    $test['descr']    = _('Session handler');
    $test['type']     = 2;
    $test['test']     = session_module_name();
    $test['result']   = ($test['test'] == 'memcached');
    $test['comments'] = 'For better performance Memcached is recommended.';
    return $test;
  }

  /**
   * @return array
   */
  function tst_browser() {
    $test['descr']    = _('Browser type');
    $test['type']     = 0;
    $test['test']     = $_SERVER['HTTP_USER_AGENT'];
    $test['result']   = true;
    $test['comments'] = _('Any browser is supported');
    return $test;
  }

  /**
   * @return array
   */
  function tst_server() {
    $test['descr']    = _('Http server type');
    $test['test']     = $_SERVER['SERVER_SOFTWARE'];
    $test['type']     = 0;
    $test['result']   = true;
    $test['comments'] = _('Any server is supported');
    return $test;
  }

  /**
   * @return array
   */
  function tst_debug() {
    $test['descr']    = _('Debugging mode');
    $test['type']     = 0;
    $test['test']     = Config::_get('debug.enabled') ? _("Yes") : _("No");
    $test['result']   = Config::_get('debug.enabled') != 0;
    $test['comments'] = _('To switch debugging on set true in config.php file');
    return $test;
  }

  /**
   * @return array
   */
  function tst_logging() {
    $test['descr'] = _('Error logging');
    $test['type']  = 2;
    // if error lgging is on, but log file does not exists try write
    if (Config::_get('debug.log_file') && !is_file(Config::_get('debug.log_file'))) {
      fclose(fopen(Config::_get('debug.log_file'), 'w'));
    }
    $test['result'] = Config::_get('debug.log_file') != '' && is_writable(Config::_get('debug.log_file'));
    $test['test']   = Config::_get('debug.log_file') == '' ? _("Disabled") : Config::_get('debug.log_file');
    if (Config::_get('debug.log_file') == '') {
      $test['comments'] = _('To switch error logging set $error_logging in config.php file');
    } else {
      if (!is_writable(Config::_get('debug.log_file'))) {
        $test['comments'] = _('Log file is not writeable');
      }
    }
    return $test;
  }

  //
  //	Installed ADV database structure version
  //
  /**
   * @return array
   */
  function tst_subdirs() {
    $comp_subdirs  = array('images', 'pdf_files', 'backup', 'js_cache');
    $test['descr'] = _('Company subdirectories consistency');
    $test['type']  = 3;
    $test['test']  = array(PATH_COMPANY . '*');
    foreach ($comp_subdirs as $sub) {
      $test['test'][] = PATH_COMPANY . '*/' . $sub;
    }
    $test['result'] = true;
    if (!is_dir(PATH_COMPANY) || !is_writable(PATH_COMPANY)) {
      $test['result']     = false;
      $test['comments'][] = sprintf(_("'%s' is not writeable"), PATH_COMPANY);
      return $test;
    }
    ;
    foreach (Config::_getAll('db') as $n => $comp) {
      $path = PATH_COMPANY . "";
      if (!is_dir($path) || !is_writable($path)) {
        $test['result']     = false;
        $test['comments'][] = sprintf(_("'%s' is not writeable"), $path);
        continue;
      }
      ;
      foreach ($comp_subdirs as $sub) {
        $spath = $path . '/' . $sub;
        if (!is_dir($spath) || !is_writable($spath)) {
          $test['result']     = false;
          $test['comments'][] = sprintf(_("'%s' is not writeable"), $spath);
        } else {
          $dir = opendir($spath);
          while (false !== ($fname = readdir($dir))) {
            // check only *.js files. Manually installed package can contain other
            // non-writable files which are non-crucial for normal operations
            if (preg_match('/.*(\.js)/', $fname) && !is_writable("$spath/$fname")) {
              $test['result']     = false;
              $test['comments'][] = sprintf(_("'%s' is not writeable"), "$spath/$fname");
            }
          }
        }
      }
    }
    return $test;
  }

  /**
   * @return array
   */
  function tst_tmpdir() {
    $test['descr']      = _('Temporary directory');
    $test['type']       = 3;
    $test['test']       = ROOT_DOC . 'tmp';
    $test['result']     = is_dir($test['test']) && is_writable($test['test']);
    $test['comments'][] = sprintf(_("'%s' is not writeable"), $test['test']);
    return $test;
  }

  /**
   * @return array
   */
  function tst_langs() {
    $test['descr']    = _('Language configuration consistency');
    $test['type']     = 3;
    $test['result']   = true;
    $test['comments'] = [];
    $old              = setlocale(LC_MESSAGES, '0');
    $langs            = [];
    foreach (Config::_get('languages.installed') as $language) {
      $langs[] = $language['code'];
      if ($language['code'] == 'en_AU') {
        continue;
      } // native ADV language
      if (!setlocale(LC_MESSAGES, $language['code'] . "." . $language['encoding'])) {
        $test['result']     = false;
        $test['comments'][] = sprintf(_('Missing system locale: %s'), $language['code'] . "." . $language['encoding']);
      }
      ;
    }
    setlocale(LC_MESSAGES, $old);
    $test['test'] = $langs;
    return $test;
  }

  /**
   * @return array
   */
  function tst_config() {
    $test['descr']      = _('Main config file');
    $test['type']       = 2;
    $test['test']       = ROOT_DOC . 'config' . DS . 'config.php';
    $test['result']     = is_file($test['test']) && !is_writable($test['test']);
    $test['comments'][] = sprintf(_("'%s' file should be read-only"), $test['test']);
    return $test;
  }



