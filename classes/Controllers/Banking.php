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
  class Banking extends Menu
  {
    public $name = "Banking";
    public $help_context = "&Banking";
    /**

     */
    protected function before() {
      $module = $this->add_module(_("Transactions"));
      $module->addLeftFunction(_("&Payments"), "/banking/banking?NewPayment=Yes", SA_PAYMENT);
      $module->addLeftFunction(_("&Deposits"), "/banking/banking?NewDeposit=Yes", SA_DEPOSIT);
      $module->addLeftFunction(_("Bank Account &Transfers"), "/banking/bank_transfer?", SA_BANKTRANSFER);
      $module->addRightFunction(_("&Journal Entry"), "/banking/gl_journal?NewJournal=Yes", SA_JOURNALENTRY);
      $module->addRightFunction(_("&Budget Entry"), "/banking/gl_budget?", SA_BUDGETENTRY);
      $module->addRightFunction(_("&Reconcile Bank Account"), "/banking/reconcile?", SA_RECONCILE);
      $module->addRightFunction(_("&Upload Bank Statement"), "/banking/manage/statement?", SA_RECONCILE);
      $module = $this->add_module(_("Inquiries and Reports"));
      $module->addLeftFunction(_("&Journal Inquiry"), "/banking/inquiry/journal?", SA_GLANALYTIC);
      $module->addLeftFunction(_("GL &Inquiry"), "/gl/search/account?", SA_GLTRANSVIEW);
      $module->addLeftFunction(_("Bank Account &Inquiry"), "/banking/inquiry/bank?", SA_BANKTRANSVIEW);
      $module->addLeftFunction(_("Ta&x Inquiry"), "/banking/inquiry/tax?", SA_TAXREP);
      $module->addRightFunction(_("Trial &Balance"), "/banking/inquiry/gl_trial_balance?", SA_GLANALYTIC);
      $module->addRightFunction(_("Balance &Sheet Drilldown"), "/banking/inquiry/balance_sheet?", SA_GLANALYTIC);
      $module->addRightFunction(_("&Profit and Loss Drilldown"), "/banking/inquiry/profit_loss?", SA_GLANALYTIC);
      $module->addRightFunction(_("Banking &Reports"), "/reporting/reports_main?Class=5", SA_BANKREP);
      $module->addRightFunction(_("General Ledger &Reports"), "/reporting/reports_main?Class=6", SA_GLREP);
      $module = $this->add_module(_("Maintenance"));
      $module->addLeftFunction(_("Bank &Accounts"), "/banking/manage/bank_accounts?", SA_BANKACCOUNT);
      $module->addLeftFunction(_("&Quick Entries"), "/gl/manage/quickentries?", SA_QUICKENTRY);
      $module->addLeftFunction(_("Account &Tags"), "/system/tags?type=account", SA_GLACCOUNTTAGS);
      //  $module->addLeftFunction(_("Payment Methods"), "/banking/manage/payment_methods", SA_BANKACCOUNT);
      $module->addLeftFunction(_("&Currencies"), "/banking/manage/currencies?", SA_CURRENCY);
      $module->addLeftFunction(_("&Exchange Rates"), "/banking/manage/exchange_rates?", SA_EXCHANGERATE);
      $module->addRightFunction(_("&GL Accounts"), "/gl/manage/accounts?", SA_GLACCOUNT);
      $module->addRightFunction(_("GL Account &Types"), "/gl/manage/types?", SA_GLACCOUNTGROUP);
      $module->addRightFunction(_("GL Account &Classes"), "/banking/manage/gl_account_classes?", SA_GLACCOUNTCLASS);
    }
  }
