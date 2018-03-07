<?php
  use ADV\App\Report;
  use ADV\Core\JS;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Reports and Analysis"), SA_OPEN);
  $reports = new Reports_Box();
  $dim     = DB_Company::_get_pref('use_dimension');
  $reports->addReportClass(_('Customer'));
  $reports->addReport(
    _('Customer'),
    101,
    _('Customer &Balances'),
    array(
         _('Start Date')      => Report::DATEBEGIN,
         _('End Date')        => Report::DATEENDM,
         _('Customer')        => Report::CUSTOMERS_NO_FILTER,
         _('Currency Filter') => Report::CURRENCY,
         _('Suppress Zeros')  => Report::YES_NO,
         _('Comments')        => Report::TEXTBOX,
         _('Destination')     => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('Customer'),
    102,
    _('&Aged Customer Analysis'),
    array(
         _('End Date')        => Report::DATE,
         _('Customer')        => Report::CUSTOMERS_NO_FILTER,
         _('Currency Filter') => Report::CURRENCY,
         _('Summary Only')    => Report::YES_NO,
         _('Suppress Zeros')  => Report::YES_NO,
         _('Graphics')        => Report::GRAPHIC,
         _('Comments')        => Report::TEXTBOX,
         _('Destination')     => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('Customer'),
    103,
    _('Customer &Detail Listing'),
    array(
         _('Activity Since')        => Report::DATEBEGIN,
         _('Sales Areas')           => Report::AREAS,
         _('Sales Folk')            => Report::SALESMEN,
         _('Activity Greater Than') => Report::TEXT,
         _('Activity Less Than')    => Report::TEXT,
         _('Comments')              => Report::TEXTBOX,
         _('Destination')           => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('Customer'),
    104,
    _('&Price Listing'),
    array(
         _('Currency Filter')    => Report::CURRENCY,
         _('Inventory Category') => Report::CATEGORIES,
         _('Sales Types')        => Report::SALESTYPES,
         _('Show Pictures')      => Report::YES_NO,
         _('Show GP %')          => Report::YES_NO,
         _('Comments')           => Report::TEXTBOX,
         _('Destination')        => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('Customer'),
    105,
    _('&Order Status Listing'),
    array(
         _('Start Date')         => Report::DATEBEGINM,
         _('End Date')           => Report::DATEENDM,
         _('Inventory Category') => Report::CATEGORIES,
         _('Stock Location')     => Report::LOCATIONS,
         _('Back Orders Only')   => Report::YES_NO,
         _('Comments')           => Report::TEXTBOX,
         _('Destination')        => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('Customer'),
    106,
    _('&Salesman Listing'),
    array(
         _('Start Date')   => Report::DATEBEGINM,
         _('End Date')     => Report::DATEENDM,
         _('Summary Only') => Report::YES_NO,
         _('Comments')     => Report::TEXTBOX,
         _('Destination')  => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('Customer'),
    107,
    _('Print &Invoices/Credit Notes'),
    array(
         _('From')            => Report::INVOICE,
         _('To')              => Report::INVOICE,
         _('Currency Filter') => Report::CURRENCY,
         _('email Customers') => Report::YES_NO,
         _('Payment Link')    => Report::PAYMENT_LINK,
         _('Comments')        => Report::TEXTBOX
    )
  );
  $reports->addReport(
    _('Customer'),
    110,
    _('Print &Deliveries'),
    array(
         _('From')                  => Report::DELIVERY,
         _('To')                    => Report::DELIVERY,
         _('email Customers')       => Report::YES_NO,
         _('Print as Packing Slip') => Report::YES_NO,
         _('Comments')              => Report::TEXTBOX
    )
  );
  $reports->addReport(
    _('Customer'),
    108,
    _('Print &Statements'),
    array(
         _('Customer')          => Report::CUSTOMERS_NOZERO_BALANCE,
         _('Email Customers')   => Report::YES_NO,
         _('Month')             => Report::DATEMONTH,
         _('Include All')       => Report::YES_NO,
         _('Include Payments')  => Report::YES_NO,
         _('Include Negatives') => Report::YES_NO,
         _('Comments')          => Report::TEXTBOX,
         _('Currency Filter')   => Report::CURRENCY,
    )
  );
  $reports->addReport(
    _('Customer'),
    109,
    _('&Print Sales Orders'),
    array(
         _('From')            => Report::ORDERS,
         _('To')              => Report::ORDERS,
         _('Currency Filter') => Report::CURRENCY,
         _('Email Customers') => Report::YES_NO,
         _('Print as Quote')  => Report::YES_NO,
         _('Comments')        => Report::TEXTBOX
    )
  );
  $reports->addReport(
    _('Customer'),
    111,
    _('&Print Sales Quotations'),
    array(
         _('From')            => Report::QUOTATIONS,
         _('To')              => Report::QUOTATIONS,
         _('Currency Filter') => Report::CURRENCY,
         _('Email Customers') => Report::YES_NO,
         _('Comments')        => Report::TEXTBOX
    )
  );
  $reports->addReport(
    _('Customer'),
    111,
    _('&Print Sales Quotations'),
    array(
         _('From')            => Report::QUOTATIONS,
         _('To')              => Report::QUOTATIONS,
         _('Currency Filter') => Report::CURRENCY,
         _('Email Customers') => Report::YES_NO,
         _('Comments')        => Report::TEXTBOX
    )
  );
  $reports->addReport(
    _('Customer'),
    112,
    _('Print Receipts'),
    array(
         _('From')            => Report::RECEIPT,
         _('To')              => Report::RECEIPT,
         _('Currency Filter') => Report::CURRENCY,
         _('Comments')        => Report::TEXTBOX
    )
  );
  $reports->addReportClass(_('Supplier'));
  $reports->addReport(
    _('Supplier'),
    201,
    _('Supplier &Balances'),
    array(
         _('Start Date')      => Report::DATEBEGIN,
         _('End Date')        => Report::DATEENDM,
         _('Supplier')        => Report::SUPPLIERS_NO_FILTER,
         _('Currency Filter') => Report::CURRENCY,
         _('Suppress Zeros')  => Report::YES_NO,
         _('Comments')        => Report::TEXTBOX,
         _('Destination')     => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('Supplier'),
    202,
    _('&Aged Supplier Analyses'),
    array(
         _('End Date')        => Report::DATE,
         _('Supplier')        => Report::SUPPLIERS_NO_FILTER,
         _('Currency Filter') => Report::CURRENCY,
         _('Summary Only')    => Report::YES_NO,
         _('Suppress Zeros')  => Report::YES_NO,
         _('Graphics')        => Report::GRAPHIC,
         _('Comments')        => Report::TEXTBOX,
         _('Destination')     => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('Supplier'),
    203,
    _('&Payment Report'),
    array(
         _('End Date')        => Report::DATE,
         _('Supplier')        => Report::SUPPLIERS_NO_FILTER,
         _('Currency Filter') => Report::CURRENCY,
         _('Suppress Zeros')  => Report::YES_NO,
         _('Comments')        => Report::TEXTBOX,
         _('Destination')     => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('Supplier'),
    204,
    _('Outstanding &GRNs Report'),
    array(
         _('Supplier')    => Report::SUPPLIERS_NO_FILTER,
         _('Comments')    => Report::TEXTBOX,
         _('Destination') => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('Supplier'),
    209,
    _('Print Purchase &Orders'),
    array(
         _('From')            => Report::PO,
         _('To')              => Report::PO,
         _('Currency Filter') => Report::CURRENCY,
         _('Email Customers') => Report::YES_NO,
         _('Comments')        => Report::TEXTBOX
    )
  );
  $reports->addReport(
    _('Supplier'),
    210,
    _('Print Remittances'),
    array(
         _('From')            => Report::REMITTANCE,
         _('To')              => Report::REMITTANCE,
         _('Currency Filter') => Report::CURRENCY,
         _('Email Customers') => Report::YES_NO,
         _('Comments')        => Report::TEXTBOX
    )
  );
  $reports->addReportClass(_('Inventory'));
  $reports->addReport(
    _('Inventory'),
    301,
    _('Inventory &Valuation Report'),
    array(
         _('Inventory Category') => Report::CATEGORIES,
         _('Location')           => Report::LOCATIONS,
         _('Summary Only')       => Report::YES_NO,
         _('Comments')           => Report::TEXTBOX,
         _('Destination')        => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('Inventory'),
    302,
    _('Inventory &Planning Report'),
    array(
         _('Inventory Category') => Report::CATEGORIES,
         _('Location')           => Report::LOCATIONS,
         _('Comments')           => Report::TEXTBOX,
         _('Destination')        => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('Inventory'),
    303,
    _('Stock &Check Sheets'),
    array(
         _('Inventory Category') => Report::CATEGORIES,
         _('Location')           => Report::LOCATIONS,
         _('Show Pictures')      => Report::YES_NO,
         _('Inventory Column')   => Report::YES_NO,
         _('Show Shortage')      => Report::YES_NO,
         _('Suppress Zeros')     => Report::YES_NO,
         _('Comments')           => Report::TEXTBOX,
         _('Destination')        => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('Inventory'),
    304,
    _('Inventory &Sales Report'),
    array(
         _('Start Date')         => Report::DATEBEGINM,
         _('End Date')           => Report::DATEENDM,
         _('Inventory Category') => Report::CATEGORIES,
         _('Location')           => Report::LOCATIONS,
         _('Customer')           => Report::CUSTOMERS_NO_FILTER,
         _('Comments')           => Report::TEXTBOX,
         _('Destination')        => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('Inventory'),
    305,
    _('&GRN Valuation Report'),
    array(
         _('Start Date')  => Report::DATEBEGINM,
         _('End Date')    => Report::DATEENDM,
         _('Comments')    => Report::TEXTBOX,
         _('Destination') => Report::DESTINATION
    )
  );
  $reports->addReportClass(_('Manufacturing'));
  $reports->addReport(
    _('Manufacturing'),
    401,
    _('&Bill of Material Listing'),
    array(
         _('From product') => Report::ITEMS,
         _('To product')   => Report::ITEMS,
         _('Comments')     => Report::TEXTBOX,
         _('Destination')  => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('Manufacturing'),
    409,
    _('Print &Work Orders'),
    array(
         _('From')            => Report::WORKORDER,
         _('To')              => Report::WORKORDER,
         _('Email Locations') => Report::YES_NO,
         _('Comments')        => Report::TEXTBOX
    )
  );
  $reports->addReportClass(_('Dimensions'));
  if ($dim > 0) {
    $reports->addReport(
      _('Dimensions'),
      501,
      _('Dimension &Summary'),
      array(
           _('From Dimension') => Report::DIMENSION,
           _('To Dimension')   => Report::DIMENSION,
           _('Show Balance')   => Report::YES_NO,
           _('Comments')       => Report::TEXTBOX,
           _('Destination')    => Report::DESTINATION
      )
    );
    //$reports->addReport(_('Dimensions'),502,_('Dimension Details'),
    //array(	_('Dimension'),'DIMENSIONS'),
    //		_('Comments'),Report::TEXTBOX)));
  }
  $reports->addReportClass(_('Banking'));
  $reports->addReport(
    _('Banking'),
    601,
    _('Bank &Statement'),
    array(
         _('Bank Accounts') => Report::BANK_ACCOUNTS,
         _('Start Date')    => Report::DATEBEGINM,
         _('End Date')      => Report::DATEENDM,
         _('Comments')      => Report::TEXTBOX,
         _('Destination')   => Report::DESTINATION
    )
  );
  $reports->addReportClass(_('General Ledger'));
  $reports->addReport(
    _('General Ledger'),
    701,
    _('Chart of &Accounts'),
    array(
         _('Show Balances') => Report::YES_NO,
         _('Comments')      => Report::TEXTBOX,
         _('Destination')   => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('General Ledger'),
    702,
    _('List of &Journal Entries'),
    array(
         _('Start Date')  => Report::DATEBEGINM,
         _('End Date')    => Report::DATEENDM,
         _('Type')        => Report::SYS_TYPES,
         _('Comments')    => Report::TEXTBOX,
         _('Destination') => Report::DESTINATION
    )
  );
  //$reports->addReport(_('General Ledger'),703,_('GL Account Group Summary'),
  //	array(	_('Comments'),Report::TEXTBOX)));
  if ($dim == 2) {
    $reports->addReport(
      _('General Ledger'),
      704,
      _('GL Account &Transactions'),
      array(
           _('Start Date')       => Report::DATEBEGINM,
           _('End Date')         => Report::DATEENDM,
           _('From Account')     => Report::GL_ACCOUNTS,
           _('To Account')       => Report::GL_ACCOUNTS,
           _('Dimension') . " 1" => Report::DIMENSIONS1,
           _('Dimension') . " 2" => Report::DIMENSIONS2,
           _('Comments')         => Report::TEXTBOX,
           _('Destination')      => Report::DESTINATION
      )
    );
    $reports->addReport(
      _('General Ledger'),
      705,
      _('Annual &Expense Breakdown'),
      array(
           _('Year')             => Report::TRANS_YEARS,
           _('Dimension') . " 1" => Report::DIMENSIONS1,
           _('Dimension') . " 2" => Report::DIMENSIONS2,
           _('Comments')         => Report::TEXTBOX,
           _('Destination')      => Report::DESTINATION
      )
    );
    $reports->addReport(
      _('General Ledger'),
      706,
      _('&Balance Sheet'),
      array(
           _('Start Date')       => Report::DATEBEGIN,
           _('End Date')         => Report::DATEENDM,
           _('Dimension') . " 1" => Report::DIMENSIONS1,
           _('Dimension') . " 2" => Report::DIMENSIONS2,
           _('Decimal values')   => Report::YES_NO,
           _('Graphics')         => Report::GRAPHIC,
           _('Comments')         => Report::TEXTBOX,
           _('Destination')      => Report::DESTINATION
      )
    );
    $reports->addReport(
      _('General Ledger'),
      707,
      _('&Profit and Loss Statement'),
      array(
           _('Start Date')       => Report::DATEBEGINM,
           _('End Date')         => Report::DATEENDM,
           _('Compare to')       => Report::COMPARE,
           _('Dimension') . " 1" => Report::DIMENSIONS1,
           _('Dimension') . " 2" => Report::DIMENSIONS2,
           _('Decimal values')   => Report::YES_NO,
           _('Graphics')         => Report::GRAPHIC,
           _('Comments')         => Report::TEXTBOX,
           _('Destination')      => Report::DESTINATION
      )
    );
    $reports->addReport(
      _('General Ledger'),
      708,
      _('Trial &Balance'),
      array(
           _('Start Date')       => Report::DATEBEGINM,
           _('End Date')         => Report::DATEENDM,
           _('Zero values')      => Report::YES_NO,
           _('Only balances')    => Report::YES_NO,
           _('Dimension') . " 1" => Report::DIMENSIONS1,
           _('Dimension') . " 2" => Report::DIMENSIONS2,
           _('Comments')         => Report::TEXTBOX,
           _('Destination')      => Report::DESTINATION
      )
    );
  } else {
    if ($dim == 1) {
      $reports->addReport(
        _('General Ledger'),
        704,
        _('GL Account &Transactions'),
        array(
             _('Start Date')   => Report::DATEBEGINM,
             _('End Date')     => Report::DATEENDM,
             _('From Account') => Report::GL_ACCOUNTS,
             _('To Account')   => Report::GL_ACCOUNTS,
             _('Dimension')    => Report::DIMENSIONS1,
             _('Comments')     => Report::TEXTBOX,
             _('Destination')  => Report::DESTINATION
        )
      );
      $reports->addReport(
        _('General Ledger'),
        705,
        _('Annual &Expense Breakdown'),
        array(
             _('Year')        => Report::TRANS_YEARS,
             _('Dimension')   => Report::DIMENSIONS1,
             _('Comments')    => Report::TEXTBOX,
             _('Destination') => Report::DESTINATION
        )
      );
      $reports->addReport(
        _('General Ledger'),
        706,
        _('&Balance Sheet'),
        array(
             _('Start Date')     => Report::DATEBEGIN,
             _('End Date')       => Report::DATEENDM,
             _('Dimension')      => Report::DIMENSIONS1,
             _('Decimal values') => Report::YES_NO,
             _('Graphics')       => Report::GRAPHIC,
             _('Comments')       => Report::TEXTBOX,
             _('Destination')    => Report::DESTINATION
        )
      );
      $reports->addReport(
        _('General Ledger'),
        707,
        _('&Profit and Loss Statement'),
        array(
             _('Start Date')     => Report::DATEBEGINM,
             _('End Date')       => Report::DATEENDM,
             _('Compare to')     => Report::COMPARE,
             _('Dimension')      => Report::DIMENSIONS1,
             _('Decimal values') => Report::YES_NO,
             _('Graphics')       => Report::GRAPHIC,
             _('Comments')       => Report::TEXTBOX,
             _('Destination')    => Report::DESTINATION
        )
      );
      $reports->addReport(
        _('General Ledger'),
        708,
        _('Trial &Balance'),
        array(
             _('Start Date')    => Report::DATEBEGINM,
             _('End Date')      => Report::DATEENDM,
             _('Zero values')   => Report::YES_NO,
             _('Only balances') => Report::YES_NO,
             _('Dimension')     => Report::DIMENSIONS1,
             _('Comments')      => Report::TEXTBOX,
             _('Destination')   => Report::DESTINATION
        )
      );
    } else {
      $reports->addReport(
        _('General Ledger'),
        704,
        _('GL Account &Transactions'),
        array(
             _('Start Date')   => Report::DATEBEGINM,
             _('End Date')     => Report::DATEENDM,
             _('From Account') => Report::GL_ACCOUNTS,
             _('To Account')   => Report::GL_ACCOUNTS,
             _('Comments')     => Report::TEXTBOX,
             _('Destination')  => Report::DESTINATION
        )
      );
      $reports->addReport(
        _('General Ledger'),
        705,
        _('Annual &Expense Breakdown'),
        array(
             _('Year')        => Report::TRANS_YEARS,
             _('Comments')    => Report::TEXTBOX,
             _('Destination') => Report::DESTINATION
        )
      );
      $reports->addReport(
        _('General Ledger'),
        706,
        _('&Balance Sheet'),
        array(
             _('Start Date')     => Report::DATEBEGIN,
             _('End Date')       => Report::DATEENDM,
             _('Decimal values') => Report::YES_NO,
             _('Graphics')       => Report::GRAPHIC,
             _('Comments')       => Report::TEXTBOX,
             _('Destination')    => Report::DESTINATION
        )
      );
      $reports->addReport(
        _('General Ledger'),
        707,
        _('&Profit and Loss Statement'),
        array(
             _('Start Date')     => Report::DATEBEGINM,
             _('End Date')       => Report::DATEENDM,
             _('Compare to')     => Report::COMPARE,
             _('Decimal values') => Report::YES_NO,
             _('Graphics')       => Report::GRAPHIC,
             _('Comments')       => Report::TEXTBOX,
             _('Destination')    => Report::DESTINATION
        )
      );
      $reports->addReport(
        _('General Ledger'),
        708,
        _('Trial &Balance'),
        array(
             _('Start Date')    => Report::DATEBEGINM,
             _('End Date')      => Report::DATEENDM,
             _('Zero values')   => Report::YES_NO,
             _('Only balances') => Report::YES_NO,
             _('Comments')      => Report::TEXTBOX,
             _('Destination')   => Report::DESTINATION
        )
      );
    }
  }
  $reports->addReport(
    _('General Ledger'),
    709,
    _('Ta&x Report'),
    array(
         _('Start Date')   => Report::DATEBEGINTAX,
         _('End Date')     => Report::DATEENDTAX,
         _('Summary Only') => Report::YES_NO,
         _('Comments')     => Report::TEXTBOX,
         _('Destination')  => Report::DESTINATION
    )
  );
  $reports->addReport(
    _('General Ledger'),
    710,
    _('Audit Trail'),
    array(
         _('Start Date')  => Report::DATEBEGINM,
         _('End Date')    => Report::DATEENDM,
         _('Type')        => Report::SYS_TYPES_ALL,
         _('User')        => Report::USERS,
         _('Comments')    => Report::TEXTBOX,
         _('Destination') => Report::DESTINATION
    )
  );
  $reports->add_custom_reports($reports);
  $js = " showClass(" . $_GET['Class'] . ")";
  JS::_onload($js);
  echo $reports->getDisplay();
  Page::end();
