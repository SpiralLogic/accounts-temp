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
  class System extends Application
  {
    public $name = "System";
    public $help_context = "&System";
    public function buildMenu() {
      $module = $this->add_module(_("Company Setup"));
      $module->addLeftFunction(_("&Company Setup"), "/system/company_preferences?", SA_SETUPCOMPANY);
      $module->addLeftFunction(_("&User Accounts Setup"), "/system/manage/users?", SA_USERS);
      $module->addLeftFunction(_("&Access Setup"), "/system/security_roles?", SA_SECROLES);
      $module->addLeftFunction(_("&Display Setup"), "/system/display_prefs?", SA_SETUPDISPLAY);
      $module->addLeftFunction(_("&Forms Setup"), "/system/forms_setup?", SA_FORMSETUP);
      $module->addRightFunction(_("&Taxes"), "/taxes/tax_types?", SA_TAXRATES);
      $module->addRightFunction(_("Tax &Groups"), "/taxes/tax_groups?", SA_TAXGROUPS);
      $module->addRightFunction(_("Item Ta&x Types"), "/tax/manage/types?", SA_ITEMTAXTYPE);
      $module->addRightFunction(_("System and &General GL Setup"), "/system/gl_setup?", SA_GLSETUP);
      $module->addRightFunction(_("&Fiscal Years"), "/system/fiscalyears?", SA_FISCALYEARS);
      $module->addRightFunction(_("&Print Profiles"), "/system/print_profiles?", SA_PRINTPROFILE);
      $module = $this->add_module(_("Miscellaneous"));
      $module->addLeftFunction(_("Pa&yment Terms"), "/system/payment_terms?", SA_PAYTERMS);
      $module->addLeftFunction(_("Shi&pping Company"), "/system/shipping_companies?", SA_SHIPPING);
      $module->addRightFunction(_("&Points of Sale"), "/sales/manage/sales_points?", SA_POSSETUP);
      $module->addRightFunction(_("&Printers"), "/system/printers?", SA_PRINTERS);
      $module = $this->add_module(_("Maintenance"));
      $module->addLeftFunction(_("&Void a Transaction"), "/system/void_transaction?", SA_VOIDTRANSACTION);
      $module->addLeftFunction(_("View or &Print Transactions"), "/system/view_print_transaction?", SA_VIEWPRINTTRANSACTION);
      $module->addLeftFunction(_("&Attach Documents"), "/system/attachments?filterType=20", SA_ATTACHDOCUMENT);
      $module->addLeftFunction(_("System &Diagnostics"), "/system/system_diagnostics?", SA_OPEN);
      $module->addRightFunction(_("&Backup and Restore"), "/system/backups?", SA_BACKUP);
      $module->addRightFunction(_("Create/Update &Companies"), "/system/create_coy?", SA_CREATECOMPANY);
      $module->addRightFunction(_("Install/Update &Languages"), "/system/inst_lang?", SA_CREATELANGUAGE);
      $module->addRightFunction(_("Software &Upgrade"), "/system/inst_upgrade?", SA_SOFTWAREUPGRADE);
    }
  }

