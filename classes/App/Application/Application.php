<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /** **/
  namespace ADV\App\Application;

  /** **/
  abstract class Application
  {
    /** @var */
    public $id;
    /** @var */
    public $name;
    /** @var bool * */
    public $direct = false;
    /** @var */
    public $help_context;
    /** @var array * */
    public $modules;
    /** @var bool * */
    public $enabled = true;
    /**
     * @internal param $id
     * @internal param $name
     * @internal param bool $enabled
     */
    public function __construct() {
      $this->id           = strtolower($this->name);
      $this->name         = $this->help_context ? : $this->name;
      $this->help_context = _($this->name);
      $this->modules      = [];
      $this->buildMenu();
    }
    abstract function buildMenu();
    /**
     * @param      $name
     * @param null $icon
     *
     * @return Module
     */
    public function add_module($name, $icon = null) {
      $module          = new Module($name, $icon);
      $this->modules[] = $module;
      return $module;
    }
  }

