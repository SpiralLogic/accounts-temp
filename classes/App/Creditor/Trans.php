<?php
  use ADV\App\User;
  use ADV\App\Dates;
  use ADV\Core\DB\DB;
  use ADV\App\SysTypes;
  use ADV\App\Tax\Tax;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /* Definition of the Supplier Transactions class to hold all the information for an accounts payable invoice or credit note
  */
  class Creditor_Trans {
    /** @var null * */
    protected static $_instance = null;
    /***
     * @static
     *
     * @param bool $reset_session
     *
     * @return Creditor_Trans
     */
    public static function i($reset_session = false) {
      if (!$reset_session && isset($_SESSION["Creditor_Trans"])) {
        static::$_instance = $_SESSION["Creditor_Trans"];
      } elseif (static::$_instance === null) {
        static::$_instance = $_SESSION["Creditor_Trans"] = new static;
      }
      return static::$_instance;
    }
    public static function killInstance() {
      unset($_SESSION["Creditor_Trans"]);
    }
    /** @var Purch_GLItem[] * */
    public $grn_items; /*array of objects of class GRNDetails using the GRN No as the pointer */
    /** @var array * */
    public $gl_codes; /*array of objects of class gl_codes using a counter as the pointer */
    /** @var */
    public $creditor_id;
    /** @var */
    public $supplier_name;
    /** @var */
    public $terms_description;
    /** @var */
    public $terms;
    /** @var */
    public $tax_description;
    /** @var */
    public $tax_group_id;
    /** @var */
    public $is_invoice;
    /** @var */
    public $Comments;
    /** @var */
    public $tran_date;
    /** @var */
    public $due_date;
    /** @var */
    public $supplier_reference;
    /** @var */
    public $reference;
    /** @var */
    public $ov_amount;
    /** @var */
    public $ov_discount;
    /** @var int * */
    public $tax_correction = 0;
    /** @var int * */
    public $total_correction = 0;
    /** @var int * */
    public $gl_codes_counter = 0;
    /**

     */
    public function __construct() {
      /*Constructor function initialises a new Supplier Transaction object */
      $this->grn_items = [];
      $this->gl_codes  = [];
    }
    /**
     * @param      $grn_item_id
     * @param      $po_detail_item
     * @param      $item_code
     * @param      $description
     * @param      $qty_recd
     * @param      $prev_quantity_inv
     * @param      $this_quantity_inv
     * @param      $order_price
     * @param      $chg_price
     * @param      $Complete
     * @param      $std_cost_unit
     * @param      $gl_code
     * @param int  $discount
     * @param null $exp_price
     *
     * @return int
     */
    public function add_grn_to_trans(
      $grn_item_id,
      $po_detail_item,
      $item_code,
      $description,
      $qty_recd,
      $prev_quantity_inv,
      $this_quantity_inv,
      $order_price,
      $chg_price,
      $Complete,
      $std_cost_unit,
      $gl_code,
      $discount = 0,
      $exp_price = null
    ) {
      $this->grn_items[$grn_item_id] = new Purch_GLItem($grn_item_id, $po_detail_item, $item_code, $description, $qty_recd, $prev_quantity_inv, $this_quantity_inv, $order_price, $chg_price, $Complete, $std_cost_unit, $gl_code, $discount, $exp_price);
      return 1;
    }
    /**
     * @param $gl_code
     * @param $gl_act_name
     * @param $gl_dim
     * @param $gl_dim2
     * @param $amount
     * @param $memo_
     *
     * @return int
     */
    public function add_gl_codes_to_trans($gl_code, $gl_act_name, $gl_dim, $gl_dim2, $amount, $memo_) {
      $this->gl_codes[$this->gl_codes_counter] = new Purch_GLCode($this->gl_codes_counter, $gl_code, $gl_act_name, $gl_dim, $gl_dim2, $amount, $memo_);
      $this->gl_codes_counter++;
      return 1;
    }
    /**
     * @param $grn_item_id
     */
    public function remove_grn_from_trans($grn_item_id) {
      unset($this->grn_items[$grn_item_id]);
    }
    /**
     * @param $gl_code_counter
     */
    public function remove_gl_codes_from_trans(&$gl_code_counter) {
      unset($this->gl_codes[$gl_code_counter]);
    }
    /**
     * @return bool
     */
    public function is_valid_trans_to_post() {
      return (count($this->grn_items) > 0 || count($this->gl_codes) > 0 || ($this->ov_amount != 0) || ($this->ov_discount > 0));
    }
    public function clear_items() {
      unset($this->grn_items, $this->gl_codes);
      $this->ov_amount = $this->ov_discount = $this->creditor_id = $this->tax_correction = $this->total_correction = 0;
      $this->grn_items = [];
      $this->gl_codes  = [];
    }
    /**
     * @param null $tax_group_id
     * @param int  $shipping_cost
     * @param bool $gl_codes
     *
     * @return array|null
     */
    public function get_taxes($tax_group_id = null, $shipping_cost = 0, $gl_codes = true) {
      $items  = [];
      $prices = [];
      if ($tax_group_id == null) {
        $tax_group_id = $this->tax_group_id;
      }
      $tax_group = Tax_Groups::get_items_as_array($tax_group_id);
      foreach ($this->grn_items as $line) {
        $items[]  = $line->item_code;
        $prices[] = Num::_round(($line->this_quantity_inv * $line->taxfree_charge_price($tax_group_id, $tax_group)), User::_price_dec());
      }
      if ($tax_group_id == null) {
        $tax_group_id = $this->tax_group_id;
      }
      $taxes = Tax::for_items($items, $prices, $shipping_cost, $tax_group_id);
      ///////////////// Joe Hunt 2009.08.18
      if ($gl_codes) {
        foreach ($this->gl_codes as $gl_code) {
          $index = Tax::is_account($gl_code->gl_code);
          if ($index !== false) {
            $taxes[$index]['Value'] += $gl_code->amount;
          }
        }
      }
      ////////////////
      return $taxes;
    }
    /**
     * @param null $tax_group_id
     *
     * @return int
     */
    public function get_total_charged($tax_group_id = null) {
      $total = 0;
      // preload the taxgroup !
      if ($tax_group_id != null) {
        $tax_group = Tax_Groups::get_items_as_array($tax_group_id);
      } else {
        $tax_group = null;
      }
      foreach ($this->grn_items as $line) {
        $total += ($line->this_quantity_inv * $line->taxfree_charge_price($tax_group_id, $tax_group));
      }
      foreach ($this->gl_codes as $gl_line) { //////// 2009-08-18 Joe Hunt
        if (!Tax::is_account($gl_line->gl_code)) {
          $total += $gl_line->amount;
        }
      }
      return $total;
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $creditor_id
     * @param        $date_
     * @param        $due_date
     * @param        $reference
     * @param        $supplier_reference
     * @param        $amount
     * @param        $amount_tax
     * @param        $discount
     * @param string $err_msg
     * @param int    $rate
     *
     * @return int
     */
    public static function add($type, $creditor_id, $date_, $due_date, $reference, $supplier_reference, $amount, $amount_tax, $discount, $err_msg = "", $rate = 0) {
      $date = Dates::_dateToSql($date_);
      if ($due_date == "") {
        $due_date = "0000-00-00";
      } else {
        $due_date = Dates::_dateToSql($due_date);
      }
      $trans_no = SysTypes::get_next_trans_no($type);
      $curr     = Bank_Currency::for_creditor($creditor_id);
      if ($rate == 0) {
        $rate = Bank_Currency::exchange_rate_from_home($curr, $date_);
      }
      $sql
        = "INSERT INTO creditor_trans (trans_no, type, creditor_id, tran_date, due_date,
				reference, supplier_reference, ov_amount, ov_gst, rate, ov_discount) ";
      $sql .= "VALUES (" . DB::_escape($trans_no) . ", " . DB::_escape($type) . ", " . DB::_escape($creditor_id) . ", '$date', '$due_date',
				" . DB::_escape($reference) . ", " . DB::_escape($supplier_reference) . ", " . DB::_escape($amount) . ", " . DB::_escape($amount_tax) . ", " . DB::_escape(
        $rate
      ) . ", " . DB::_escape($discount) . ")";
      if ($err_msg == "") {
        $err_msg = "Cannot insert a supplier transaction record";
      }
      DB::_query($sql, $err_msg);
      DB_AuditTrail::add($type, $trans_no, $date_);
      return $trans_no;
    }
    /**
     * @static
     *
     * @param $trans_no
     * @param $trans_type
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get($trans_no, $trans_type = -1) {
      $sql
        = "SELECT creditor_trans.*, (creditor_trans.ov_amount+creditor_trans.ov_gst+creditor_trans.ov_discount) AS Total,
				suppliers.name AS supplier_name, suppliers.curr_code AS SupplierCurrCode ";
      if ($trans_type == ST_SUPPAYMENT) {
        // it's a payment so also get the bank account
        $sql
          .= ", bank_accounts.bank_name, bank_accounts.bank_account_name, bank_accounts.bank_curr_code,
					bank_accounts.account_type AS BankTransType, bank_trans.amount AS BankAmount,
					bank_trans.ref ";
      }
      $sql .= " FROM creditor_trans, suppliers ";
      if ($trans_type == ST_SUPPAYMENT) {
        // it's a payment so also get the bank account
        $sql .= ", bank_trans, bank_accounts";
      }
      $sql .= " WHERE creditor_trans.trans_no=" . DB::_escape($trans_no) . "
				AND creditor_trans.creditor_id=suppliers.creditor_id";
      if ($trans_type > 0) {
        $sql .= " AND creditor_trans.type=" . DB::_escape($trans_type);
      }
      if ($trans_type == ST_SUPPAYMENT) {
        // it's a payment so also get the bank account
        $sql .= " AND bank_trans.trans_no =" . DB::_escape($trans_no) . "
					AND bank_trans.type=" . DB::_escape($trans_type) . "
					AND bank_accounts.id=bank_trans.bank_act ";
      }
      $result = DB::_query($sql, "Cannot retreive a supplier transaction");
      if (DB::_numRows($result) == 0) {
        // can't return nothing
        Event::error("no supplier trans found for given params", $sql, true);
        exit;
      }
      if (DB::_numRows($result) > 1) {
        // can't return multiple
        Event::error("duplicate supplier transactions found for given params", $sql, true);
        exit;
      }
      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     *
     * @return bool
     */
    public static function exists($type, $type_no) {
      if ($type == ST_SUPPRECEIVE) {
        return Purch_GRN::exists($type_no);
      }
      $sql    = "SELECT trans_no FROM creditor_trans WHERE type=" . DB::_escape($type) . "
				AND trans_no=" . DB::_escape($type_no);
      $result = DB::_query($sql, "Cannot retreive a supplier transaction");
      return (DB::_numRows($result) > 0);
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    public static function void($type, $type_no) {
      $sql
        = "UPDATE creditor_trans SET ov_amount=0, ov_discount=0, ov_gst=0,
				alloc=0 WHERE type=" . DB::_escape($type) . " AND trans_no=" . DB::_escape($type_no);
      DB::_query($sql, "could not void supp transactions for type=$type and trans_no=$type_no");
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     *
     * @return bool
     */
    public static function post_void($type, $type_no) {
      if ($type == ST_SUPPAYMENT) {
        Creditor_Payment::void($type, $type_no);
        return true;
      }
      if ($type == ST_SUPPINVOICE || $type == ST_SUPPCREDIT) {
        Purch_Invoice::void($type, $type_no);
        return true;
      }
      if ($type == ST_SUPPRECEIVE) {
        return Purch_GRN::void(ST_SUPPRECEIVE, $type_no);
      }
      return false;
    }
    // add a supplier-related gl transaction
    // $date_ is display date (non-sql)
    // $amount is in SUPPLIERS'S currency
    /**
     * @static
     *
     * @param        $type
     * @param        $type_no
     * @param        $date_
     * @param        $account
     * @param        $dimension
     * @param        $dimension2
     * @param        $amount
     * @param        $creditor_id
     * @param string $err_msg
     * @param int    $rate
     * @param string $memo
     *
     * @return float
     */
    public static function add_gl($type, $type_no, $date_, $account, $dimension, $dimension2, $amount, $creditor_id, $err_msg = "", $rate = 0, $memo = "") {
      if ($err_msg == "") {
        $err_msg = "The supplier GL transaction could not be inserted";
      }
      return GL_Trans::add(
        $type,
        $type_no,
        $date_,
        $account,
        $dimension,
        $dimension2,
        $memo,
        $amount,
        Bank_Currency::for_creditor($creditor_id),
        PT_SUPPLIER,
        $creditor_id,
        $err_msg,
        $rate
      );
    }
    /**
     * @static
     *
     * @param $creditor_id
     * @param $stock_id
     *
     * @return int
     */
    public static function get_conversion_factor($creditor_id, $stock_id) {
      $sql
              = "SELECT conversion_factor FROM purch_data
					WHERE creditor_id = " . DB::_escape($creditor_id) . "
					AND stock_id = " . DB::_escape($stock_id);
      $result = DB::_query($sql, "The supplier pricing details for " . $stock_id . " could not be retrieved");
      if (DB::_numRows($result) == 1) {
        $myrow = DB::_fetch($result);
        return $myrow['conversion_factor'];
      } else {
        return 1;
      }
    }
    /**
     * @static
     *
     * @param     $tax_items
     * @param     $columns
     * @param int $tax_recorded
     */
    public static function trans_tax_details($tax_items, $columns, $tax_recorded = 0) {
      $tax_total = 0;
      while ($tax_item = DB::_fetch($tax_items)) {
        $tax = Num::_format(abs($tax_item['amount']), User::_price_dec());
        if ($tax_item['included_in_price']) {
          Table::label(
            _("Included") . " " . $tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%) " . _("Amount") . ": $tax",
            "colspan=$columns class='alignright'",
            "class='alignright'"
          );
        } else {
          Table::label($tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%)", $tax, "colspan=$columns class='alignright'", "class='alignright'");
        }
        $tax_total += $tax;
      }
      if ($tax_recorded != 0) {
        $tax_correction = Num::_format($tax_recorded - $tax_total, User::_price_dec());
        Table::label("Tax Correction ", $tax_correction, "colspan=$columns class='alignright'", "class='alignright'");
      }
    }
    /**
     * @static
     *
     * @param $creditor_trans
     */
    public static function get_duedate_from_terms($creditor_trans) {
      if (!Dates::_isDate($creditor_trans->tran_date)) {
        $creditor_trans->tran_date = Dates::_today();
      }
      if (substr($creditor_trans->terms, 0, 1) == "1") { /*Its a day in the following month when due */
        $creditor_trans->due_date = Dates::_addDays(Dates::_endMonth($creditor_trans->tran_date), (int) substr($creditor_trans->terms, 1));
      } else { /*Use the Days Before Due to add to the invoice date */
        $creditor_trans->due_date = Dates::_addDays($creditor_trans->tran_date, (int) substr($creditor_trans->terms, 1));
      }
    }
  } /* end of class defintion */

