<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      22/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers;

  use ADV\App\Controller\Action;
  use ADV\App\DB\Collection;
  use ADV\App\Item\Purchase;

  /** **/
  class Index2 extends Action
  {
    public $name = "Banking";
    public $help_context = "&Banking";
    /**

     */
    protected function index() {
      $prices = new Collection(new Purchase(), ['creditor_id']);
      $prices->getAll(2866);
      $changes = ['ms' => ['price' => 999]];
      $prices->setFromArray('stock_id', $changes, false);
      var_dump($prices);
    }
  }

