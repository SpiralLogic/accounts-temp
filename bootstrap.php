<?php
  /**
   * PHP version 5.4
   * \   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  if (strpos($_SERVER['HTTP_HOST'], 'dev.advaccounts') === 0) {
    header('Location: http://dev.advanced.advancedgroup.com.au' . $_SERVER['REQUEST_URI']);
  } elseif (strpos($_SERVER['HTTP_HOST'], 'advaccounts') !== false) {
    header('Location: http://advanced.advancedgroup.com.au' . $_SERVER['REQUEST_URI']);
  }
  if (!isset($_SERVER['DOCUMENT_URI']) && isset($_SERVER['PATH_INFO'])) {
    $_SERVER['DOCUMENT_URI'] = $_SERVER['PATH_INFO'];
  }
  if (isset($_SERVER['DOCUMENT_URI']) && $_SERVER['DOCUMENT_URI'] !== '/assets.php' && (!isset($_SERVER['QUERY_STRING']) || (strlen($_SERVER['QUERY_STRING']) && substr_compare(
          $_SERVER['QUERY_STRING'], '/profile/', 0, 9, true
        )) !== 0) && extension_loaded('xhprof')
  ) {
    $XHPROF_ROOT = realpath(dirname(__FILE__) . '/xhprof');
    include $XHPROF_ROOT . "/xhprof_lib/config.php";
    include $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
    include $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";
    $ignore = array();
    xhprof_enable(XHPROF_FLAGS_NO_BUILTINS | XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY, array('ignored_functions' => $ignore));
    register_shutdown_function(
      function () {
        register_shutdown_function(
          function () {
            $profiler_namespace = $_SERVER["SERVER_NAME"]; // namespace for your application
            $xhprof_data        = xhprof_disable();
            $xhprof_runs        = new \XHProfRuns_Default();
            $xhprof_runs->save_run($xhprof_data, $profiler_namespace);
          }
        );
      }
    );
  }
  if (function_exists("date_default_timezone_set") and function_exists("date_default_timezone_get")) {
    @date_default_timezone_set(@date_default_timezone_get());
  }
  error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
  ini_set('display_errors', 'On');
  ini_set("ignore_repeated_errors", "On");
  ini_set("log_errors", "On");
  define('E_SUCCESS', E_ALL << 1);
  define('DS', DIRECTORY_SEPARATOR);
  define('ROOT_DOC', __DIR__ . DS);
  define('ROOT_WEB', ROOT_DOC . 'public' . DS);
  define('ROOT_URL', str_ireplace(realpath(__DIR__), '', ROOT_DOC));
  define('PATH_APP', ROOT_DOC . 'classes' . DS . 'App' . DS);
  define('PATH_CORE', ROOT_DOC . 'classes' . DS . 'Core' . DS);
  define('PATH_VENDOR', ROOT_DOC . 'classes' . DS . 'Vendor' . DS);
  define('PATH_CONTROLLERS', ROOT_DOC . 'classes' . DS . 'Controller' . DS);
  define('PATH_VIEW', ROOT_DOC . 'views' . DS);
  define('PATH_COMPANY', ROOT_WEB . 'company' . DS);
  define('PATH_LANG', ROOT_DOC . 'lang' . DS);
  define("REQUEST_METHOD", isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null);
  define("REQUEST_AJAX", (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'));
  define('REQUEST_JSON', (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false));
  define('REQUEST_POST', REQUEST_METHOD === 'POST' ? 'POST' : false);
  define('REQUEST_GET', REQUEST_METHOD === 'GET' ? 'GET' : false);
  define('REQUEST_PUT', REQUEST_METHOD === 'PUT' ? 'PUT' : false);
  define('REQUEST_DELETE', REQUEST_METHOD === 'DELETE' ? 'DELETE' : false);
  define('CRLF', chr(13) . chr(10));
  /** @var $loader */
  $loader = require PATH_CORE . 'Loader.php';
  if ($_SERVER['DOCUMENT_URI'] === '/assets.php') {
    new \ADV\Core\Assets();
    exit;
  }
  if (!function_exists('e')) {
    /**
     * @param $string
     *
     * @return array|string
     */
    function e($string) {
      return \ADV\Core\Security::htmlentities($string);
    }
  }
  if (!function_exists('_')) {
    /**
     * @param $string
     *
     * @return array|string
     */
    function _($string) {
      return $string;
    }
  }
  $bootstrap = function () use ($loader) {
    $app        = new \ADV\App\ADVAccounting($loader);
    $controller = $app->getController();
    if ($controller && file_exists($controller)) {
      include($controller);
    }
  };
  $bootstrap();
  unset($bootstrap);

