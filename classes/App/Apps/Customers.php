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
  namespace ADV\App\Apps;

  use ADV\App\Application\Application;

  /** **/
  class Customers extends Application
  {
    public $name = "Debtors";
    public $help_context = "&Customers";
    public function buildMenu() {
      $this->direct = '/contacts/manage/customers';
    }
  }
