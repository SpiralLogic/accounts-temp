<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App;

  /** **/
  class Report
  {
    /** @var */
    public $id;
    /** @var */
    public $name;
    /** @var */
    public $ar_params;
    /** @var */
    public $controls;
    const DATEBEGIN                = 'DATEBEGIN';
    const DATEENDM                 = 'DATEENDM';
    const CUSTOMERS_NO_FILTER      = 'CUSTOMERS_NO_FILTER';
    const CUSTOMERS_NOZERO_BALANCE = 'CUSTOMERS_NOZERO_BALANCE';
    const CURRENCY                 = 'CURRENCY';
    const YES_NO                   = 'YES_NO';
    const DATE                     = 'DATE';
    const AREAS                    = 'AREAS';
    const TEXT                     = 'TEXT';
    const CATEGORIES               = 'CATEGORIES';
    const LOCATIONS                = 'LOCATIONS';
    const ITEMS                    = 'ITEMS';
    const DIMENSION                = 'DIMENSION';
    const BANK_ACCOUNTS            = 'BANK_ACCOUNTS';
    const SALESTYPES               = 'SALESTYPES';
    const PO                       = 'PO';
    const REMITTANCE               = 'REMITTANCE';
    const DATEBEGINM               = 'DATEBEGINM';
    const SYS_TYPES_ALL            = 'SYS_TYPES_ALL';
    const DATEENDTAX               = 'DATEENDTAX';
    const DATEBEGINTAX             = 'DATEBEGINTAX';
    const TRANS_YEARS              = 'TRANS_YEARS';
    const DIMENSIONS1              = 'DIMENSIONS1';
    const COMPARE                  = 'COMPARE';
    const GL_ACCOUNTS              = 'GL_ACCOUNTS';
    const DIMENSIONS2              = 'DIMENSIONS2';
    const ORDERS                   = 'ORDERS';
    const QUOTATIONS               = 'QUOTATIONS';
    const WORKORDER                = 'WORKORDER';
    const SYS_TYPES                = 'SYS_TYPES';
    const DATEMONTH                = 'DATEMONTH';
    const PAYMENT_LINK             = 'PAYMENT_LINK';
    const INVOICE                  = 'INVOICE';
    const DELIVERY                 = 'DELIVERY';
    const RECEIPT                  = 'RECEIPT';
    const USERS                    = 'USERS';
    const SALESMEN                 = 'SALESMEN';
    const TEXTBOX                  = 'TEXTBOX';
    const DESTINATION              = 'DESTINATION';
    const SUPPLIERS_NO_FILTER      = 'SUPPLIERS_NO_FILTER';
    const GRAPHIC                  = 'GRAPHIC';
    /**
     * @param      $id
     * @param      $name
     * @param null $ar_params
     */
    public function __construct($id, $name, $ar_params = null) {
      $this->id   = $id;
      $this->name = $name;
      if ($ar_params) {
        $this->set_controls($ar_params);
      }
    }
    public function get_controls() {
      return $this->controls;
    }
    public function add_custom_reports() {
      $file = PATH_COMPANY . "reporting/reports_custom.php";
      if (file_exists($file)) {
        /** @noinspection PhpIncludeInspection */
        include_once($file);
      }
    }
    /**
     * @param $ar_params
     */
    protected function set_controls($ar_params) {
      $this->controls = $ar_params;
    }
  }
