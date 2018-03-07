<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Costed Bill Of Material Inquiry"), SA_WORKORDERCOST);
  Validation::check(Validation::BOM_ITEMS, _("There are no manufactured or kit items defined in the system."), STOCK_MANUFACTURE);
  if (isset($_GET['stock_id'])) {
    $_POST['stock_id'] = $_GET['stock_id'];
  }
  if (Forms::isListUpdated('stock_id')) {
    Ajax::_activate('_page_body');
  }
  Forms::start(false);
  Table::start('noborder');
  Item_UI::manufactured_row(_("Select a manufacturable item:"), 'stock_id', null, false, true);
  Table::end();
  echo "<br>";
  Display::heading(_("All Costs Are In:") . " " . Bank_Currency::for_company());
  WO::display_bom(Input::_post('stock_id'));
  Forms::end();
  Page::end();

