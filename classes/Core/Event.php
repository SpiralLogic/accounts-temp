<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core;

  use Exception;

  /** **/
  class Event
  {
    use \ADV\Core\Traits\Hook;

    /** @var \ADV\Core\Cache */
    protected static $Cache;
    /** @var array all objects with methods to be run on shutdown */
    protected static $shutdown_objects = [];
    /**@var bool Whether the request from the browser has finsihed */
    protected static $request_finsihed = false;
    /**@var array Events which occur after browser dissconnect which will be shown on next request */
    protected static $shutdown_events = [];
    /**@var string id for cache handler to store shutdown events */
    protected static $shutdown_events_id;
    /**
     * @param Cache  $cache
     * @param string $presistanceKey
     *
     * @return void
     */
    public static function init(\ADV\Core\Cache $cache, $presistanceKey = '') {
      static::$Cache              = $cache;
      static::$shutdown_events_id = 'shutdown.events.' . $presistanceKey;
      $shutdown_events            = static::$Cache->get(static::$shutdown_events_id);
      static::$Cache->delete(static::$shutdown_events_id);
      if ($shutdown_events) {
        while ($msg = array_pop($shutdown_events)) {
          static::handle($msg[0], $msg[1], $msg[2], $msg[3]);
        }
      }
    }
    /**
     * @static
     *
     * @param string $message Error message
     * @param bool   $log
     *
     * @return bool
     */
    public static function error($message, $log = true) {
      $backtrace = debug_backtrace();
      return static::handle($message, reset($backtrace), E_USER_ERROR, $log);
    }
    /**
     * @static
     *
     * @param string $message
     * @param bool   $log
     *
     * @return bool
     */
    public static function notice($message, $log = true) {
      $backtrace = debug_backtrace();
      return static::handle($message, reset($backtrace), E_USER_NOTICE, $log);
    }
    /**
     * @static
     *
     * @param string $message
     * @param bool   $log
     *
     * @return bool
     */
    public static function success($message, $log = true) {
      $backtrace = debug_backtrace();
      return static::handle($message, reset($backtrace), E_SUCCESS, $log);
    }
    /**
     * @static
     *
     * @param      $message
     * @param bool $log
     *
     * @return bool
     */
    public static function warning($message, $log = true) {
      $backtrace = debug_backtrace();
      return static::handle($message, reset($backtrace), E_USER_WARNING, $log);
    }
    /**
     * @static
     *
     * @param $message
     * @param $source
     * @param $type
     * @param $log
     *
     * @return bool
     */
    protected static function handle($message, $source, $type, $log) {
      if (static::$request_finsihed) {
        static::$shutdown_events[] = [$message, $source, $type, $log];
      } else {
        $message = $message . '||' . $source['file'] . '||' . $source['line'] . '||';
        $message .= $log ? 1 : 0;
        ($type === E_SUCCESS) ? Errors::handler($type, $message) : trigger_error($message, $type);
      }
      return ($type === E_SUCCESS || $type === E_USER_NOTICE);
    }
    /**
     * @static
     *
     * @param string $function
     * @param array  $arguments
     *
     * @internal param $object
     */
    public static function registerShutdown($function = '_shutdown', $arguments = []) {
      Event::registerHook('shutdown', $function, $arguments);
    }
    /**
     * @static
     *
     * @param string $function
     * @param array  $arguments
     *
     * @internal param $object
     */
    public static function registerPreShutdown($function = '_shutdown', $arguments = []) {
      Event::registerHook('pre_shutdown', $function, $arguments);
    }
    /*** @static Shutdown handler */
    public static function shutdown() {
      Errors::process();
      $levels = ob_get_level();
      for ($i = 0; $i < $levels; $i++) {
        ob_end_flush();
      }
      try {
        session_write_close();
      } catch (Exception $e) {
        var_dump($e);
      }
      if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
      }
      static::$request_finsihed = true;
      try {
        static::fireHooks('shutdown');
      } catch (\Exception $e) {
        static::error('Error during post processing: ' . $e->getMessage());
      }
      if (static::$Cache instanceof Cache) {
        static::$Cache->set(static::$shutdown_events_id, static::$shutdown_events);
      }
    }
  }
