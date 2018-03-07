<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /*
     Security sections groups various areas on both functionality and privilege levels.
     Often analytic inquires are available only for management, and configuration
     for administration or management staff. This is why we have those three
     section type inside near every ADV module.

     Section codes 0-99 are reserved for core ADV functionalities.
     Every security section can contain up to 256 different areas.
     External modules can extend security roles system by adding rows to
     $security_sections and User::_i()->Security['areas'] using section codes >=100.
     Security areas and sections created by extension modules/plugins
     have dynamically assigned 3-byte integer codes. The highest byte is zero
     for sections/areas defined in this file, and extid+1 for those defined
     by extensions
   */
  return [
      'sections' => [
          SS_SADMIN  => "System administration",
      SS_SETUP   => "Company setup",
      SS_SPEC    => "Special maintenance",
      SS_SALES_C => "Sales configuration",
      SS_SALES   => "Sales transactions",
      SS_SALES_A => "Sales related reports",
      SS_PURCH_C => "Purchase configuration",
      SS_PURCH   => "Purchase transactions",
      SS_PURCH_A => "Purchase analytics",
      SS_ITEMS_C => "Inventory configuration",
      SS_ITEMS   => "Inventory operations",
      SS_ITEMS_A => "Inventory analytics",
      SS_MANUF_C => "Manufacturing configuration",
      SS_MANUF   => "Manufacturing transations",
      SS_MANUF_A => "Manufacturing analytics",
      SS_DIM_C   => "Dimensions configuration",
      SS_DIM     => "Dimensions",
      SS_GL_C    => "Banking & GL configuration",
      SS_GL      => "Banking & GL transactions",
      SS_GL_A    => "Banking & GL analytics",
      SS_ADV     => "Advanced"
      ],
      'areas'    => [
          //
      //	Advanced
      //
          SA_ADVANCED             => [SS_ADV | 1, "Advanced"],
          //
      //	Site administration
      //
          SA_CREATECOMPANY        => [SS_SADMIN | 1, "Install/update companies"],
          SA_CREATELANGUAGE       => [SS_SADMIN | 2, "Install/update languages"],
          SA_CREATEMODULES        => [SS_SADMIN | 3, "Install/upgrade modules"],
          SA_SOFTWAREUPGRADE      => [SS_SADMIN | 4, "Software upgrades"],
          SA_DEBUGGING            => [SS_SADMIN | 5, "Debugging"],
          //
      //	Company setup
      //
          SA_SETUPCOMPANY         => [SS_SETUP | 1, "Company parameters"],
          SA_SECROLES             => [SS_SETUP | 2, "Access levels edition"],
          SA_USERS                => [SS_SETUP | 3, "Users setup"],
          SA_POSSETUP             => [SS_SETUP | 4, "Point of sales definitions"],
          SA_PRINTERS             => [SS_SETUP | 5, "Printers configuration"],
          SA_PRINTPROFILE         => [SS_SETUP | 6, "Print profiles"],
          SA_PAYTERMS             => [SS_SETUP | 7, "Payment terms"],
          SA_SHIPPING             => [SS_SETUP | 8, "Shipping ways"],
          SA_CRSTATUS             => [SS_SETUP | 9, "Credit status definitions changes"],
          SA_INVENTORYLOCATION    => [SS_SETUP | 10, "Inventory locations changes"],
          SA_INVENTORYMOVETYPE    => [SS_SETUP | 11, "Inventory movement types"],
          SA_WORKCENTRES          => [SS_SETUP | 12, "Manufacture work centres"],
          SA_FORMSETUP            => [SS_SETUP | 13, "Forms setup"],
          //
      // Special and common functions
      //
          SA_VOIDTRANSACTION      => [SS_SPEC | 1, "Voiding transactions"],
          SA_BACKUP               => [SS_SPEC | 2, "Database backup/restore"],
          SA_VIEWPRINTTRANSACTION => [SS_SPEC | 3, "Common view/print transactions interface"],
          SA_ATTACHDOCUMENT       => [SS_SPEC | 4, "Attaching documents"],
          SA_SETUPDISPLAY         => [SS_SPEC | 5, "Display preferences"],
          //???
          SA_CHGPASSWD            => [SS_SPEC | 6, "Password changes"],
          //???
      //
      // Sales related functionality
      //
          SA_SALESTYPES           => [SS_SALES_C | 1, "Sales types"],
          SA_SALESPRICE           => [SS_SALES_C | 2, "Sales prices edition"],
          SA_SALESMAN             => [SS_SALES_C | 3, "Sales staff maintenance"],
          SA_SALESAREA            => [SS_SALES_C | 4, "Sales areas maintenance"],
          SA_SALESGROUP           => [SS_SALES_C | 5, "Sales groups changes"],
          SA_STEMPLATE            => [SS_SALES_C | 6, "Sales templates"],
          SA_SRECURRENT           => [SS_SALES_C | 7, "Recurrent invoices definitions"],
          SA_SALESTRANSVIEW       => [SS_SALES | 1, "Sales transactions view"],
          SA_CUSTOMER             => [SS_SALES | 2, "Sales customer and branches changes"],
          SA_SALESORDER           => [SS_SALES | 3, "Sales orders edition"],
          SA_SALESDELIVERY        => [SS_SALES | 4, "Sales deliveries edition"],
          SA_SALESINVOICE         => [SS_SALES | 5, "Sales invoices edition"],
          SA_SALESCREDITINV       => [SS_SALES | 6, "Sales credit notes against invoice"],
          SA_SALESCREDIT          => [SS_SALES | 7, "Sales freehand credit notes"],
          SA_SALESPAYMNT          => [SS_SALES | 8, "Customer payments entry"],
          SA_SALESALLOC           => [SS_SALES | 9, "Customer payments allocation"],
          SA_SALESQUOTE           => [SS_SALES | 10, "Sales quotations"],
          SA_SALESREFUND          => [SS_SALES | 11, "Customer refund entry"],
          SA_CUSTOMER_CREDIT      => [SS_SALES | 12, "Sales customer credit changes"],
          SA_VOIDINVOICE          => [SS_SALES | 14, "Void Invoices"],
          SA_SALESANALYTIC        => [SS_SALES_A | 1, "Sales analytical reports"],
          SA_SALESBULKREP         => [SS_SALES_A | 2, "Sales document bulk reports"],
          SA_PRICEREP             => [SS_SALES_A | 3, "Sales prices listing"],
          SA_SALESMANREP          => [SS_SALES_A | 4, "Sales staff listing"],
          SA_CUSTBULKREP          => [SS_SALES_A | 5, "Customer bulk listing"],
          SA_CUSTSTATREP          => [SS_SALES_A | 6, "Customer status report"],
          SA_CUSTPAYMREP          => [SS_SALES_A | 7, "Customer payments report"],
          SA_CUSTREFUNDREP        => [SS_SALES_A | 8, "Customer refund report"],
          //
      // Purchase related functions
      //
          SA_PURCHASEPRICING      => [SS_PURCH_C | 1, "Purchase price changes"],
          SA_SUPPTRANSVIEW        => [SS_PURCH | 1, "Supplier transactions view"],
          SA_SUPPLIER             => [SS_PURCH | 2, "Suppliers changes"],
          SA_PURCHASEORDER        => [SS_PURCH | 3, "Purchase order entry"],
          SA_GRN                  => [SS_PURCH | 4, "Purchase receive"],
          SA_SUPPLIERINVOICE      => [SS_PURCH | 5, "Supplier invoices"],
          SA_GRNDELETE            => [SS_PURCH | 9, "Deleting GRN items during invoice entry"],
          SA_SUPPLIERCREDIT       => [SS_PURCH | 6, "Supplier credit notes"],
          SA_SUPPLIERPAYMNT       => [SS_PURCH | 7, "Supplier payments"],
          SA_SUPPLIERALLOC        => [SS_PURCH | 8, "Supplier payments allocations"],
          SA_SUPPLIERANALYTIC     => [SS_PURCH_A | 1, "Supplier analytical reports"],
          SA_SUPPBULKREP          => [SS_PURCH_A | 2, "Supplier document bulk reports"],
          SA_SUPPPAYMREP          => [SS_PURCH_A | 3, "Supplier payments report"],
          //
      // Inventory
      //
          SA_ITEM                 => [SS_ITEMS_C | 1, "Stock items add/edit"],
          SA_SALESKIT             => [SS_ITEMS_C | 2, "Sales kits"],
          SA_ITEMCATEGORY         => [SS_ITEMS_C | 3, "Item categories"],
          SA_UOM                  => [SS_ITEMS_C | 4, "Units of measure"],
          SA_ITEMSSTATVIEW        => [SS_ITEMS | 1, "Stock status view"],
          SA_ITEMSTRANSVIEW       => [SS_ITEMS | 2, "Stock transactions view"],
          SA_FORITEMCODE          => [SS_ITEMS | 3, "Foreign item codes entry"],
          SA_LOCATIONTRANSFER     => [SS_ITEMS | 4, "Inventory location transfers"],
          SA_INVENTORYADJUSTMENT  => [SS_ITEMS | 5, "Inventory adjustments"],
          SA_REORDER              => [SS_ITEMS_A | 1, "Reorder levels"],
          SA_ITEMSANALYTIC        => [SS_ITEMS_A | 2, "Items analytical reports and inquiries"],
          SA_ITEMSVALREP          => [SS_ITEMS_A | 3, "Inventory valuation report"],
          //
      // Manufacturing module
      //
          SA_BOM                  => [SS_MANUF_C | 1, "Bill of Materials"],
          SA_MANUFTRANSVIEW       => [SS_MANUF | 1, "Manufacturing operations view"],
          SA_WORKORDERENTRY       => [SS_MANUF | 2, "Work order entry"],
          SA_MANUFISSUE           => [SS_MANUF | 3, "Material issues entry"],
          SA_MANUFRECEIVE         => [SS_MANUF | 4, "Final product receive"],
          SA_MANUFRELEASE         => [SS_MANUF | 5, "Work order releases"],
          SA_WORKORDERANALYTIC    => [SS_MANUF_A | 1, "Work order analytical reports and inquiries"],
          SA_WORKORDERCOST        => [SS_MANUF_A | 2, "Manufacturing cost inquiry"],
          SA_MANUFBULKREP         => [SS_MANUF_A | 3, "Work order bulk reports"],
          SA_BOMREP               => [SS_MANUF_A | 4, "Bill of materials reports"],
          //
      // Dimensions
      //
          SA_DIMTAGS              => [SS_DIM_C | 1, "Dimension tags"],
          SA_DIMTRANSVIEW         => [SS_DIM | 1, "Dimension view"],
          SA_DIMENSION            => [SS_DIM | 2, "Dimension entry"],
          SA_DIMENSIONREP         => [SS_DIM | 3, "Dimension reports"],
          //
      // Banking and General Ledger
      //
          SA_ITEMTAXTYPE          => [SS_GL_C | 1, "Item tax type definitions"],
          SA_GLACCOUNT            => [SS_GL_C | 2, "GL accounts edition"],
          SA_GLACCOUNTGROUP       => [SS_GL_C | 3, "GL account groups"],
          SA_GLACCOUNTCLASS       => [SS_GL_C | 4, "GL account classes"],
          SA_QUICKENTRY           => [SS_GL_C | 5, "Quick GL entry definitions"],
          SA_CURRENCY             => [SS_GL_C | 6, "Currencies"],
          SA_BANKACCOUNT          => [SS_GL_C | 7, "Bank accounts"],
          SA_TAXRATES             => [SS_GL_C | 8, "Tax rates"],
          SA_TAXGROUPS            => [SS_GL_C | 12, "Tax groups"],
          SA_FISCALYEARS          => [SS_GL_C | 9, "Fiscal years maintenance"],
          SA_GLSETUP              => [SS_GL_C | 10, "Company GL setup"],
          SA_GLACCOUNTTAGS        => [SS_GL_C | 11, "GL Account tags"],
          SA_BANKTRANSVIEW        => [SS_GL | 1, "Bank transactions view"],
          SA_GLTRANSVIEW          => [SS_GL | 2, "GL postings view"],
          SA_EXCHANGERATE         => [SS_GL | 3, "Exchange rate table changes"],
          SA_PAYMENT              => [SS_GL | 4, "Bank payments"],
          SA_DEPOSIT              => [SS_GL | 5, "Bank deposits"],
          SA_BANKTRANSFER         => [SS_GL | 6, "Bank account transfers"],
          SA_RECONCILE            => [SS_GL | 7, "Bank reconciliation"],
          SA_JOURNALENTRY         => [SS_GL | 8, "Manual journal entries"],
          SA_BANKJOURNAL          => [SS_GL | 11, "Journal entries to bank related accounts"],
          SA_BUDGETENTRY          => [SS_GL | 9, "Budget edition"],
          SA_STANDARDCOST         => [SS_GL | 10, "Item standard costs"],
          SA_GLANALYTIC           => [SS_GL_A | 1, "GL analytical reports and inquiries"],
          SA_TAXREP               => [SS_GL_A | 2, "Tax reports and inquiries"],
          SA_BANKREP              => [SS_GL_A | 3, "Bank reports and inquiries"],
          SA_GLREP                => [SS_GL_A | 4, "GL reports and inquiries"],
      ]
  ];
