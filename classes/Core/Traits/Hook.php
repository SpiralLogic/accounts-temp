<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.core.traits
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\Traits;

  use ADV\Core\Event;

  /** **/
  trait Hook
  {
    /** @var \ADV\Core\Hook $hooks */
    protected static $hooks = null;
    /**
     * @static
     *
     * @param                         $hook
     * @param   \Callable|\Closure    $function
     * @param array                   $arguments
     *
     * @internal param $object
     * @return bool
     */
    public static function registerHook($hook, $function = null, $arguments = []) {
      if (static::$hooks === null) {
        static::$hooks = new \ADV\Core\Hook();
      }
      if (!is_callable($function)) {
        return Event::error('Hook is not callable!');
      }
      static::$hooks->add($hook, $function, $arguments);
    }
    /**
     * @static
     *
     * @param $hook
     */
    public static function fireHooks($hook) {
      if (static::$hooks) {
        static::$hooks->fire($hook);
      }
    }
  }
