<?php  /**
 * PHP version 5.4
 *
 * @category  PHP
 * @package   adv.accounts.core
 * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
 * @copyright 2010 - 2012
 * @link      http://www.advancedgroup.com.au
 **/
  namespace ADV\Core;

  /** **/
  class HookException extends \Exception
  {
  }

  /** **/
  class Hook
  {
    /** @var array * */
    protected $hooks = [];
    /**
     * @param       $name
     * @param       $callback
     * @param array $arguments
     *
     * @return bool
     */
    public function add($name, $callback, $arguments = []) {
      if (is_array($callback) && is_object($callback[0])) {
        $callback_id = spl_object_hash($callback[0]) . $callback[1] . serialize($arguments);
      } elseif (is_string($callback)) {
        $callback_id = $callback . serialize($arguments);
      } else {
        $callback_id = count($this->hooks) . serialize($arguments);
      }
      if (!isset($this->hooks[$name][$callback_id])) {
        return $this->hooks[$name][$callback_id] = [$callback, (array)$arguments];
      }
      return false;
    }
    /**
     * @param $name
     *
     * @return array
     */
    public function getCallbacks($name) {
      return isset($this->hooks[$name]) ? $this->hooks[$name] : [];
    }
    /**
     * @param $name
     */
    public function fire($name) {
      foreach ($this->getCallbacks($name) as $callback) {
        if (!is_callable($callback[0])) {
          continue;
        }
        call_user_func_array($callback[0], $callback[1]);
      }
    }
  }
