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

  use \ADV\App\Controller\Menu;

  /** **/
  class Inventory extends Menu
  {
    public $name = "Inventory";
    public $help_context = "&Inventory";
    /**

     */
    protected function before() {
      $module = $this->add_module(_("Transactions"));
      $module->addLeftFunction(_("Location &Transfers"), "/inventory/transfers?NewTransfer=1", SA_LOCATIONTRANSFER);
      $module->addLeftFunction(_("&Adjustments"), "/inventory/adjustments?NewAdjustment=1", SA_INVENTORYADJUSTMENT);
      $module = $this->add_module(_("Pricing and Costs"));
      $module->addLeftFunction(_("Sales &Pricing"), "/items/manage/prices?", SA_SALESPRICE);
      $module->addLeftFunction(_("Purchase &Pricing"), "/items/manage/purchasing?", SA_PURCHASEPRICING);
      $module->addRightFunction(_("Standard &Costs"), "/inventory/cost_update?", SA_STANDARDCOST);
      $module = $this->add_module(_("Inquiries and Reports"));
      $module->addLeftFunction(_("Item &Movements"), "/inventory/inquiry/stock_movements?", SA_ITEMSTRANSVIEW);
      $module->addLeftFunction(_("Item &Status"), "/inventory/inquiry/stock_status?", SA_ITEMSSTATVIEW);
      $module->addRightFunction(_("Inventory &Reports"), "/reporting/reports_main?Class=2", SA_ITEMSTRANSVIEW);
      $module = $this->add_module(_("Maintenance"));
      $module->addLeftFunction(_("&Items"), "/items/manage/items?", SA_ITEM);
      $module->addLeftFunction(_("&Foreign Item Codes"), "/inventory/manage/item_codes?", SA_FORITEMCODE);
      $module->addLeftFunction(_("Sales &Kits"), "/inventory/manage/sales_kits?", SA_SALESKIT);
      $module->addLeftFunction(_("&Categories"), "/items/manage/categories?", SA_ITEMCATEGORY);
      $module->addLeftFunction(_("Inventory &Locations"), "/items/manage/locations?", SA_INVENTORYLOCATION);
      $module->addRightFunction(_("&Movement Types"), "/inventory/manage/movement_types?", SA_INVENTORYMOVETYPE);
      $module->addRightFunction(_("&Units of Measure"), "/items/manage/units?", SA_UOM);
      $module->addRightFunction(_("&Reorder Levels"), "/items/manage/reorders?", SA_REORDER);
      $module->addRightFunction(_("&Barcodes"), "/inventory/barcodes?", SA_INVENTORYLOCATION);
    }
  }
