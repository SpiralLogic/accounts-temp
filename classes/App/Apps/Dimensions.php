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

  use DB_Company;
  use ADV\App\Application\Application;

  /** **/
  class Dimensions extends Application
  {
    public $name = "Dimensions";
    public $help_context = "&Dimensions";
    public $enabled = false;
    /**

     */
    public function __construct() {
      $this->enabled = DB_Company::_get_pref('use_dimension');
      parent::__construct();
    }
    public function buildMenu() {
      $module = $this->add_module(_("Transactions"));
      $module->addLeftFunction(_("Dimension &Entry"), "/dimensions/dimension_entry?", SA_DIMENSION);
      $module->addLeftFunction(_("&Outstanding Dimensions"), "/dimensions/inquiry/search_dimensions?outstanding_only=1", SA_DIMTRANSVIEW);
      $module = $this->add_module(_("Inquiries and Reports"));
      $module->addLeftFunction(_("Dimension &Inquiry"), "/dimensions/inquiry/search_dimensions?", SA_DIMTRANSVIEW);
      $module->addRightFunction(_("Dimension &Reports"), "reporting/reports_main?Class=4", SA_DIMENSIONREP);
      $module = $this->add_module(_("Maintenance"));
      $module->addLeftFunction(_("Dimension &Tags"), "system/tags?type=dimension", SA_DIMTAGS);
    }
  }

