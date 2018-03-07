<?php
    namespace Reports;

    /** **/
    class Report
    {
        const CUST_NO            = "Cust no";
        const DATE               = "Date";
        const STATEMENT_NOTE     = "IMPORTANT PLEASE PASS THIS ON TO YOUR ACCOUNTS DEPARTMENT ASAP";
        const PAYMENT_TERMS_NOTE = "If you do not have an account, our terms are Pre payments only. All accounts are 30 days Cash, cheque, Visa, MasterCard, or Direct deposit";
        const CHARGE_TO          = "Charge To";
        const DELIVERED_TO       = "Delivered To";
        const SHIPPING_COMPANY   = "Shipping Company";
        const DUE_DATE           = "Due Date";
        const YOUR_REF           = "Your Ref";
        const OUR_REF            = "Contact";
        const YOUR_TAX_NO        = "Your GST no.";
        const PAYMENT_TERMS      = "Payment Terms";
        const CUSTOMERS_REF      = "Customers Reference";
        const OUR_ORDER_NO       = "Our Order No";
        const OUR_TAX_NO         = "Our GST No.";
        const SUBURB             = "Suburb";
        const PLEASE_QUOTE       = "Please quote Credit no. when paying. All amounts stated in ";
        const BANK_ACCOUNT       = "Bank Account";
        const ADDRESS            = "Address";
        const PHONE_FAX_EMAIL    = "Phone/Fax/Email";
        const BANK               = "Bank";
        const PAYMENT_LINK       = "You can pay through";
        const TITLE              = "STATEMENT";
        const DEAR_SIRS          = "Dear";
        const ATTACHEDFILE       = "Attached you will find ";
        const KINDEST_REGARDS    = "Kindest Regards";
        const DELIVERY_NO        = "Delivery Note No.";
        const ORDER_NO           = "Order No.";
        const OUTSTANDING        = "Outstanding Transactions";
        const CURRENT            = "Current";
        const TOTAL_BALANCE      = "Total Balance";
        const TOTAL_PO_EX_TAX    = "Total Ex GST";
        const SUBTOTAL           = "Subtotal";
        const STATEMENT          = "Statement";
        const AS_OF              = "as of";
        const DAYS               = "Days";
        const OVER               = "Over";
        /** @var array * */
        public $headers = array('Trans Type', 'Invoice#', 'Date', 'Due Date', 'Charges', 'Credits', 'Allocated', 'Outstanding');
        /** @var array * */
        public $doctypes = [
            ST_CUSTDELIVERY => "Delivery Note No.",
            ST_CUSTPAYMENT  => "Receipt No.",
            ST_CUSTREFUND   => "Refund No.",
            ST_SUPPAYMENT   => "Remittance No.",
            ST_PURCHORDER   => "Purchase Order No.",
            ST_SALESORDER   => "Order No.",
            ST_SALESINVOICE => "Invoice No.",
            ST_SALESQUOTE   => "Quotation No.",
            ST_WORKORDER    => "Work Order No.",
            ST_CUSTCREDIT   => "Credit No."
        ];
    }
