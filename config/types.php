<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  global $bank_transfer_types, //
         $class_types, //
         $payment_person_types, //
         $quick_actions, //
         $quick_entry_types, //
         $stock_types, //
         $systypes_array, //
         $systypes_array_short, //
         $tag_types, //
         $wo_cost_types, //
         $wo_types_array, $sales_type_names;
  //
  $bank_transfer_types  = array(
    BT_TRANSFER => "Transfer", //
    "Cheque", //
    "Credit", //
    "Cash"
  );
  $payment_person_types = array(
    PT_MISC => "Miscellaneous", //
    "Work Order", //
    "Debtor", //
    "Creditor", //
    "Quick Entry"
  );
  $wo_types_array       = array(
    WO_ASSEMBLY   => "Assemble", //
    WO_UNASSEMBLY => "Unassemble", //
    WO_ADVANCED   => "Advanced Manufacture"
  );
  $wo_cost_types        = array(
    WO_LABOUR   => "Labour Cost", //
    WO_OVERHEAD => "Overhead Cost", ////
  );
  $class_types          = array(
    CL_ASSETS      => "Assets", //
    CL_LIABILITIES => "Liabilities", //
    CL_EQUITY      => "Equity", //
    CL_INCOME      => "Income", //
    CL_COGS        => "Cost of Goods Sold", //
    CL_EXPENSE     => "Expense",
  );
  $quick_actions        = array(
    '='  => 'Remainder', // post current base amount to GL account
    'a'  => 'Amount', // post amount to GL account
    'a+' => 'Amount, increase base', // post amount to GL account and increase base
    'a-' => 'Amount, reduce base', // post amount to GL account and reduce base
    '%'  => '% amount of base', // store acc*amount% to GL account
    '%+' => '% amount of base, increase base', // ditto & increase base amount
    '%-' => '% amount of base, reduce base', // ditto & reduce base amount
    'T'  => 'Taxes added', // post taxes calculated on base amount
    'T+' => 'Taxes added, increase base', // ditto & increase base amount
    'T-' => 'Taxes added, reduce base', // ditto & reduce base amount
    't'  => 'Taxes included', // post taxes calculated on base amount
    't+' => 'Taxes included, increase base', // ditto & increase base amount
    't-' => 'Taxes included, reduce base' // ditto & reduce base amount
  );
  $quick_entry_types    = array(
    QE_DEPOSIT => "Bank Deposit", //
    QE_PAYMENT => "Bank Payment", //
    QE_JOURNAL => "Journal Entry", //
    QE_SUPPINV => "Supplier Invoice/Credit"
  );
  $stock_types          = array(
    STOCK_MANUFACTURE => "Manufactured", //
    STOCK_PURCHASED   => "Purchased", //
    STOCK_SERVICE     => "Service", //
    STOCK_INFO        => "Information"
  );
  $systypes_array       = array(
    ST_SALESQUOTE   => "Sales Quotation",
    ST_SALESORDER   => "Sales Order",
    ST_PROFORMA     => "Proforma Invoice",
    ST_CUSTDELIVERY => "Sales Delivery",
    ST_SALESINVOICE => "Sales Invoice",
    ST_CUSTCREDIT   => "Sales Credit Note",
    ST_CUSTPAYMENT  => "Debtor Payment",
    ST_CUSTREFUND   => "Debtor Refund",
    ST_PURCHORDER   => "Purchase Order",
    ST_SUPPRECEIVE  => "Purchase Delivery",
    ST_SUPPINVOICE  => "Purchase Invoice",
    ST_SUPPCREDIT   => "Purchase Credit Note",
    ST_SUPPAYMENT   => "Creditor Payment",
    ST_JOURNAL      => "Journal Entry",
    ST_BANKPAYMENT  => "Bank Payment",
    ST_BANKDEPOSIT  => "Bank Deposit",
    ST_BANKTRANSFER => "Funds Transfer",
    ST_GROUPDEPOSIT => "Group Deposit",
    ST_MANUISSUE    => "Work Order Issue",
    ST_WORKORDER    => "Work Order",
    ST_MANURECEIVE  => "Work Order Production",
    ST_INVADJUST    => "Inventory Adjustment",
    ST_LOCTRANSFER  => "Location Transfer",
    ST_COSTUPDATE   => "Cost Update",
    ST_DIMENSION    => "Dimension"
  );
  $systypes_array_short = array(
    ST_SALESQUOTE   => "Quote",
    ST_SALESORDER   => "Order",
    ST_PROFORMA     => "Proforma",
    ST_CUSTDELIVERY => "Despatch",
    ST_SALESINVOICE => "Cust Invoice",
    ST_CUSTCREDIT   => "Credit Note",
    ST_CUSTPAYMENT  => "Payment",
    ST_CUSTREFUND   => "Refund",
    ST_PURCHORDER   => "Order",
    ST_SUPPRECEIVE  => "Delivery",
    ST_SUPPINVOICE  => "Invoice",
    ST_SUPPCREDIT   => "Credit Note",
    ST_SUPPAYMENT   => "Payment",
    ST_JOURNAL      => "Journal Entry",
    ST_BANKPAYMENT  => "Payment",
    ST_BANKDEPOSIT  => "Deposit",
    ST_BANKTRANSFER => "Funds Transfer",
    ST_GROUPDEPOSIT => "Group Deposit",
    ST_MANUISSUE    => "Work Order Issue",
    ST_WORKORDER    => "Work Order",
    ST_MANURECEIVE  => "Work Order Production",
    ST_INVADJUST    => "Adjustment",
    ST_LOCTRANSFER  => "Location Transfer",
    ST_COSTUPDATE   => "Cost Update",
    ST_DIMENSION    => "Dimension"
  );
  $tag_types            = array(
    TAG_ACCOUNT   => "Account", //
    TAG_DIMENSION => "Dimension"
  );
  $wo_cost_types        = array(
    WO_LABOUR   => "Labour Cost", //
    WO_OVERHEAD => "Overhead Cost",
  );
  $wo_types_array       = array(
    WO_ASSEMBLY   => "Assemble", //
    WO_UNASSEMBLY => "Unassemble", //
    WO_ADVANCED   => "Advanced Manufacture"
  );
  $sales_type_names     = array(
    ST_SALESORDER   => 'Sales Order',
    ST_SALESQUOTE   => 'Sales Quote',
    ST_SALESINVOICE => "Invoice",
    ST_CUSTDELIVERY => "Sales Delivery",
    ST_CUSTPAYMENT  => "Sales Payment"
  );
