<?php
  namespace ADV\App\Reports;

  /**
   * @property mixed title
   * @property mixed headers
   */
  trait Doctext
  {
    public $txt_date;
    public $txt_cust_no;
    public $doc_debtor_id;
    public $doc_Charge_To;
    public $doc_Delivered_To;
    public $doc_shipping_company;
    public $doc_Due_Date;
    public $doc_Your_Ref;
    public $doc_Our_Ref;
    public $doc_Your_TAX_no;
    public $doc_Payment_Terms;
    public $doc_Customers_Ref;
    public $doc_Our_Order_No;
    public $doc_Our_TAX_no;
    public $doc_Extra;
    public $doc_Bank_Account;
    public $doc_Please_Quote;
    public $doc_Address;
    public $doc_Phone_Fax_Email;
    public $doc_Bank;
    public $doc_Payment_Link;
    public $doc_Dear_Sirs;
    public $doc_AttachedFile;
    public $doc_Kindest_regards;
    public $doc_invoice_no;
    public $doc_Delivery_no;
    public $doc_Order_no;
    public $doc_sub_total;
    public $doc_shipping;
    public $doc_included;
    public $doc_amount;
    public $doc_total_invoice;
    public $doc_TOTAL_ORDER;
    public $doc_TOTAL_ORDER2;
    public $doc_TOTAL_DELIVERY;
    public $doc_TOTAL_PO;
    public $doc_Towards;
    public $txt_by_Cheque;
    public $txt_dated;
    public $doc_Drawn;
    public $doc_Drawn_Branch;
    public $txt_received;
    public $txt_total_allocated;
    public $txt_left_to_allocate;
    public $txt_total_payment;
    public $txt_outstanding;
    public $txt_opening_balance;
    public $txt_current;
    public $txt_total_balance;
    public $txt_statement;
    public $doc_as_of;
    public $txt_days;
    public $txt_over;
    /**
     * @param int  $doctype
     * @param bool $header2type
     * @param null $linetype
     * @param bool $isproforma
     * @param bool $emailtype
     *
     * @return mixed
     */
    public function getHeaderArray($doctype = 0, $header2type = true, $linetype = null, $isproforma = false, $emailtype = false) {
      global $print_as_quote;
      global $packing_slip;
      $this->txt_date = $return['txt_date'] = _("Date:");
      if (isset($header2type)) {
        $this->txt_cust_no   = $return['txt_cust_no'] = _("Cust no");
        $this->doc_debtor_id = $return['doc_debtor_id'] = "Customer ID";
        if ($doctype == ST_PURCHORDER || $doctype == ST_SUPPAYMENT) { // Purchase Order
          $this->doc_Charge_To = $return['doc_Charge_To'] = _("Order To");
          if ($doctype == ST_PURCHORDER) {
            $this->doc_Delivered_To = $return['doc_Delivered_To'] = _("Deliver To");
          } else {
            $this->doc_Delivered_To = $return['doc_Delivered_To'] = _("Charge To");
          }
        } else {
          if ($doctype == ST_CUSTPAYMENT) {
            $this->doc_Charge_To = $return['doc_Charge_To'] = _("Charged To");
          } elseif ($doctype == ST_CUSTREFUND) {
            $this->doc_Charge_To = $return['doc_Charge_To'] = _("Refunded To");
          } else {
            $this->doc_Charge_To = $return['doc_Charge_To'] = _("Charge To");
          }
          $this->doc_Delivered_To = $return['doc_Delivered_To'] = _("Delivered To");
        }
        $this->doc_shipping_company = $return['doc_shipping_company'] = _("Shipping Company");
        if ($doctype == ST_SALESQUOTE) {
          $this->doc_Due_Date = $return['doc_Due_Date'] = _("Valid until");
        } elseif ($doctype == ST_SALESORDER) {
          $this->doc_Due_Date = $return['doc_Due_Date'] = _("Delivery Date");
        } else {
          $this->doc_Due_Date = $return['doc_Due_Date'] = _("Due Date");
        }
        $this->doc_Your_Ref = $return['doc_Your_Ref'] = _("Your Ref");
        if ($doctype == ST_WORKORDER) {
          $this->doc_Our_Ref       = $return['doc_Our_Ref'] = _("Type");
          $this->doc_Your_TAX_no   = $return['doc_Your_TAX_no'] = _("Manufactured Item");
          $this->doc_Payment_Terms = $return['doc_Payment_Terms'] = _("Required By");
          $this->doc_Customers_Ref = $return['doc_Customers_Ref'] = _("Reference");
          $this->doc_Our_Order_No  = $return['doc_Our_Order_No'] = _("Into Location");
          $this->doc_Due_Date      = $return['doc_Due_Date'] = _("Quantity");
        } else {
          //	if ($doctype == ST_SUPPAYMENT || $doctype == ST_CUSTPAYMENT)
          //			$this->doc_Our_Ref=$return['doc_Our_Ref'] = _("Type");
          $this->doc_Our_Ref = $return['doc_Our_Ref'] = "Contact";
          # __ADVANCEDEDIT__ BEGIN #
          if ($doctype == ST_PURCHORDER) {
            $this->txt_date          = $return['txt_date'] = _("Date:");
            $this->doc_Customers_Ref = $return['doc_Customers_Ref'] = "Acount Number";
            $this->doc_Our_Ref       = $return['doc_Our_Ref'] = "";
            $this->doc_Your_TAX_no   = $return['doc_Your_TAX_no'] = "Phone";
            $this->doc_Our_Order_No  = $return['doc_Our_Order_No'] = "Fax";
            $this->doc_Due_Date      = $return['doc_Due_Date'] = "";
          } elseif ($doctype == ST_PROFORMA) {
            $this->doc_Customers_Ref = $return['doc_Customers_Ref'] = "";
            $this->doc_Our_Ref       = $return['doc_Our_Ref'] = "Contact";
            $this->doc_Your_TAX_no   = $return['doc_Your_TAX_no'] = "";
            $this->doc_Our_Order_No  = $return['doc_Our_Order_No'] = "";
            $this->doc_Due_Date      = $return['doc_Due_Date'] = "Due Date";
            $this->doc_Payment_Terms = $return['doc_Payment_Terms'] = _("Payment Terms");
          } elseif ($doctype == ST_STATEMENT) {
            $this->txt_date          = $return['txt_date'] = "Statement Date:";
            $this->doc_Customers_Ref = $return['doc_Customers_Ref'] = "";
            $this->doc_Our_Ref       = $return['doc_Our_Ref'] = "Customer ID";
            $this->doc_Your_TAX_no   = $return['doc_Your_TAX_no'] = "Phone";
            $this->doc_Our_Order_No  = $return['doc_Our_Order_No'] = "Fax";
            $this->doc_Due_Date      = $return['doc_Due_Date'] = "";
            $this->doc_Payment_Terms = $return['doc_Payment_Terms'] = "Payment Terms";
          } elseif ($doctype == ST_CUSTDELIVERY) {
            $this->doc_Customers_Ref = $return['doc_Customers_Ref'] = "Purchase Order#";
            $this->doc_Payment_Terms = $return['doc_Payment_Terms'] = "";
            $this->doc_Your_TAX_no   = $return['doc_Your_TAX_no'] = "Phone";
            $this->doc_Our_Order_No  = $return['doc_Our_Order_No'] = "Order No:";
          } else {
            $this->doc_Customers_Ref = $return['doc_Customers_Ref'] = ($doctype == ST_SALESQUOTE || $doctype == ST_STATEMENT) ? "" : "Purchase Order#";
            $this->doc_Our_Ref       = $return['doc_Our_Ref'] = "Contact";
            $this->doc_Payment_Terms = $return['doc_Payment_Terms'] = "Payment Terms";
            $this->doc_debtor_id     = $return['doc_debtor_id'] = "Customer ID";
            $this->doc_Your_TAX_no   = $return['doc_Your_TAX_no'] = "Phone";
            $this->doc_Our_Order_No  = $return['doc_Our_Order_No'] = "Fax";
          }
          # __ADVANCEDEDIT__ END #
        }
        $this->doc_Our_TAX_no = $return['doc_Our_TAX_no'] = _("Our ABN No.");
        //	$this->doc_Suburb=$return['doc_Suburb'] = _("Suburb");
        $this->doc_Extra = $return['doc_Extra'] = "";
        if ($doctype == ST_CUSTDELIVERY || $doctype == ST_SALESQUOTE || $doctype == ST_PURCHORDER || $doctype == ST_SALESORDER || $doctype == ST_SUPPAYMENT || $doctype == ST_CUSTPAYMENT || $doctype == ST_CUSTREFUND
        ) {
          if ($doctype == ST_CUSTPAYMENT) {
            $this->doc_Extra = $return['doc_Extra'] = _("* Subject to Realisation of the Cheque.");
          }
          $this->doc_Bank_Account = $return['doc_Bank_Account'] = '';
          $this->doc_Please_Quote = $return['doc_Please_Quote'] = _("All amounts stated in");
        } else {
          $this->doc_Bank_Account = $return['doc_Bank_Account'] = _("Bank Account");
          $this->doc_Please_Quote = $return['doc_Please_Quote'] = $doctype == ST_SALESINVOICE ? _("Please quote Invoice no. when paying. All amounts stated in") :
            _("Please quote Credit no. when paying. All amounts stated in");
        }
        $this->doc_Address         = $return['doc_Address'] = _("Address");
        $this->doc_Phone_Fax_Email = $return['doc_Phone_Fax_Email'] = _("Phone/Fax/Email");
        $this->doc_Bank            = $return['doc_Bank'] = _("Bank");
        $this->doc_Payment_Link    = $return['doc_Payment_Link'] = _("You can pay through");
        if ($doctype == ST_SALESQUOTE || $doctype == ST_PURCHORDER || $doctype == ST_SALESORDER || $doctype == ST_SALESINVOICE || $doctype == ST_CUSTCREDIT || $doctype == ST_CUSTDELIVERY || $doctype == ST_PROFORMA || $doctype == ST_WORKORDER || $doctype == ST_SUPPAYMENT || $doctype == ST_CUSTPAYMENT || $doctype == ST_CUSTREFUND
        ) {
          if ($doctype == ST_SALESQUOTE) {
            $this->title = _("QUOTATION");
          } elseif ($doctype == ST_PURCHORDER) {
            $this->title = _("PURCHASE ORDER");
          } elseif ($doctype == ST_CUSTDELIVERY) {
            $this->title = ($packing_slip == 1 ? _("PACKING SLIP") : _("DELIVERY NOTE"));
          } elseif ($doctype == ST_SALESORDER) {
            $this->title = ($print_as_quote == 1 ? _("QUOTE") : _("ORDER"));
          } elseif ($doctype == ST_SALESINVOICE) {
            $this->title = _("TAX INVOICE");
          } elseif ($doctype == ST_WORKORDER) {
            $this->title = _("WORK ORDER");
          } elseif ($doctype == ST_SUPPAYMENT) {
            $this->title = _("REMITTANCE");
          } elseif ($doctype == ST_CUSTPAYMENT) {
            $this->title = _("RECEIPT");
          } elseif ($doctype == ST_CUSTREFUND) {
            $this->title = _("REFUND");
          } else {
            $this->title = _("CREDIT NOTE");
          }
          if (isset($isproforma) && $isproforma) {
            $this->title = _("PROFORMA INVOICE");
          }
          if ($doctype == ST_PURCHORDER) {
            $this->headers = array(_("Item Code"), _("Item Description"), _("Qty"), _("Unit"), _("Price"), _("Disc"), _("Total"));
          } elseif ($doctype == ST_WORKORDER) {
            $this->headers = array(
              _("Item Code"),
              _("Item Description"),
              _("From Location"),
              _("Work Centre"),
              _("Unit Quantity"),
              _("Total Quantity"),
              _("Units Issued")
            );
          } elseif ($doctype == ST_SUPPAYMENT || $doctype == ST_CUSTPAYMENT || $doctype == ST_CUSTREFUND) {
            $this->headers = array(
              _("Trans Type"),
              _("#"),
              _("Date"),
              _("Due Date"),
              _("Total Amount"),
              _("Left to Allocate"),
              _("This Allocation")
            );
          } elseif ($doctype == ST_CUSTDELIVERY) {
            $this->headers = array(_("Item Code"), _("Item Description"), _("Qty"));
          } else {
            $this->headers = array(_("Item Code"), _("Item Description"), _("Qty"), _("Unit"), _("Price"), _("Disc.%"), _("Tax"), _("Total"));
          }
        } else {
          if ($doctype == ST_STATEMENT) {
            $this->title   = _("STATEMENT");
            $this->headers = array(
              _("Transaction"),
              _("Invoice"),
              _("PO#"),
              _("Date"),
              _("Due"),
              _("Debits"),
              _("Credits"),
              _("Outstanding"),
              _("Balance")
            );
          }
        }
      }
      if (isset($emailtype)) {
        $this->doc_Dear_Sirs       = $return['doc_Dear_Sirs'] = _("Dear");
        $this->doc_AttachedFile    = $return['doc_AttachedFile'] = _("Attached you will find ");
        $this->doc_Kindest_regards = $return['doc_Kindest_regards'] = _("Kindest regards");
        $this->doc_Payment_Link    = $return['doc_Payment_Link'] = _("You can pay through");
      }
      if (isset($header2type) || isset($linetype)) {
        if (isset($header2type) || isset($linetype)) {
          switch ($doctype) {
            case ST_CUSTDELIVERY:
              $this->doc_invoice_no = $return['doc_invoice_no'] = "Delivery Note No.";
              break;
            case ST_CUSTPAYMENT:
              $this->doc_invoice_no = $return['doc_invoice_no'] = "Receipt No.";
              break;
            case ST_CUSTREFUND:
              $this->doc_invoice_no = $return['doc_invoice_no'] = "Refund No.";
              break;
            case ST_SUPPAYMENT:
              $this->doc_invoice_no = $return['doc_invoice_no'] = "Remittance No.";
              break;
            case ST_PURCHORDER:
              $this->doc_invoice_no = $return['doc_invoice_no'] = "Purchase Order #:";
              break;
            case ST_SALESORDER:
              $this->doc_invoice_no = $return['doc_invoice_no'] = "Order No.";
              break;
            case ST_SALESINVOICE:
              $this->doc_invoice_no = $return['doc_invoice_no'] = "Invoice No.";
              break;
            case ST_SALESQUOTE:
              $this->doc_invoice_no = $return['doc_invoice_no'] = "Quotation No.";
              break;
            case ST_WORKORDER:
              $this->doc_invoice_no = $return['doc_invoice_no'] = "Work Order No.";
              break;
            case ST_CUSTCREDIT:
              $this->doc_invoice_no = $return['doc_invoice_no'] = "Credit No.";
              break;
            default:
              $this->doc_invoice_no = $return['doc_invoice_no'] = '';
          }
        }
        $this->doc_Delivery_no = $return['doc_Delivery_no'] = _("Delivery Note No.");
        $this->doc_Order_no    = $return['doc_Order_no'] = _("Order No.");
      }
      if (isset($linetype)) {
        if ($doctype == ST_SALESQUOTE || $doctype == ST_PURCHORDER || $doctype == ST_SALESORDER || $doctype == ST_SALESINVOICE || $doctype == ST_CUSTCREDIT || $doctype == ST_CUSTDELIVERY
        ) {
          $this->doc_sub_total      = $return['doc_sub_total'] = _("Subtotal");
          $this->doc_shipping       = $return['doc_shipping'] = _("Shipping");
          $this->doc_included       = $return['doc_included'] = _("Included");
          $this->doc_amount         = $return['doc_amount'] = _("Amount");
          $this->doc_total_invoice  = $return['doc_total_invoice'] = $doctype == ST_SALESINVOICE ? _("TOTAL INVOICE") : _("TOTAL CREDIT");
          $this->doc_TOTAL_ORDER    = $return['doc_TOTAL_ORDER'] = _("TOTAL ORDER EX GST");
          $this->doc_TOTAL_ORDER2   = $return['doc_TOTAL_ORDER2'] = _("TOTAL ORDER GST INCL.");
          $this->doc_TOTAL_PO       = $return['doc_TOTAL_PO'] = _("TOTAL PO EX GST");
          $this->doc_TOTAL_DELIVERY = $return['doc_TOTAL_DELIVERY'] = _("TOTAL DELIVERY INCL. GST");
        } elseif ($doctype == ST_SUPPAYMENT || ST_CUSTPAYMENT || $doctype == ST_CUSTREFUND) {
          $this->doc_Towards          = $return['doc_Towards'] = _("As advance / full / part / payment towards:");
          $this->txt_by_Cheque        = $return['txt_by_Cheque'] = _("By Cash / Cheque* / Draft No.");
          $this->txt_dated            = $return['txt_dated'] = _("Dated");
          $this->doc_Drawn            = $return['doc_Drawn'] = _("Drawn on Bank");
          $this->doc_Drawn_Branch     = $return['doc_Drawn_Branch'] = _("Branch");
          $this->txt_received         = $return['txt_received'] = _("Received / Sign");
          $this->txt_total_allocated  = $return['txt_total_allocated'] = _("Total Allocated");
          $this->txt_left_to_allocate = $return['txt_left_to_allocate'] = _("Left to Allocate");
          if ($doctype == ST_CUSTPAYMENT) {
            $this->txt_total_payment = $return['txt_total_payment'] = _("TOTAL RECEIPT");
          } elseif ($doctype == ST_CUSTREFUND) {
            $this->txt_total_payment = $return['txt_total_payment'] = _("TOTAL REFUND");
          } else {
            $this->txt_total_payment = $return['txt_total_payment'] = _("TOTAL REMITTANCE");
          }
        }
      }
      if ($doctype == ST_STATEMENT) {
        $this->txt_outstanding     = $return['txt_outstanding'] = _("Outstanding Transactions");
        $this->txt_opening_balance = $return['txt_opening_balance'] = "Opening Balance";
        $this->txt_current         = $return['txt_current'] = _("Current");
        $this->txt_total_balance   = $return['txt_total_balance'] = _("Total Balance");
        $this->txt_statement       = $return['txt_statement'] = _("Your Statement");
        $this->doc_Kindest_regards = $return['doc_Kindest_regards'] = _(
          "Please pass this statement on to your accounts department.\n\nIf this is not the correct email address to send future statements to please respond to this email with the correct address.\n\nKindest regards"
        );
        $this->doc_as_of           = $return['doc_as_of'] = _("as of");
        $this->txt_days            = $return['txt_days'] = _("Days");
        $this->txt_over            = $return['txt_over'] = _("Over");
      }
      return $return;
    }
  }
