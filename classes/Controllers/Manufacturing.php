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
  class Manufacturing extends Menu
  {
    public $name = "Manufacturing";
    public $help_context = "&Manufacturing";
    /**

     */
    protected function before() {
      $module = $this->add_module(_("Transactions"));
      $module->addLeftFunction(_("Work &Order"), "/manufacturing/work_order_entry?", SA_WORKORDERENTRY);
      $module->addLeftFunction(_("&Outstanding Work Orders"), "/manufacturing/search_work_orders?outstanding_only=1", SA_MANUFTRANSVIEW);
      $module = $this->add_module(_("Inquiries and Reports"));
      $module->addLeftFunction(_("Costed Bill Of Material Inquiry"), "/manufacturing/inquiry/bom_cost?", SA_WORKORDERCOST);
      $module->addLeftFunction(_("Inventory Item Where Used &Inquiry"), "/manufacturing/inquiry/where_used?", SA_WORKORDERANALYTIC);
      $module->addLeftFunction(_("Work Order &Inquiry"), "/manufacturing/search_work_orders?", SA_MANUFTRANSVIEW);
      $module->addRightFunction(_("Manufacturing &Reports"), "/reporting/reports_main?Class=3", SA_MANUFTRANSVIEW);
      $module = $this->add_module(_("Maintenance"));
      $module->addLeftFunction(_("&Bills Of Material"), "/manufacturing/manage/bom_edit?", SA_BOM);
      $module->addLeftFunction(_("&Work Centres"), "/manufacturing/manage/work_centres?", SA_WORKCENTRES);
    }
  }
