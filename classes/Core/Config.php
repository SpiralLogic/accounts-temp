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

  use ADV\Core\Cache;

  /**
   * @method static _get($var, $default = false)
   * @method static _getAll($var, $default = false)
   * @method static _removeAll()
   * @method static Config i()
   */
  class Config
  {
    use Traits\StaticAccess;

    /***
     * @var array|null
     */
    protected $_vars = null;
    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache) {
      $this->Cache = $cache;
      if (isset($_GET['reload_config'])) {
        $this->Cache->delete('config');
        header('Location: /');
      } elseif ($this->_vars === null) {
        $this->_vars = $this->Cache->get('config');
      }
      if (!$this->_vars) {
        $this->load();
      }
    }
    /**
     * @static
     *
     * @param string $group
     *
     * @throws \RuntimeException
     * @return mixed
     */
    protected function load($group = 'config') {
      if (is_array($group)) {
        $group_name = implode('.', $group);
        $group_file = array_pop($group) . '.php';
        $group_path = implode(DS, $group);
        $file       = ROOT_DOC . "config" . $group_path . DS . $group_file;
      } else {
        $file       = ROOT_DOC . "config" . DS . $group . '.php';
        $group_name = $group;
      }
      if ($this->_vars && array_key_exists($group_name, $this->_vars)) {
        return true;
      }
      if (!file_exists($file)) {
        throw new \RuntimeException("There is no file for config: " . $file);
      }
      /** @noinspection PhpIncludeInspection */
      $this->_vars[$group_name] = include($file);
      Event::registerShutdown([$this, '_shutdown']);
      return true;
    }
    /**
     * @static
     *
     * @param   $var
     * @param   $value
     *
     * @internal param string $group
     * @return mixed
     */
    public function set($var, $value) {
      if (!strstr($var, '.')) {
        $var = 'config.' . $var;
      }
      $group_array               = explode('.', $var);
      $var                       = array_pop($group_array);
      $group                     = implode('.', $group_array);
      $this->_vars[$group][$var] = $value;
      return $value;
    }
    /***
     * @static
     *
     * @param string  $var
     * @param mixed   $default
     *
     * @internal param null $array_key
     * @return Array|mixed
     */
    public function get($var, $default = false) {
      if (!strstr($var, '.')) {
        $var = 'config.' . $var;
      }
      $group_array = explode('.', $var);
      $var         = array_pop($group_array);
      $group       = implode('.', $group_array);
      (isset($this->_vars[$group], $this->_vars[$group][$var])) or $this->load($group_array);
      if (!isset($this->_vars[$group][$var])) {
        return $default;
      }
      return $this->_vars[$group][$var];
    }
    /**
     * @static
     *
     * @param string $group
     * @param array  $default
     *
     * @return mixed
     * @return array
     * @return array
     */
    public function getAll($group = 'config', $default = []) {
      if (!isset($this->_vars[$group]) && $this->load($group) === false) {
        return $default;
      }
      return $this->_vars[$group];
    }
    /**
     * @return void
     */
    public function reset() {
      $this->removeAll();
      $this->load();
    }
    /**
     * @static
     *
     * @param        $var
     * @param string $group
     * @param string $group
     */
    public function remove($var, $group = 'config') {
      if (array_key_exists($var, $this->_vars[$group])) {
        unset($this->_vars[$group][$var]);
      }
    }
    /**
     * @static
     */
    public function removeAll() {
      $this->Cache->delete('config');
      $this->_vars = [];
    }
    /**
     * @return mixed
     */
    public function shutdown() {
      if (isset($_GET['reload_config'])) {
        Event::notice('Config reloaded');
      }
      return $this->Cache->set('config', $this->_vars);
    }
  }
