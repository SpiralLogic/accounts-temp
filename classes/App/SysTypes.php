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

    //	Returns next transaction number.
    //	Used only for transactions stored in tables without autoincremented key.
  //
  use ADV\Core\Event;
  use ADV\Core\DB\DB;

  /** **/
  class SysTypes
  {
    public static $names
      = array(
        ST_SALESQUOTE   => "Sales Quotation",
        ST_SALESORDER   => "Sales Order",
        ST_PROFORMA     => "Proforma Invoice",
        ST_CUSTDELIVERY => "Sales Delivery",
        ST_SALESINVOICE => "Sales Invoice",
        ST_CUSTCREDIT   => "Sales Credit Note",
        ST_STATEMENT    => "Statement",
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
    public static $short_names
      = array(
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
    /**
     * @static
     *
     * @param $trans_type
     *
     * @return int
     */
    public static function get_next_trans_no($trans_type) {
      $st = SysTypes::get_db_info($trans_type);
      if (!($st && $st[0] && $st[2])) {
        // this is in fact internal error condition.
        Event::error('Internal error: invalid type passed to SysTypes::get_next_trans_no()');
        return 0;
      }
      $sql = "SELECT MAX(`$st[2]`) FROM $st[0]";
      if ($st[1] != null) {
        $sql .= " WHERE `$st[1]`=$trans_type";
      }
      $unique = false;
      $result = DB::_query($sql, "The next transaction number for $trans_type could not be retrieved");
      $myrow  = DB::_fetchRow($result);
      $ref    = $myrow[0];
      while (!$unique) {
        $ref++;
        $sql    = "SELECT id FROM refs WHERE `id`=" . $ref . " AND `type`=" . $trans_type;
        $result = DB::_query($sql);
        $unique = (DB::_numRows($result) > 0) ? false : true;
      }
      return $ref;
    }
    /**
     * @static
     *
     * @param $type
     *
     * @return array|null
     */
    public static function get_db_info($type) {
      switch ($type) {
        case   ST_JOURNAL    :
          return array("gl_trans", "type", "type_no", null, "tran_date");
        case   ST_BANKPAYMENT  :
          return array("bank_trans", "type", "trans_no", "ref", "trans_date");
        case   ST_BANKDEPOSIT  :
          return array("bank_trans", "type", "trans_no", "ref", "trans_date");
        case   3         :
          return null;
        case   ST_BANKTRANSFER :
          return array("bank_trans", "type", "trans_no", "ref", "trans_date");
        case   ST_SALESINVOICE :
          return array("debtor_trans", "type", "trans_no", "reference", "tran_date");
        case   ST_CUSTCREDIT   :
          return array("debtor_trans", "type", "trans_no", "reference", "tran_date");
        case   ST_CUSTPAYMENT  :
          return array("debtor_trans", "type", "trans_no", "reference", "tran_date");
        case   ST_CUSTREFUND  :
          return array("debtor_trans", "type", "trans_no", "reference", "tran_date");
        case   ST_CUSTDELIVERY :
          return array("debtor_trans", "type", "trans_no", "reference", "tran_date");
        case   ST_LOCTRANSFER  :
          return array("stock_moves", "type", "trans_no", "reference", "tran_date");
        case   ST_INVADJUST  :
          return array("stock_moves", "type", "trans_no", "reference", "tran_date");
        case   ST_PURCHORDER   :
          return array("purch_orders", null, "order_no", "reference", "tran_date");
        case   ST_SUPPINVOICE  :
          return array("creditor_trans", "type", "trans_no", "reference", "tran_date");
        case   ST_SUPPCREDIT   :
          return array("creditor_trans", "type", "trans_no", "reference", "tran_date");
        case   ST_SUPPAYMENT   :
          return array("creditor_trans", "type", "trans_no", "reference", "tran_date");
        case   ST_SUPPRECEIVE  :
          return array("grn_batch", null, "id", "reference", "delivery_date");
        case   ST_WORKORDER  :
          return array("workorders", null, "id", "wo_ref", "released_date");
        case   ST_MANUISSUE  :
          return array("wo_issues", null, "issue_no", "reference", "issue_date");
        case   ST_MANURECEIVE  :
          return array("wo_manufacture", null, "id", "reference", "date_");
        case   ST_SALESORDER   :
          return array("sales_orders", "trans_type", "order_no", "reference", "ord_date");
        case   31        :
          return array("service_orders", null, "order_no", "cust_ref", "date");
        case   ST_SALESQUOTE   :
          return array("sales_orders", "trans_type", "order_no", "reference", "ord_date");
        case   ST_DIMENSION  :
          return array("dimensions", null, "id", "reference", "date_");
        case   ST_COSTUPDATE   :
          return array("gl_trans", "type", "type_no", null, "tran_date");
      }
      Event::error("invalid type ($type) sent to get_systype_db_info", "", true);
    }
    /**
     * @static
     * @return null|\PDOStatement
     */
    public static function get() {
      $sql    = "SELECT type_id,type_no,CONCAT(prefix,next_reference)as next_reference FROM sys_types";
      $result = DB::_query($sql, "could not query systypes table");
      return $result;
    }
    /**
     * @static
     *
     * @param $ctype
     *
     * @return int
     */
    public static function get_class_type_convert($ctype) {
      return ((($ctype >= CL_LIABILITIES && $ctype <= CL_INCOME) || $ctype == CL_NONE) ? -1 : 1);
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $value
     * @param bool $spec_opt
     * @param bool $submit_on_change
     *
     * @return string
     */
    public static function select($name, $value = null, $spec_opt = false, $submit_on_change = false) {
      return Forms::arraySelect(
        $name,
        $value,
        SysTypes::$names,
        array(
             'spec_option'   => $spec_opt,
             'spec_id'       => ALL_NUMERIC,
             'select_submit' => $submit_on_change,
             'async'         => false,
        )
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $value
     * @param bool $submit_on_change
     */
    public static function cells($label, $name, $value = null, $submit_on_change = false) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo SysTypes::select($name, $value, false, $submit_on_change);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $value
     * @param bool $submit_on_change
     */
    public static function row($label, $name, $value = null, $submit_on_change = false) {
      echo "<tr><td class='label'>$label</td>";
      SysTypes::cells(null, $name, $value, $submit_on_change);
      echo "</tr>\n";
    }
  }
