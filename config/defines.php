<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  $return = array(
    'ICON_EDIT'               => "icon-file",
    'ICON_DELETE'             => "icon-trash",
    'ICON_ADD'                => "icon-ok",
    'ICON_UPDATE'             => "icon-ok",
    'ICON_OK'                 => "icon-ok",
    'ICON_SAVE'               => "icon-ok",
    'ICON_CANCEL'             => "icon-remove",
    'ICON_GL'                 => "icon-columns",
    'ICON_PRINT'              => "icon-print",
    'ICON_PDF'                => "icon-file",
    'ICON_DOC'                => "icon-shopping-cart",
    'ICON_CREDIT'             => "icon-credit-card",
    'ICON_RECEIVE'            => "icon-truck",
    'ICON_DOWN'               => "icon-download",
    'ICON_MONEY'              => "icon-credit-card",
    'ICON_REMOVE'             => "icon-remove",
    'ICON_REPORT'             => "icon-list",
    'ICON_VIEW'               => "icon-info-sign",
    'ICON_SUBMIT'             => "icon-ok",
    'ICON_ESCAPE'             => "icon-circle-arrow-left",
    'ICON_ASC'                => "icon-caret-up",
    'ICON_DESC'               => "icon-caret-down",
    'ICON_TRASH'              => "icon-trash",
    'DEFAULT_TAX_GROUP'       => 1,
    'DEFAULT_AREA'            => 1,
    'DEFAULT_SHIP_VIA'        => 1,
    'APP_TITLE'               => "Advanced Accounting",
    'POWERED_BY'              => 'Advanced Accounting',
    'POWERED_URL'             => 'http://www.advancedgroup.com.au',
    'PATH_THEME'              => ROOT_URL . 'themes/',
    'PATH_BACKUP'             => ROOT_URL . 'company/' . 'backup/',
    'PATH_REPORTS'            => ROOT_DOC . 'controllers' . DS . 'reporting' . DS,
    // ACCESS LEVELS
    'SS_SADMIN'               => 1 << 8,
    // site admin
    'SS_SETUP'                => 2 << 8,
    // company level setup
    'SS_SPEC'                 => 3 << 8,
    // special administration
    'SS_SALES_C'              => 11 << 8,
    // configuration
    'SS_SALES'                => 12 << 8,
    // transactions
    'SS_SALES_A'              => 13 << 8,
    // analytic functions/reports/inquires
    'SS_PURCH_C'              => 21 << 8,
    'SS_PURCH'                => 22 << 8,
    'SS_PURCH_A'              => 23 << 8,
    'SS_ITEMS_C'              => 31 << 8,
    'SS_ITEMS'                => 32 << 8,
    'SS_ITEMS_A'              => 33 << 8,
    'SS_MANUF_C'              => 41 << 8,
    'SS_MANUF'                => 42 << 8,
    'SS_MANUF_A'              => 43 << 8,
    'SS_DIM_C'                => 51 << 8,
    'SS_DIM'                  => 52 << 8,
    'SS_DIM_A'                => 53 << 8,
    'SS_GL_C'                 => 61 << 8,
    'SS_GL'                   => 62 << 8,
    'SS_GL_A'                 => 63 << 8,
    'SS_ADV'                  => 71 << 8,
    'SA_ADVANCED'             => 'SA_ADVANCED',
    'SA_OPEN'                 => 'SA_OPEN',
    'SA_DENIED'               => 'SA_DENIED',
    //
    //	Site administration
    //
    'SA_CREATECOMPANY'        => 'SA_CREATECOMPANY',
    'SA_CREATELANGUAGE'       => 'SA_CREATELANGUAGE',
    'SA_CREATEMODULES'        => 'SA_CREATEMODULES',
    'SA_SOFTWAREUPGRADE'      => 'SA_SOFTWAREUPGRADE',
    'SA_DEBUGGING'            => 'SA_DEBUGGING',
    //
    //	Company setup
    //
    'SA_SETUPCOMPANY'         => 'SA_SETUPCOMPANY',
    'SA_SECROLES'             => 'SA_SECROLES',
    'SA_USERS'                => 'SA_USERS',
    'SA_POSSETUP'             => 'SA_POSSETUP',
    'SA_PRINTERS'             => 'SA_PRINTERS',
    'SA_PRINTPROFILE'         => 'SA_PRINTPROFILE',
    'SA_PAYTERMS'             => 'SA_PAYTERMS',
    'SA_SHIPPING'             => 'SA_SHIPPING',
    'SA_CRSTATUS'             => 'SA_CRSTATUS',
    'SA_INVENTORYLOCATION'    => 'SA_INVENTORYLOCATION',
    'SA_INVENTORYMOVETYPE'    => 'SA_INVENTORYMOVETYPE',
    'SA_WORKCENTRES'          => 'SA_WORKCENTRES',
    'SA_FORMSETUP'            => 'SA_FORMSETUP',
    //
    // Special and common functions
    //
    'SA_VOIDTRANSACTION'      => 'SA_VOIDTRANSACTION',
    'SA_BACKUP'               => 'SA_BACKUP',
    'SA_VIEWPRINTTRANSACTION' => 'SA_VIEWPRINTTRANSACTION',
    'SA_ATTACHDOCUMENT'       => 'SA_ATTACHDOCUMENT',
    'SA_SETUPDISPLAY'         => 'SA_SETUPDISPLAY',
    'SA_CHGPASSWD'            => 'SA_CHGPASSWD',
    //
    // Sales related functionality
    //
    'SA_SALESTYPES'           => 'SA_SALESTYPES',
    'SA_SALESPRICE'           => 'SA_SALESPRICE',
    'SA_SALESMAN'             => 'SA_SALESMAN',
    'SA_SALESAREA'            => 'SA_SALESAREA',
    'SA_SALESGROUP'           => 'SA_SALESGROUP',
    'SA_STEMPLATE'            => 'SA_STEMPLATE',
    'SA_SRECURRENT'           => 'SA_SRECURRENT',
    'SA_SALESTRANSVIEW'       => 'SA_SALESTRANSVIEW',
    'SA_CUSTOMER'             => 'SA_CUSTOMER',
    'SA_CUSTOMER_CREDIT'      => 'SA_CUSTOMER_CREDIT',
    'SA_SALESQUOTE'           => 'SA_SALESQUOTE',
    'SA_SALESORDER'           => 'SA_SALESORDER',
    'SA_SALESDELIVERY'        => 'SA_SALESDELIVERY',
    'SA_SALESINVOICE'         => 'SA_SALESINVOICE',
    'SA_VOIDINVOICE'          => 'SA_VOIDINVOICE',
    'SA_SALESCREDITINV'       => 'SA_SALESCREDITINV',
    'SA_SALESCREDIT'          => 'SA_SALESCREDIT',
    'SA_SALESPAYMNT'          => 'SA_SALESPAYMNT',
    'SA_SALESREFUND'          => 'SA_SALESREFUND',
    'SA_SALESALLOC'           => 'SA_SALESALLOC',
    'SA_SALESANALYTIC'        => 'SA_SALESANALYTIC',
    'SA_SALESBULKREP'         => 'SA_SALESBULKREP',
    'SA_PRICEREP'             => 'SA_PRICEREP',
    'SA_SALESMANREP'          => 'SA_SALESMANREP',
    'SA_CUSTBULKREP'          => 'SA_CUSTBULKREP',
    'SA_CUSTSTATREP'          => 'SA_CUSTSTATREP',
    'SA_CUSTPAYMREP'          => 'SA_CUSTPAYMREP',
    'SA_CUSTREFUNDREP'        => 'SA_CUSTREFUNDREP',
    //
    // Purchase related functions
    //
    'SA_PURCHASEPRICING'      => 'SA_PURCHASEPRICING',
    'SA_SUPPTRANSVIEW'        => 'SA_SUPPTRANSVIEW',
    'SA_SUPPLIER'             => 'SA_SUPPLIER',
    'SA_PURCHASEORDER'        => 'SA_PURCHASEORDER',
    'SA_GRN'                  => 'SA_GRN',
    'SA_SUPPLIERINVOICE'      => 'SA_SUPPLIERINVOICE',
    'SA_GRNDELETE'            => 'SA_GRNDELETE',
    'SA_SUPPLIERCREDIT'       => 'SA_SUPPLIERCREDIT',
    'SA_SUPPLIERPAYMNT'       => 'SA_SUPPLIERPAYMNT',
    'SA_SUPPLIERALLOC'        => 'SA_SUPPLIERALLOC',
    'SA_SUPPLIERANALYTIC'     => 'SA_SUPPLIERANALYTIC',
    'SA_SUPPBULKREP'          => 'SA_SUPPBULKREP',
    'SA_SUPPPAYMREP'          => 'SA_SUPPPAYMREP',
    //
    // Inventory
    //
    'SA_ITEM'                 => 'SA_ITEM',
    'SA_SALESKIT'             => 'SA_SALESKIT',
    'SA_ITEMCATEGORY'         => 'SA_ITEMCATEGORY',
    'SA_UOM'                  => 'SA_UOM',
    'SA_ITEMSSTATVIEW'        => 'SA_ITEMSSTATVIEW',
    'SA_ITEMSTRANSVIEW'       => 'SA_ITEMSTRANSVIEW',
    'SA_FORITEMCODE'          => 'SA_FORITEMCODE',
    'SA_LOCATIONTRANSFER'     => 'SA_LOCATIONTRANSFER',
    'SA_INVENTORYADJUSTMENT'  => 'SA_INVENTORYADJUSTMENT',
    'SA_REORDER'              => 'SA_REORDER',
    'SA_ITEMSANALYTIC'        => 'SA_ITEMSANALYTIC',
    'SA_ITEMSVALREP'          => 'SA_ITEMSVALREP',
    //
    // Manufacturing module
    //
    'SA_BOM'                  => 'SA_BOM',
    'SA_MANUFTRANSVIEW'       => 'SA_MANUFTRANSVIEW',
    'SA_WORKORDERENTRY'       => 'SA_WORKORDERENTRY',
    'SA_MANUFISSUE'           => 'SA_MANUFISSUE',
    'SA_MANUFRECEIVE'         => 'SA_MANUFRECEIVE',
    'SA_MANUFRELEASE'         => 'SA_MANUFRELEASE',
    'SA_WORKORDERANALYTIC'    => 'SA_WORKORDERANALYTIC',
    'SA_WORKORDERCOST'        => 'SA_WORKORDERCOST',
    'SA_MANUFBULKREP'         => 'SA_MANUFBULKREP',
    'SA_BOMREP'               => 'SA_BOMREP',
    //
    // Dimensions
    //
    'SA_DIMTAGS'              => 'SA_DIMTAGS',
    'SA_DIMTRANSVIEW'         => 'SA_DIMTRANSVIEW',
    'SA_DIMENSION'            => 'SA_DIMENSION',
    'SA_DIMENSIONREP'         => 'SA_DIMENSIONREP',
    //
    // Banking and General Ledger
    //
    'SA_ITEMTAXTYPE'          => 'SA_ITEMTAXTYPE',
    'SA_GLACCOUNT'            => 'SA_GLACCOUNT',
    'SA_GLACCOUNTGROUP'       => 'SA_GLACCOUNTGROUP',
    'SA_GLACCOUNTCLASS'       => 'SA_GLACCOUNTCLASS',
    'SA_QUICKENTRY'           => 'SA_QUICKENTRY',
    'SA_CURRENCY'             => 'SA_CURRENCY',
    'SA_BANKACCOUNT'          => 'SA_BANKACCOUNT',
    'SA_TAXRATES'             => 'SA_TAXRATES',
    'SA_TAXGROUPS'            => 'SA_TAXGROUPS',
    'SA_FISCALYEARS'          => 'SA_FISCALYEARS',
    'SA_GLSETUP'              => 'SA_GLSETUP',
    'SA_GLACCOUNTTAGS'        => 'SA_GLACCOUNTTAGS',
    'SA_BANKTRANSVIEW'        => 'SA_BANKTRANSVIEW',
    'SA_GLTRANSVIEW'          => 'SA_GLTRANSVIEW',
    'SA_EXCHANGERATE'         => 'SA_EXCHANGERATE',
    'SA_PAYMENT'              => 'SA_PAYMENT',
    'SA_DEPOSIT'              => 'SA_DEPOSIT',
    'SA_BANKTRANSFER'         => 'SA_BANKTRANSFER',
    'SA_RECONCILE'            => 'SA_RECONCILE',
    'SA_JOURNALENTRY'         => 'SA_JOURNALENTRY',
    'SA_BANKJOURNAL'          => 'SA_BANKJOURNAL',
    'SA_BUDGETENTRY'          => 'SA_BUDGETENTRY',
    'SA_STANDARDCOST'         => 'SA_STANDARDCOST',
    'SA_GLANALYTIC'           => 'SA_GLANALYTIC',
    'SA_TAXREP'               => 'SA_TAXREP',
    'SA_BANKREP'              => 'SA_BANKREP',
    'SA_GLREP'                => 'SA_GLREP',
    //	ADVAccounts system transaction types
    //
    'ST_JOURNAL'              => 0,
    'ST_BANKPAYMENT'          => 1,
    'ST_BANKDEPOSIT'          => 2,
    'ST_BANKTRANSFER'         => 4,
    'ST_SALESINVOICE'         => 10,
    'ST_CUSTCREDIT'           => 11,
    'ST_CUSTPAYMENT'          => 12,
    'ST_CUSTDELIVERY'         => 13,
    'ST_CUSTREFUND'           => 14,
    'ST_GROUPDEPOSIT'         => 15,
    'ST_LOCTRANSFER'          => 16,
    'ST_INVADJUST'            => 17,
    'ST_PURCHORDER'           => 18,
    'ST_SUPPINVOICE'          => 20,
    'ST_SUPPCREDIT'           => 21,
    'ST_SUPPAYMENT'           => 22,
    'ST_SUPPRECEIVE'          => 25,
    'ST_WORKORDER'            => 26,
    'ST_MANUISSUE'            => 28,
    'ST_MANURECEIVE'          => 29,
    'ST_PROFORMA'             => 36,
    'ST_PROFORMAQ'            => 37,
    'ST_SALESORDER'           => 30,
    'ST_SALESQUOTE'           => 32,
    'ST_COSTUPDATE'           => 35,
    'ST_DIMENSION'            => 40,
    // Don't include these defines in the SysTypes::$names.
    // They are used for documents only.
    'ST_STATEMENT'            => 91,
    'ST_CHEQUE'               => 92,
    //		Bank transaction types
    //
    'BT_TRANSFER'             => 0,
    'BT_CHEQUE'               => 1,
    'BT_CREDIT'               => 2,
    'BT_CASH'                 => 3,
    //
    //	Payment types
    //
    'PT_MISC'                 => 0,
    'PT_WORKORDER'            => 1,
    'PT_CUSTOMER'             => 2,
    'PT_SUPPLIER'             => 3,
    'PT_QUICKENTRY'           => 4,
    'PT_DIMENSION'            => 5,
    //	Manufacturing types
    //
    'WO_ASSEMBLY'             => 0,
    'WO_UNASSEMBLY'           => 1,
    'WO_ADVANCED'             => 2,
    'WO_LABOUR'               => 0,
    'WO_OVERHEAD'             => 1,
    //	GL account classes
    //
    'CL_NONE'                 => 0,
    // for backward compatibility
    'CL_ASSETS'               => 1,
    'CL_LIABILITIES'          => 2,
    'CL_EQUITY'               => 3,
    'CL_INCOME'               => 4,
    'CL_COGS'                 => 5,
    'CL_EXPENSE'              => 6,
    //	Quick entry types
    //
    'QE_PAYMENT'              => 1,
    'QE_DEPOSIT'              => 2,
    'QE_JOURNAL'              => 3,
    'QE_SUPPINV'              => 4,
    //	Special option values for various list selectors.
    //
    'ANY_TEXT'                => '',
    'ANY_NUMERIC'             => -1,
    'ALL_TEXT'                => '',
    'ALL_NUMERIC'             => -1,
    'CT_CUSTOMER'             => 1,
    'CT_SUPPLIER'             => 2,
    // Types of stock items
    'STOCK_MANUFACTURE'       => 'M',
    'STOCK_PURCHASED'         => 'B',
    'STOCK_SERVICE'           => 'D',
    'STOCK_INFO'              => 'I',
    'TAG_ACCOUNT'             => 1,
    'TAG_DIMENSION'           => 2,
    // Special Locations
    'LOC_NOT_FAXED_YET'       => 'NFY',
    'LOC_DROP_SHIP'           => 'DRP',
    // Modes
    'MODE_RESET'              => 'RESET',
    'MODE_EDIT'               => 'Edit',
    'MODE_DELETE'             => 'Delete',
    'MODE_CLONE'              => 'CLONE',
    'COMMIT'                  => 'Commit',
    'ADD_ITEM'                => 'EnterLine',
    'ADDED_ID'                => 'AddedID',
    'ADDED_QU'                => 'AddedQU',
    'ADDED_DN'                => 'AddedDN',
    'ADDED_DI'                => 'AddedDI',
    'ADDED'                   => 'added',
    'UPDATED_ID'              => 'UpdatedID',
    'UPDATED_QU'              => 'UpdatedQU',
    'UPDATE_ITEM'             => 'updateItem',
    'UPDATED'                 => 'updated',
    'TYPE'                    => 'type',
    'REMOVED_ID'              => 'RemovedID',
    'REMOVED'                 => 'Removed',
    'CANCEL'                  => 'Cancel',
    'SAVE'                    => 'Save',
    'FETCH'                   => 'fetch',
    'ADD'                     => 'Add',
    'EDIT'                    => 'Edit',
    'DELETE'                  => 'Delete',
    'INACTIVE'                => 'Inactive',
    'CHANGED'                 => 'Changed',
    //FORMS
    'FORM_ID'                 => '_form_id',
    'FORM_VALUE'              => '_value',
    'FORM_ACTION'             => '_action',
    'FORM_CONTROL'            => '_control'
  );
  foreach (\ADV\Core\Config::_getAll('default') as $k => $v) {
    $return['DEFAULT_' . strtoupper($k)] = $v;
  }
  return $return;
