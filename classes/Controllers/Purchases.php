<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      22/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers;

  use \ADV\App\Controller\Menu;

  /** **/
  class Purchases extends Menu
  {
    public $name = "Purchases";
    public $help_context = "&Purchases";
    /**

     */
    protected function before() {
      $module = $this->add_module(_("Purchases"));
      $module->addLeftFunction(_("New Purchase &Order"), "/purchases/order?NewOrder=Yes", SA_PURCHASEORDER);
      $module->addLeftFunction(_("New &Invoice"), "/purchases/invoice?New=1", SA_SUPPLIERINVOICE);
      $module->addLeftFunction(_("New &Credit"), "/purchases/credit?New=1", SA_SUPPLIERCREDIT);
      $module->addRightFunction(_("New Payment"), "/purchases/payment?", SA_SUPPLIERPAYMNT);
      $module->addRightFunction(_("&Allocate Payments and Credits"), "/purchases/allocations/supplier_allocation_main?", SA_SUPPLIERALLOC);
      $module = $this->add_module(_("Inquiries"));
      $module->addLeftFunction(_("Search Completed Orders"), "/purchases/search/completed?", SA_SUPPTRANSVIEW);
      $module->addLeftFunction(_("Search &Outstanding Orders"), "/purchases/search/orders?", SA_SUPPTRANSVIEW);
      $module->addRightFunction(_("Search Transactions"), "/purchases/search/transactions?", SA_SUPPTRANSVIEW);
      $module->addRightFunction(_("Search Allocations"), "/purchases/search/allocations?", SA_SUPPLIERALLOC);
      $module = $this->add_module(_("Maintenance & Reports"));
      $module->addLeftFunction(_("&Suppliers"), "/contacts/manage/suppliers?", SA_SUPPLIER);
      $module->addRightFunction(_("Supplier and Purchasing &Reports"), "/reporting/reports_main?Class=1", SA_SUPPTRANSVIEW);
    }
  }
