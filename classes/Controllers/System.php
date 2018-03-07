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
  class System extends Menu
  {
    public $name = "System";
    public $help_context = "&System";
    protected function before() {
      $module = $this->add_module(_("Company Setup"));
      $module->addLeftFunction(_("&Company Setup"), "/system/company_preferences?", SA_SETUPCOMPANY);
      $module->addLeftFunction(_("&User Setup"), "/system/manage/users?", SA_USERS);
      $module->addLeftFunction(_("&Access Setup"), "/system/security_roles?", SA_SECROLES);
      $module->addLeftFunction(_("&Display Setup"), "/system/display_prefs?", SA_SETUPDISPLAY);
      $module->addLeftFunction(_("&Forms Setup"), "/system/forms_setup?", SA_FORMSETUP);
      $module->addRightFunction(_("&Taxes"), "/tax/manage/types?", SA_TAXRATES);
      $module->addRightFunction(_("Tax &Groups"), "/taxes/tax_groups?", SA_TAXGROUPS);
      $module->addRightFunction(_("Item Ta&x Types"), "/tax/manage/itemtypes?", SA_ITEMTAXTYPE);
      $module->addRightFunction(_("System and &General GL Setup"), "/system/gl_setup?", SA_GLSETUP);
      $module->addRightFunction(_("&Fiscal Years"), "/system/fiscalyears?", SA_FISCALYEARS);
      $module = $this->add_module(_("Maintenance"));
      $module->addLeftFunction(_("&Void a Transaction"), "/system/void_transaction?", SA_VOIDTRANSACTION);
      $module->addRightFunction(_("View or &Print Transactions"), "/system/view_print_transaction?", SA_VIEWPRINTTRANSACTION);
      $module = $this->add_module(_('Setup'));
      $module->addLeftFunction(_('Sales T&ypes'), '/sales/manage/types?', SA_SALESTYPES);
      $module->addLeftFunction(_('Sales &Groups'), '/sales/manage/groups?', SA_SALESGROUP);
      $module->addLeftFunction(_("Pa&yment Terms"), "/system/payment_terms?", SA_PAYTERMS);
      $module->addRightFunction(_("Shi&pping Company"), "/system/shipping_companies?", SA_SHIPPING);
      $module->addRightFunction(_('Sales &Persons'), '/sales/manage/people?', SA_SALESMAN);
      $module->addRightFunction(_("&Points of Sale"), "/sales/manage/points?", SA_POSSETUP);
      $module->addRightFunction(_('Sales &Areas'), '/sales/manage/areas?', SA_SALESAREA);
      $module = $this->add_module(_("Miscellaneous"));
      $module->addLeftFunction(_('Credit &Statuses'), '/sales/manage/creditstatuses?', SA_CRSTATUS);
      $module->addRightFunction(_("&Printers"), "/system/printers?", SA_PRINTERS);
      $module->addRightFunction(_("&Print Profiles"), "/system/print_profiles?", SA_PRINTPROFILE);
      $module->addLeftFunction(_("System &Diagnostics"), "/system/system_diagnostics?", SA_OPEN);
      $module->addRightFunction(_("&Backup and Restore"), "/system/backups?", SA_BACKUP);
      $module->addRightFunction(_("Install/Update &Languages"), "/system/inst_lang?", SA_CREATELANGUAGE);
    }
  }

