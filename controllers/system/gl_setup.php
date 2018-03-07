<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  use ADV\Core\Table;
  use ADV\Core\JS;
  use ADV\Core\Num;
  use ADV\App\Forms;
  use ADV\Core\Event;
  use ADV\App\Validation;
  use ADV\Core\Input\Input;
  use ADV\App\Page;

  Page::start(_($help_context = "System and General GL Setup"), SA_GLSETUP);
  if (isset($_POST['submit']) && can_process()) {
    $_POST['allow_negative_stock'] = Input::_hasPost('allow_negative_stock');
    $_POST['po_over_receive']      = Validation::input_num('po_over_receive');
    $_POST['po_over_charge']       = Validation::input_num('po_over_charge');
    $_POST['accumulate_shipping']  = Input::_hasPost('accumulate_shipping');
    DB_Company::_update_gl_setup($_POST);
    Event::success(_("The general GL setup has been updated."));
  } /* end of if submit */
  Forms::start();
  //Table::startOuter("class='padded'");
  Table::startOuter('standard');
  Table::section(1);
  $myrow                               = DB_Company::_get_prefs();
  $_POST['retained_earnings_act']      = $myrow["retained_earnings_act"];
  $_POST['profit_loss_year_act']       = $myrow["profit_loss_year_act"];
  $_POST['debtors_act']                = $myrow["debtors_act"];
  $_POST['creditors_act']              = $myrow["creditors_act"];
  $_POST['freight_act']                = $myrow["freight_act"];
  $_POST['pyt_discount_act']           = $myrow["pyt_discount_act"];
  $_POST['exchange_diff_act']          = $myrow["exchange_diff_act"];
  $_POST['bank_charge_act']            = $myrow["bank_charge_act"];
  $_POST['default_sales_act']          = $myrow["default_sales_act"];
  $_POST['default_sales_discount_act'] = $myrow["default_sales_discount_act"];
  $_POST['default_prompt_payment_act'] = $myrow["default_prompt_payment_act"];
  $_POST['default_inventory_act']      = $myrow["default_inventory_act"];
  $_POST['default_credit_limit']       = $myrow["default_credit_limit"];
  $_POST['default_cogs_act']           = $myrow["default_cogs_act"];
  $_POST['default_adj_act']            = $myrow["default_adj_act"];
  $_POST['default_inv_sales_act']      = $myrow['default_inv_sales_act'];
  $_POST['default_assembly_act']       = $myrow['default_assembly_act'];
  $_POST['allow_negative_stock']       = $myrow['allow_negative_stock'];
  $_POST['po_over_receive']            = Num::_percentFormat($myrow['po_over_receive']);
  $_POST['po_over_charge']             = Num::_percentFormat($myrow['po_over_charge']);
  $_POST['past_due_days']              = $myrow['past_due_days'];
  $_POST['default_credit_limit']       = $myrow['default_credit_limit'];
  $_POST['legal_text']                 = $myrow['legal_text'];
  $_POST['accumulate_shipping']        = $myrow['accumulate_shipping'];
  $_POST['default_workorder_required'] = $myrow['default_workorder_required'];
  $_POST['default_dim_required']       = $myrow['default_dim_required'];
  $_POST['default_delivery_required']  = $myrow['default_delivery_required'];
  Table::sectionTitle(_("General GL"));
  // Not used in FA2.0.
  //GL_UI::all_row(_("Retained Earning Clearing Account:"), 'retained_earnings_act', $_POST['retained_earnings_act']);
  // Not used in FA2.0.
  //GL_UI::all_row(_("Payroll Account:"), 'payroll_act', $_POST['payroll_act']);
  Forms::textRow(_("Past Due Days Interval:"), 'past_due_days', $_POST['past_due_days'], 6, 6, '', "", _("days"));
  GL_UI::all_row(_("Retained Earnings:"), 'retained_earnings_act', $_POST['retained_earnings_act']);
  GL_UI::all_row(_("Profit/Loss Year:"), 'profit_loss_year_act', $_POST['profit_loss_year_act']);
  GL_UI::all_row(_("Exchange Variances Account:"), 'exchange_diff_act', $_POST['exchange_diff_act']);
  GL_UI::all_row(_("Bank Charges Account:"), 'bank_charge_act', $_POST['bank_charge_act']);
  Table::sectionTitle(_("Customers and Sales"));
  Forms::textRow(_("Default Credit Limit:"), 'default_credit_limit', $_POST['default_credit_limit'], 12, 12);
  Forms::checkRow(_("Accumulate batch shipping:"), 'accumulate_shipping', null);
  Forms::textareaRow(_("Legal Text on Invoice:"), 'legal_text', $_POST['legal_text'], 32, 3);
  GL_UI::all_row(_("Shipping Charged Account:"), 'freight_act', $_POST['freight_act']);
  Table::sectionTitle(_("Customers and Sales Defaults"));
  // default for customer branch
  GL_UI::all_row(_("Receivable Account:"), 'debtors_act');
  GL_UI::all_row(_("Sales Account:"), 'default_sales_act', null, false, false, true);
  GL_UI::all_row(_("Sales Discount Account:"), 'default_sales_discount_act');
  GL_UI::all_row(_("Prompt Payment Discount Account:"), 'default_prompt_payment_act');
  Forms::textRow(_("Delivery Required By:"), 'default_delivery_required', $_POST['default_delivery_required'], 6, 6, '', "", _("days"));
  Table::section(2);
  Table::sectionTitle(_("Dimension Defaults"));
  Forms::textRow(_("Dimension Required By After:"), 'default_dim_required', $_POST['default_dim_required'], 6, 6, '', "", _("days"));
  Table::sectionTitle(_("Suppliers and Purchasing"));
  Forms::percentRow(_("Delivery Over-Receive Allowance:"), 'po_over_receive');
  Forms::percentRow(_("Invoice Over-Charge Allowance:"), 'po_over_charge');
  Table::sectionTitle(_("Suppliers and Purchasing Defaults"));
  GL_UI::all_row(_("Payable Account:"), 'creditors_act', $_POST['creditors_act']);
  GL_UI::all_row(_("Purchase Discount Account:"), 'pyt_discount_act', $_POST['pyt_discount_act']);
  Table::sectionTitle(_("Inventory"));
  Forms::checkRow(_("Allow Negative Inventory:"), 'allow_negative_stock', null);
  Table::label(null, _("Warning: This may cause a delay in GL postings"), "", "class='stockmankofg' colspan=2");
  Table::sectionTitle(_("Items Defaults"));
  GL_UI::all_row(_("Sales Account:"), 'default_inv_sales_act', $_POST['default_inv_sales_act']);
  GL_UI::all_row(_("Inventory Account:"), 'default_inventory_act', $_POST['default_inventory_act']);
  // this one is default for items and suppliers (purchase account)
  GL_UI::all_row(_("C.O.G.S. Account:"), 'default_cogs_act', $_POST['default_cogs_act']);
  GL_UI::all_row(_("Inventory Adjustments Account:"), 'default_adj_act', $_POST['default_adj_act']);
  GL_UI::all_row(_("Item Assembly Costs Account:"), 'default_assembly_act', $_POST['default_assembly_act']);
  Table::sectionTitle(_("Manufacturing Defaults"));
  Forms::textRow(_("Work Order Required By After:"), 'default_workorder_required', $_POST['default_workorder_required'], 6, 6, '', "", _("days"));
  Table::endOuter(1);
  Forms::submitCenter('submit', _("Update"), true, '', 'default');
  Forms::end(2);
  Page::end();
  /**
   * @return bool
   */
  function can_process() {
    if (!Validation::post_num('po_over_receive', 0, 100)) {
      Event::error(_("The delivery over-receive allowance must be between 0 and 100."));
      JS::_setFocus('po_over_receive');
      return false;
    }
    if (!Validation::post_num('po_over_charge', 0, 100)) {
      Event::error(_("The invoice over-charge allowance must be between 0 and 100."));
      JS::_setFocus('po_over_charge');
      return false;
    }
    if (!Validation::post_num('past_due_days', 0, 100)) {
      Event::error(_("The past due days interval allowance must be between 0 and 100."));
      JS::_setFocus('past_due_days');
      return false;
    }
    return true;
  }


