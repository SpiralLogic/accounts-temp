<?php
  use ADV\App\Page;

  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/

  Page::start(_($help_context = "Websales to Accounting"), SA_OPEN);

  $test = new \Modules\Volusion();
  $test->doWebsales();
  Page::end();

