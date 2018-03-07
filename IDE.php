<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /** **/
  define('ICON_TRASH', 1);
  /** **/
  define('ICON_ADD', 1);
  define('ICON_EDIT', 1);
  define('ICON_MONEY', 1);
  /** **/
  define('ICON_COMMIT', 1);
  define('ICON_SUBMIT', 1);
  /** **/
  define('ICON_DELETE', 1);
  define('ICON_RECEIVE', 1);
  define('ICON_PRINT', 1);
  /** **/
  define('ICON_SAVE', 1);
  /** **/
  define('ICON_CANCEL', 1);
  define('ICON_UPDATE', 1);
  define('ICON_GL', 1);
  define('ICON_ASC', 1);
  define('ICON_DESC', 1);
  /** **/
  define('DEFAULT_AREA', 1);
  /** **/
  define('DEFAULT_SHIP_VIA', 1);
  /** **/
  define('APP_TITLE', "Advanced Accounting");
  /** **/
  define('POWERED_BY', 'Advanced Accounting');
  /** **/
  define('POWERED_URL', 'http://www.advancedgroup.com.au');
  /** **/
  define('PATH_COMPANY', ROOT_URL . 'company/');
  /** **/
  define('PATH_THEME', ROOT_URL . 'themes/');
  /** **/
  define("PATH_BACKUP", PATH_COMPANY . 'backup/');
  // ACCESS LEVELS
  /** **/
  define('SS_SADMIN', 1 << 8); // site admin
  /** **/
  define('SS_SETUP', 2 << 8); // company level setup
  /** **/
  define('SS_SPEC', 3 << 8); // special administration
  /** **/
  define('SS_SALES_C', 11 << 8); // configuration
  /** **/
  define('SS_SALES', 12 << 8); // transactions
  /** **/
  define('SS_SALES_A', 13 << 8); // analytic functions/reports/inquires
  /** **/
  define('SS_PURCH_C', 21 << 8);
  /** **/
  define('SS_PURCH', 22 << 8);
  /** **/
  define('SS_PURCH_A', 23 << 8);
  /** **/
  define('SS_ITEMS_C', 31 << 8);
  /** **/
  define('SS_ITEMS', 32 << 8);
  /** **/
  define('SS_ITEMS_A', 33 << 8);
  /** **/
  define('SS_MANUF_C', 41 << 8);
  /** **/
  define('SS_MANUF', 42 << 8);
  /** **/
  define('SS_MANUF_A', 43 << 8);
  /** **/
  define('SS_DIM_C', 51 << 8);
  /** **/
  define('SS_DIM', 52 << 8);
  /** **/
  define('SS_DIM_A', 53 << 8);
  /** **/
  define('SS_GL_C', 61 << 8);
  /** **/
  define('SS_GL', 62 << 8);
  /** **/
  define('SS_GL_A', 63 << 8);
  /** **/
  define('SS_ADV', 71 << 8);
  /** **/
  define('SA_ADVANCED', 'SA_ADVANCED');
  /** **/
  define('SA_OPEN', 'SA_OPEN');
  /** **/
  define('SA_DENIED', 'SA_DENIED');
  //
  //	Site administration
  //
  /** **/
  define('SA_CREATECOMPANY', 'SA_CREATECOMPANY');
  /** **/
  define('SA_CREATELANGUAGE', 'SA_CREATELANGUAGE');
  /** **/
  define('SA_CREATEMODULES', 'SA_CREATEMODULES');
  /** **/
  define('SA_SOFTWAREUPGRADE', 'SA_SOFTWAREUPGRADE');
  /** **/
  define('SA_DEBUGGING', 'SA_DEBUGGING');
  //
  //	Company setup
  //
  /** **/
  define('SA_SETUPCOMPANY', 'SA_SETUPCOMPANY');
  /** **/
  define('SA_SECROLES', 'SA_SECROLES');
  /** **/
  define('SA_USERS', 'SA_USERS');
  /** **/
  define('SA_POSSETUP', 'SA_POSSETUP');
  /** **/
  define('SA_PRINTERS', 'SA_PRINTERS');
  /** **/
  define('SA_PRINTPROFILE', 'SA_PRINTPROFILE');
  /** **/
  define('SA_PAYTERMS', 'SA_PAYTERMS');
  /** **/
  define('SA_SHIPPING', 'SA_SHIPPING');
  /** **/
  define('SA_CRSTATUS', 'SA_CRSTATUS');
  /** **/
  define('SA_INVENTORYLOCATION', 'SA_INVENTORYLOCATION');
  /** **/
  define('SA_INVENTORYMOVETYPE', 'SA_INVENTORYMOVETYPE');
  /** **/
  define('SA_WORKCENTRES', 'SA_WORKCENTRES');
  /** **/
  define('SA_FORMSETUP', 'SA_FORMSETUP');
  //
  // Special and common functions
  //
  /** **/
  define('SA_VOIDTRANSACTION', 'SA_VOIDTRANSACTION');
  /** **/
  define('SA_BACKUP', 'SA_BACKUP');
  /** **/
  define('SA_VIEWPRINTTRANSACTION', 'SA_VIEWPRINTTRANSACTION');
  /** **/
  define('SA_ATTACHDOCUMENT', 'SA_ATTACHDOCUMENT');
  /** **/
  define('SA_SETUPDISPLAY', 'SA_SETUPDISPLAY');
  /** **/
  define('SA_CHGPASSWD', 'SA_CHGPASSWD');
  //
  // Sales related functionality
  //
  /** **/
  define('SA_SALESTYPES', 'SA_SALESTYPES');
  /** **/
  define('SA_SALESPRICE', 'SA_SALESPRICE');
  /** **/
  define('SA_SALESMAN', 'SA_SALESMAN');
  /** **/
  define('SA_SALESAREA', 'SA_SALESAREA');
  /** **/
  define('SA_SALESGROUP', 'SA_SALESGROUP');
  /** **/
  define('SA_STEMPLATE', 'SA_STEMPLATE');
  /** **/
  define('SA_SRECURRENT', 'SA_SRECURRENT');
  /** **/
  define('SA_SALESTRANSVIEW', 'SA_SALESTRANSVIEW');
  /** **/
  define('SA_CUSTOMER', 'SA_CUSTOMER');
  /** **/
  define('SA_CUSTOMER_CREDIT', 'SA_CUSTOMER_CREDIT');
  /** **/
  define('SA_SALESQUOTE', 'SA_SALESQUOTE');
  /** **/
  define('SA_SALESORDER', 'SA_SALESORDER');
  /** **/
  define('SA_SALESDELIVERY', 'SA_SALESDELIVERY');
  /** **/
  define('SA_SALESINVOICE', 'SA_SALESINVOICE');
  /** **/
  define('SA_VOIDINVOICE', 'SA_VOIDINVOICE');
  /** **/
  define('SA_SALESCREDITINV', 'SA_SALESCREDITINV');
  /** **/
  define('SA_SALESCREDIT', 'SA_SALESCREDIT');
  /** **/
  define('SA_SALESPAYMNT', 'SA_SALESPAYMNT');
  /** **/
  define('SA_SALESREFUND', 'SA_SALESREFUND');
  /** **/
  define('SA_SALESALLOC', 'SA_SALESALLOC');
  /** **/
  define('SA_SALESANALYTIC', 'SA_SALESANALYTIC');
  /** **/
  define('SA_SALESBULKREP', 'SA_SALESBULKREP');
  /** **/
  define('SA_PRICEREP', 'SA_PRICEREP');
  /** **/
  define('SA_SALESMANREP', 'SA_SALESMANREP');
  /** **/
  define('SA_CUSTBULKREP', 'SA_CUSTBULKREP');
  /** **/
  define('SA_CUSTSTATREP', 'SA_CUSTSTATREP');
  /** **/
  define('SA_CUSTPAYMREP', 'SA_CUSTPAYMREP');
  /** **/
  define('SA_CUSTREFUNDREP', 'SA_CUSTREFUNDREP');
  //
  // Purchase related functions
  //
  /** **/
  define('SA_PURCHASEPRICING', 'SA_PURCHASEPRICING');
  /** **/
  define('SA_SUPPTRANSVIEW', 'SA_SUPPTRANSVIEW');
  /** **/
  define('SA_SUPPLIER', 'SA_SUPPLIER');
  /** **/
  define('SA_PURCHASEORDER', 'SA_PURCHASEORDER');
  /** **/
  define('SA_GRN', 'SA_GRN');
  /** **/
  define('SA_SUPPLIERINVOICE', 'SA_SUPPLIERINVOICE');
  /** **/
  define('SA_GRNDELETE', 'SA_GRNDELETE');
  /** **/
  define('SA_SUPPLIERCREDIT', 'SA_SUPPLIERCREDIT');
  /** **/
  define('SA_SUPPLIERPAYMNT', 'SA_SUPPLIERPAYMNT');
  /** **/
  define('SA_SUPPLIERALLOC', 'SA_SUPPLIERALLOC');
  /** **/
  define('SA_SUPPLIERANALYTIC', 'SA_SUPPLIERANALYTIC');
  /** **/
  define('SA_SUPPBULKREP', 'SA_SUPPBULKREP');
  /** **/
  define('SA_SUPPPAYMREP', 'SA_SUPPPAYMREP');
  //
  // Inventory
  //
  /** **/
  define('SA_ITEM', 'SA_ITEM');
  /** **/
  define('SA_SALESKIT', 'SA_SALESKIT');
  /** **/
  define('SA_ITEMCATEGORY', 'SA_ITEMCATEGORY');
  /** **/
  define('SA_UOM', 'SA_UOM');
  /** **/
  define('SA_ITEMSSTATVIEW', 'SA_ITEMSSTATVIEW');
  /** **/
  define('SA_ITEMSTRANSVIEW', 'SA_ITEMSTRANSVIEW');
  /** **/
  define('SA_FORITEMCODE', 'SA_FORITEMCODE');
  /** **/
  define('SA_LOCATIONTRANSFER', 'SA_LOCATIONTRANSFER');
  /** **/
  define('SA_INVENTORYADJUSTMENT', 'SA_INVENTORYADJUSTMENT');
  /** **/
  define('SA_REORDER', 'SA_REORDER');
  /** **/
  define('SA_ITEMSANALYTIC', 'SA_ITEMSANALYTIC');
  /** **/
  define('SA_ITEMSVALREP', 'SA_ITEMSVALREP');
  //
  // Manufacturing module
  //
  /** **/
  define('SA_BOM', 'SA_BOM');
  /** **/
  define('SA_MANUFTRANSVIEW', 'SA_MANUFTRANSVIEW');
  /** **/
  define('SA_WORKORDERENTRY', 'SA_WORKORDERENTRY');
  /** **/
  define('SA_MANUFISSUE', 'SA_MANUFISSUE');
  /** **/
  define('SA_MANUFRECEIVE', 'SA_MANUFRECEIVE');
  /** **/
  define('SA_MANUFRELEASE', 'SA_MANUFRELEASE');
  /** **/
  define('SA_WORKORDERANALYTIC', 'SA_WORKORDERANALYTIC');
  /** **/
  define('SA_WORKORDERCOST', 'SA_WORKORDERCOST');
  /** **/
  define('SA_MANUFBULKREP', 'SA_MANUFBULKREP');
  /** **/
  define('SA_BOMREP', 'SA_BOMREP');
  //
  // Dimensions
  //
  /** **/
  define('SA_DIMTAGS', 'SA_DIMTAGS');
  /** **/
  define('SA_DIMTRANSVIEW', 'SA_DIMTRANSVIEW');
  /** **/
  define('SA_DIMENSION', 'SA_DIMENSION');
  /** **/
  define('SA_DIMENSIONREP', 'SA_DIMENSIONREP');
  //
  // Banking and General Ledger
  //
  /** **/
  define('SA_ITEMTAXTYPE', 'SA_ITEMTAXTYPE');
  /** **/
  define('SA_GLACCOUNT', 'SA_GLACCOUNT');
  /** **/
  define('SA_GLACCOUNTGROUP', 'SA_GLACCOUNTGROUP');
  /** **/
  define('SA_GLACCOUNTCLASS', 'SA_GLACCOUNTCLASS');
  /** **/
  define('SA_QUICKENTRY', 'SA_QUICKENTRY');
  /** **/
  define('SA_CURRENCY', 'SA_CURRENCY');
  /** **/
  define('SA_BANKACCOUNT', 'SA_BANKACCOUNT');
  /** **/
  define('SA_TAXRATES', 'SA_TAXRATES');
  /** **/
  define('SA_TAXGROUPS', 'SA_TAXGROUPS');
  /** **/
  define('SA_FISCALYEARS', 'SA_FISCALYEARS');
  /** **/
  define('SA_GLSETUP', 'SA_GLSETUP');
  /** **/
  define('SA_GLACCOUNTTAGS', 'SA_GLACCOUNTTAGS');
  /** **/
  define('SA_BANKTRANSVIEW', 'SA_BANKTRANSVIEW');
  /** **/
  define('SA_GLTRANSVIEW', 'SA_GLTRANSVIEW');
  /** **/
  define('SA_EXCHANGERATE', 'SA_EXCHANGERATE');
  /** **/
  define('SA_PAYMENT', 'SA_PAYMENT');
  /** **/
  define('SA_DEPOSIT', 'SA_DEPOSIT');
  /** **/
  define('SA_BANKTRANSFER', 'SA_BANKTRANSFER');
  /** **/
  define('SA_RECONCILE', 'SA_RECONCILE');
  /** **/
  define('SA_JOURNALENTRY', 'SA_JOURNALENTRY');
  /** **/
  define('SA_BANKJOURNAL', 'SA_BANKJOURNAL');
  /** **/
  define('SA_BUDGETENTRY', 'SA_BUDGETENTRY');
  /** **/
  define('SA_STANDARDCOST', 'SA_STANDARDCOST');
  /** **/
  define('SA_GLANALYTIC', 'SA_GLANALYTIC');
  /** **/
  define('SA_TAXREP', 'SA_TAXREP');
  /** **/
  define('SA_BANKREP', 'SA_BANKREP');
  /** **/
  define('SA_GLREP', 'SA_GLREP');
  //	ADVAccounts system transaction types
  //
  /** **/
  define('ST_JOURNAL', 0);
  /** **/
  define('ST_BANKPAYMENT', 1);
  /** **/
  define('ST_BANKDEPOSIT', 2);
  /** **/
  define('ST_BANKTRANSFER', 4);
  /** **/
  define('ST_SALESINVOICE', 10);
  /** **/
  define('ST_CUSTCREDIT', 11);
  /** **/
  define('ST_CUSTPAYMENT', 12);
  /** **/
  define('ST_CUSTDELIVERY', 13);
  /** **/
  define('ST_CUSTREFUND', 14);
  /** **/
  define('ST_GROUPDEPOSIT', 15);
  /** **/
  define('ST_LOCTRANSFER', 16);
  /** **/
  define('ST_INVADJUST', 17);
  /** **/
  define('ST_PURCHORDER', 18);
  /** **/
  define('ST_SUPPINVOICE', 20);
  /** **/
  define('ST_SUPPCREDIT', 21);
  /** **/
  define('ST_SUPPAYMENT', 22);
  /** **/
  define('ST_SUPPRECEIVE', 25);
  /** **/
  define('ST_WORKORDER', 26);
  /** **/
  define('ST_MANUISSUE', 28);
  /** **/
  define('ST_MANURECEIVE', 29);
  /** **/
  define('ST_PROFORMA', 36);
  /** **/
  define('ST_PROFORMAQ', 37);
  /** **/
  define('ST_SALESORDER', 30);
  /** **/
  define('ST_SALESQUOTE', 32);
  /** **/
  define('ST_COSTUPDATE', 35);
  /** **/
  define('ST_DIMENSION', 40);
  // Don't include these defines in the SysTypes::$names.
  // They are used for documents only.
  /** **/
  define ('ST_STATEMENT', 91);
  /** **/
  define ('ST_CHEQUE', 92);
  //		Bank transaction types
  //
  /** **/
  define('BT_TRANSFER', 0);
  /** **/
  define('BT_CHEQUE', 1);
  /** **/
  define('BT_CREDIT', 2);
  /** **/
  define('BT_CASH', 3);
  //
  //	Payment types
  //
  /** **/
  define('PT_MISC', 0);
  /** **/
  define('PT_WORKORDER', 1);
  /** **/
  define('PT_CUSTOMER', 2);
  /** **/
  define('PT_SUPPLIER', 3);
  /** **/
  define('PT_QUICKENTRY', 4);
  /** **/
  define('PT_DIMENSION', 5);
  //	Manufacturing types
  //
  /** **/
  define('WO_ASSEMBLY', 0);
  /** **/
  define('WO_UNASSEMBLY', 1);
  /** **/
  define('WO_ADVANCED', 2);
  /** **/
  define('WO_LABOUR', 0);
  /** **/
  define('WO_OVERHEAD', 1);
  //	GL account classes
  //
  /** **/
  define('CL_NONE', 0); // for backward compatibility
  /** **/
  define('CL_ASSETS', 1);
  /** **/
  define('CL_LIABILITIES', 2);
  /** **/
  define('CL_EQUITY', 3);
  /** **/
  define('CL_INCOME', 4);
  /** **/
  define('CL_COGS', 5);
  /** **/
  define('CL_EXPENSE', 6);
  //	Quick entry types
  //
  /** **/
  define('QE_PAYMENT', 1);
  /** **/
  define('QE_DEPOSIT', 2);
  /** **/
  define('QE_JOURNAL', 3);
  /** **/
  define('QE_SUPPINV', 4);
  //	Special option values for various list selectors.
  //
  /** **/
  define('ANY_TEXT', '');
  /** **/
  define('ANY_NUMERIC', -1);
  /** **/
  define('ALL_TEXT', '');
  /** **/
  define('ALL_NUMERIC', -1);
  /** **/
  define('CT_CUSTOMER', 1);
  /** **/
  define('CT_SUPPLIER', 2);
  // Types of stock items
  /** **/
  define('STOCK_MANUFACTURE', 'M');
  /** **/
  define('STOCK_PURCHASED', 'B');
  /** **/
  define('STOCK_SERVICE', 'D');
  /** **/
  define('STOCK_INFO', 'I');
  /** **/
  define('TAG_ACCOUNT', 1);
  /** **/
  define('TAG_DIMENSION', 2);
  /** **/
  define('PATH_REPORTS', '../reporting');
  // Special Locations
  /** **/
  define('LOC_NOT_FAXED_YET', 'NFY');
  /** **/
  define('LOC_DROP_SHIP', 'DRP');
  // Modes
  /** **/
  define('MODE_RESET', 'RESET');
  /** **/
  define('MODE_EDIT', 'Edit');
  /** **/
  define('MODE_DELETE', 'Delete');
  /** **/
  define('MODE_CLONE', 'CLONE');
  /** **/
  define('COMMIT', 'Commit');
  /** **/
  define('ADD_ITEM', 'EnterLine');
  /** **/
  define('ADDED_ID', 'AddedID');
  /** **/
  define('ADDED_QU', 'AddedQU');
  /** **/
  define('ADDED_DN', 'AddedDN');
  /** **/
  define('ADDED_DI', 'AddedDI');
  /** **/
  define('ADDED', 'added');
  /** **/
  define('UPDATED_ID', 'UpdatedID');
  /** **/
  define('UPDATED_QU', 'UpdatedQU');
  /** **/
  define('UPDATE_ITEM', 'updateItem');
  /** **/
  define('UPDATED', 'updated');
  /** **/
  define('TYPE', 'type');
  /** **/
  define('REMOVED_ID', 'RemovedID');
  /** **/
  define('REMOVED', 'Removed');
  /** **/
  define('CANCEL', 'Cancel');
  /** **/
  define('SAVE', 'Save');
  define('ADD', 'ADD');
  define('FETCH', 'fetch');
  define('INACTIVE', 'Inactive');
  define('DELETE', 'Delete');
  define('CHANGED', 'Changed');
  define('EDIT', 'Edit');
  //FORMS
  define('FORM_ID', '_form_id');
  define('FORM_VALUE', '_value');
  define('FORM_ACTION', '_action');
  define('FORM_CONTROL', '_control');
  function xhprof_enable() {
  }

  function newrelic_set_appname() {
  }

  function newrelic_name_transaction() {
  }

  function newrelic_disable_autorum() {
  }

  /**
   * @return string
   */
  function xhprof_disable() {
    return '';
  }

  define('XHPROF_FLAGS_CPU', 0);
  define('XHPROF_FLAGS_MEMORY', 0);
  /** **/
  class XHProfRuns_Default
  {
    function save_run() {
    }
  }

  /**
   * @param $string
   */
  function igbinary_serialize($string) {
    return $string;
  }

  /**
   * @param $string
   */
  function igbinary_unserialize($string) {
    return $string;
  }
