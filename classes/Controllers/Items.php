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
  namespace ADV\Controllers;

  use \ADV\App\Controller\Menu;

  /** **/
  class Items extends Menu
  {
    public $name = "Items";
    public $help_context = "&Items";
    protected function before() {
      $this->direct = '/items/manage/items';
    }
  }
