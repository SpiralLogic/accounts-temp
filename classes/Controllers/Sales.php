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

  use ADV\App\Controller\Menu;
  use ADV\App\Orders;

  /** **/
  class Sales extends Menu
  {
    public $name = "Sales";
    public $help_context = "&Sales";
    /**

     */
    protected function before() {
      $module = $this->add_module('Create');
      $module->addLeftFunction(_('New &Quotation'), '/sales/order?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_SALESQUOTE, SA_SALESQUOTE);
      $module->addRightFunction(_('New &Order'), '/sales/order?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_SALESORDER, SA_SALESORDER);
      $module->addLeftFunction(_('New D&irect Delivery'), '/sales/order?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_CUSTDELIVERY, SA_SALESDELIVERY);
      $module->addRightFunction(_('New Direct &Invoice'), '/sales/order?' . Orders::ADD . '=0&' . Orders::TYPE . '=' . ST_SALESINVOICE, SA_SALESINVOICE);
      $module = $this->add_module(_('Inquiries'));
      $module->addLeftFunction(_('Search Quotatio&ns'), '/sales/search/orders?' . Orders::TYPE . '=' . ST_SALESQUOTE . '', SA_SALESTRANSVIEW);
      $module->addRightFunction(_('Search Orders'), '/sales/search/orders?' . Orders::TYPE . '=' . ST_SALESORDER, SA_SALESTRANSVIEW);
      $module->addLeftFunction(_('Search Invoices & Deliveries'), '/sales/search/transactions?', SA_SALESTRANSVIEW);
      $module->addRightFunction(_('Search Customer A&llocations'), '/sales/inquiry/customer_allocation_inquiry?', SA_SALESALLOC);
      $module = $this->add_module(_('Process'));
      $module->addLeftFunction(_('&Deliver an Order'), '/sales/search/orders?OutstandingOnly=1', SA_SALESDELIVERY);
      $module->addLeftFunction(_('&Invoice a Delivery'), '/sales/search/deliveries?OutstandingOnly=1', SA_SALESINVOICE);
      $module->addRightFunction(_('Invoice from &Template '), '/sales/search/orders?InvoiceTemplates=Yes', SA_SALESINVOICE);
      $module->addRightFunction(_('&Template Delivery'), '/sales/search/orders?DeliveryTemplates=Yes', SA_SALESDELIVERY);
      $module = $this->add_module(_('Payments'));
      $module->addLeftFunction(_('New Customer &Payment'), '/sales/payment?', SA_SALESPAYMNT);
      $module->addLeftFunction(_('New Customer &Credit'), '/sales/credit?NewCredit=Yes', SA_SALESCREDIT);
      $module->addRightFunction(_('&Allocate Payments and Credits'), '/sales/allocations/customer_allocation_main?', SA_SALESALLOC);
      $module = $this->add_module(_('Reports & Maintainance'));
      $module->addLeftFunction(_('&Customers'), '/contacts/manage/customers', SA_CUSTOMER);
      $module->addRightFunction(_('Manage Recurrent &Invoices'), '/sales/manage/recurrent_invoices?', SA_SRECURRENT);
      $module->addRightFunction(_('Email Customer Statements'), '/sales/email_statements', SA_SALESTRANSVIEW);
      $module->addRightFunction(_('Customer and Sales &Reports'), '/reporting/reports_main?Class=0', SA_SALESTRANSVIEW);
      $module->addRightFunction(_('&Create and Print Recurrent Invoices'), '/sales/create_recurrent_invoices?', SA_SALESINVOICE);
      //  $module->addLeftFunction(_('New Customer &Refund'), '/sales/customer_refunds?', SA_SALESREFUND);
    }
  }
