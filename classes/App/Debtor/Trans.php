<?php
  use ADV\Core\DB\DB;
  use ADV\App\SysTypes;
  use ADV\App\Dates;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Debtor_Trans
  {
    /**
     * @static
     *
     * @param $trans_type
     * @param $trans_no
     *
     * @return array|int
     */
    public static function get_parent($trans_type, $trans_no) {
      $sql    = 'SELECT trans_link FROM ' . 'debtor_trans WHERE (trans_no=' . DB::_quote($trans_no) . ' AND type=' . DB::_quote($trans_type) . ' AND trans_link!=0)';
      $result = DB::_query($sql, 'Parent document numbers cannot be retrieved');
      if (DB::_numRows($result)) {
        $link = DB::_fetch($result);
        return array($link['trans_link']);
      }
      if ($trans_type != ST_SALESINVOICE) {
        return 0;
      } // this is credit note with no parent invoice
      // invoice: find batch invoice parent trans.
      $sql      = 'SELECT trans_no FROM ' . 'debtor_trans WHERE (trans_link=' . DB::_quote($trans_no) . ' AND type=' . Debtor_Trans::get_parent_type($trans_type) . ')';
      $result   = DB::_query($sql, 'Delivery links cannot be retrieved');
      $delivery = [];
      if (DB::_numRows($result) > 0) {
        while ($link = DB::_fetch($result)) {
          $delivery[] = $link['trans_no'];
        }
      }
      return count($delivery) ? $delivery : 0;
    }
    /**
     * @static
     *
     * @param $order
     *
     * @return int
     */
    public static function set_parent($order) {
      $inv_no = key($order->trans_no);
      if (count($order->src_docs) == 1) {
        // if this child document has only one parent - update child link
        $src    = array_keys($order->src_docs);
        $del_no = reset($src);
        $sql    = 'UPDATE debtor_trans SET trans_link = ' . $del_no . ' WHERE type=' . DB::_quote($order->trans_type) . ' AND trans_no=' . $inv_no;
        DB::_query($sql, 'UPDATE Child document link cannot be updated');
      }
      if ($order->trans_type != ST_SALESINVOICE) {
        return 0;
      }
      // the rest is batch invoice specific
      foreach ($order->line_items as $line) {
        if ($line->quantity != $line->qty_dispatched) {
          return 1; // this is partial invoice
        }
      }
      $sql        = 'UPDATE debtor_trans SET trans_link = ' . $inv_no . ' WHERE type=' . Debtor_Trans::get_parent_type($order->trans_type) . ' AND (';
      $deliveries = array_keys($order->src_docs);
      foreach ($deliveries as $key => $del) {
        $deliveries[$key] = 'trans_no=' . $del;
      }
      $sql .= implode(' OR ', $deliveries) . ')';
      DB::_query($sql, 'Delivery links cannot be updated');
      return 0; // batch or complete invoice
    }
    /**
     * @static
     *
     * @param $type
     *
     * @return int
     */
    public static function get_parent_type($type) {
      $parent_types = array(
        ST_CUSTCREDIT   => ST_SALESINVOICE,
        ST_SALESINVOICE => ST_CUSTDELIVERY,
        ST_CUSTDELIVERY => ST_SALESORDER
      );
      return isset($parent_types[$type]) ? $parent_types[$type] : 0;
    }
    /***
     * @static
     *
     * @param $type
     * @param $versions
     *
     * @return null|PDOStatement
     * Mark changes in debtor_trans_details

     */
    public static function update_version($type, $versions) {
      $sql
        = 'UPDATE debtor_trans SET version=version+1
            WHERE type=' . DB::_quote($type) . ' AND (';
      foreach ($versions as $trans_no => $version) {
        $where[] = '(trans_no=' . DB::_quote($trans_no) . ' AND version=' . $version . ')';
      }
      $sql .= implode(' OR ', $where) . ')';
      return DB::_query($sql, 'Concurrent editing conflict');
    }
    /***
     * @static
     *
     * @param int   $type     Gets document header versions for transaction set of type
     * @param array $trans_no array(num1, num2,...);
     *
     * @return array array(num1=>ver1, num2=>ver2...)
     */
    public static function get_version($type, $trans_no) {
      if (!is_array($trans_no)) {
        $trans_no = array($trans_no);
      }
      $sql = 'SELECT trans_no, version FROM ' . 'debtor_trans
            WHERE type=' . DB::_quote($type) . ' AND (';
      foreach ($trans_no as $key => $trans) {
        $trans_no[$key] = 'trans_no=' . $trans_no[$key];
      }
      $sql .= implode(' OR ', $trans_no) . ')';
      $res  = DB::_query($sql, 'document version retreival');
      $vers = [];
      while ($mysql = DB::_fetch($res)) {
        $vers[$mysql['trans_no']] = $mysql['version'];
      }
      return $vers;
    }
    /***
     * @static
     *
     * @param              $trans_type
     * @param              $trans_no
     * @param              $debtor_id
     * @param              $branch_no
     * @param string       $date_         is display date (non-sql)
     * @param              $reference
     * @param  float       $total         in customer's currency
     * @param int          $discount      in customer's currency
     * @param int          $tax           in customer's currency
     * @param int          $freight       in customer's currency
     * @param int          $freight_tax
     * @param int          $sales_type
     * @param int          $order_no
     * @param int          $trans_link
     * @param int          $ship_via
     * @param string       $due_date
     * @param int          $alloc_amt
     * @param int          $rate
     * @param int          $dimension_id
     * @param int          $dimension2_id
     *
     * @return int
     */
    public static function write(
      $trans_type,
      $trans_no,
      $debtor_id,
      $branch_no,
      $date_,
      $reference,
      $total,
      $discount = 0,
      $tax = 0,
      $freight = 0,
      $freight_tax = 0,
      $sales_type = 0,
      $order_no = 0,
      $trans_link = 0,
      $ship_via = 0,
      $due_date = "",
      $alloc_amt = 0,
      $rate = 0,
      $dimension_id = 0,
      $dimension2_id = 0
    ) {
      $new  = $trans_no == 0;
      $curr = Bank_Currency::for_debtor($debtor_id);
      if ($rate == 0) {
        $rate = Bank_Currency::exchange_rate_from_home($curr, $date_);
      }
      $SQLDate = Dates::_dateToSql($date_);
      if ($due_date == "") {
        $SQLDueDate = $SQLDate;
      } else {
        $SQLDueDate = Dates::_dateToSql($due_date);
      }
      if ($trans_type == ST_BANKPAYMENT) {
        $total = -$total;
      }
      if ($trans_type == ST_CUSTPAYMENT) {
        $alloc_amt = abs($alloc_amt);
      }
      if ($new) {
        $trans_no = SysTypes::get_next_trans_no($trans_type);
        $sql
                  = "INSERT INTO debtor_trans (
        trans_no, type,
        debtor_id, branch_id,
        tran_date, due_date,
        reference, tpe,
        order_, ov_amount, ov_discount,
        ov_gst, ov_freight, ov_freight_tax,
        rate, ship_via, alloc, trans_link,
        dimension_id, dimension2_id
        ) VALUES ($trans_no, " . DB::_quote($trans_type) . ",
        " . DB::_quote($debtor_id) . ", " . DB::_quote($branch_no) . ",
        '$SQLDate', '$SQLDueDate', " . DB::_quote($reference) . ",
        " . DB::_quote($sales_type) . ", " . DB::_quote($order_no) . ", $total, " . DB::_quote($discount) . ", $tax,
        " . DB::_quote($freight) . ",
        $freight_tax, $rate, " . DB::_quote($ship_via) . ", $alloc_amt, " . DB::_quote($trans_link) . ",
        " . DB::_quote($dimension_id) . ", " . DB::_quote($dimension2_id) . ")";
      } else { // may be optional argument should stay unchanged ?
        $sql
          = "UPDATE debtor_trans SET
        debtor_id=" . DB::_quote($debtor_id) . " , branch_id=" . DB::_quote($branch_no) . ",
        tran_date='$SQLDate', due_date='$SQLDueDate',
        reference=" . DB::_quote($reference) . ", tpe=" . DB::_quote($sales_type) . ", order_=" . DB::_quote($order_no) . ",
        ov_amount=$total, ov_discount=" . DB::_quote($discount) . ", ov_gst=$tax,
        ov_freight=" . DB::_quote($freight) . ", ov_freight_tax=$freight_tax, rate=$rate,
        ship_via=" . DB::_quote($ship_via) . ", alloc=$alloc_amt, trans_link=$trans_link,
        dimension_id=" . DB::_quote($dimension_id) . ", dimension2_id=" . DB::_quote($dimension2_id) . "
        WHERE trans_no=$trans_no AND type=" . DB::_quote($trans_type);
      }
      DB::_query($sql, "The debtor transaction record could not be inserted");
      DB_AuditTrail::add($trans_type, $trans_no, $date_, $new ? '' : _("Updated."));
      return $trans_no;
    }
    /***
     * Generic read debtor transaction into order
     *
     * @param             $doc_type
     * @param             $trans_no - array of trans nums; special case trans_no==0 - new doc
     * @param Sales_Order $order
     *
     * @return bool
     */
    public static function read($doc_type, $trans_no, &$order) {
      if (!is_array($trans_no) && $trans_no) {
        $trans_no = array($trans_no);
      }
      $order->trans_type = $doc_type;
      if (!$trans_no) { // new document
        $order->trans_no = $trans_no;
      } else {
        // read header data from first document
        $myrow = Debtor_Trans::get($trans_no[0], $doc_type);
        if (count($trans_no) > 1) {
          $order->trans_no = Debtor_Trans::get_version($doc_type, $trans_no);
        } else {
          $order->trans_no = array($trans_no[0] => $myrow["version"]);
        }
        $order->set_sales_type($myrow["tpe"], $myrow["sales_type"], $myrow["tax_included"], 0);
        $order->set_customer($myrow["debtor_id"], $myrow["DebtorName"], $myrow["curr_code"], $myrow["discount"], $myrow["payment_terms"]);
        $order->set_branch($myrow["branch_id"], $myrow["tax_group_id"], $myrow["tax_group_name"], $myrow["phone"], $myrow["email"]);
        $order->reference     = $myrow["reference"];
        $order->order_no      = $myrow["order_"];
        $order->trans_link    = $myrow["trans_link"];
        $order->due_date      = Dates::_sqlToDate($myrow["due_date"]);
        $order->document_date = Dates::_sqlToDate($myrow["tran_date"]);
        $order->dimension_id  = $myrow['dimension_id']; // added 2.1 Joe Hunt 2008-11-12
        $order->dimension2_id = $myrow['dimension2_id'];
        $order->Comments      = '';
        foreach ($trans_no as $trans) {
          $order->Comments .= DB_Comments::get_string($doc_type, $trans);
        }
        // FIX this should be calculated sum() for multiply parents
        $order->set_delivery($myrow["ship_via"], $myrow["br_name"], $myrow["br_address"], $myrow["ov_freight"]);
        $location = 0;
        $myrow    = Inv_Location::get_for_trans($order); // find location from movement
        if ($myrow != null) {
          $order->set_location($myrow['loc_code'], $myrow['location_name']);
        }
        $result = Debtor_TransDetail::get($doc_type, $trans_no);
        if (DB::_numRows($result) > 0) {
          for ($line_no = 0; $myrow = DB::_fetch($result); $line_no++) {
            $order->line_items[$line_no] = new Sales_Line($myrow["stock_id"], $myrow["quantity"], $myrow["unit_price"], $myrow["discount_percent"], $myrow["qty_done"], $myrow["standard_cost"], $myrow["StockDescription"], $myrow["id"], $myrow["debtor_trans_no"]);
          }
        }
      } // !newdoc
      return true;
    }
    /**
     * @static
     *
     * @param $trans_id
     * @param $trans_type
     *
     * @return Array|\ADV\Core\DB\Query\Result
     */
    public static function get($trans_id, $trans_type) {
      $sql
        = "SELECT debtor_trans.*,
        ov_amount+ov_gst+ov_freight+ov_freight_tax+ov_discount AS Total,
        debtors.name AS DebtorName, debtors.address, debtors.email AS email2,
        debtors.curr_code, debtors.tax_id, debtors.payment_terms ";
      if ($trans_type == ST_CUSTPAYMENT) {
        // it's a payment/refund so also get the bank account
        $sql
          .= ", bank_accounts.bank_name, bank_accounts.bank_account_name,
            bank_accounts.account_type AS BankTransType ";
      }
      if ($trans_type == ST_SALESINVOICE || $trans_type == ST_CUSTCREDIT || $trans_type == ST_CUSTDELIVERY) {
        // it's an invoice so also get the shipper and salestype
        $sql .= ", shippers.shipper_name, " . "sales_types.sales_type, " . "sales_types.tax_included, " . "branches.*, " . "debtors.discount, " . "tax_groups.name AS tax_group_name, " . "tax_groups.id AS tax_group_id ";
      }
      $sql .= " FROM debtor_trans, debtors ";
      if ($trans_type == ST_CUSTPAYMENT || $trans_type == ST_BANKDEPOSIT) {
        // it's a payment so also get the bank account
        $sql .= ", bank_trans, bank_accounts";
      }
      if ($trans_type == ST_SALESINVOICE || $trans_type == ST_CUSTCREDIT || $trans_type == ST_CUSTDELIVERY) {
        // it's an invoice so also get the shipper, salestypes
        $sql .= ", shippers, sales_types, branches, tax_groups ";
      }
      $sql .= " WHERE debtor_trans.trans_no=" . DB::_quote($trans_id) . "
        AND debtor_trans.type=" . DB::_quote($trans_type) . "
        AND debtor_trans.debtor_id=debtors.debtor_id";
      if ($trans_type == ST_CUSTPAYMENT || $trans_type == ST_BANKDEPOSIT) {
        // it's a payment so also get the bank account
        $sql
          .= " AND bank_trans.trans_no =$trans_id
            AND bank_trans.type=$trans_type
            AND bank_accounts.id=bank_trans.bank_act ";
      }
      if ($trans_type == ST_SALESINVOICE || $trans_type == ST_CUSTCREDIT || $trans_type == ST_CUSTDELIVERY) {
        // it's an invoice so also get the shipper
        $sql
          .= " AND shippers.shipper_id=debtor_trans.ship_via
            AND sales_types.id = debtor_trans.tpe
            AND branches.branch_id = debtor_trans.branch_id
            AND branches.tax_group_id = tax_groups.id ";
      }
      $result = DB::_query($sql, "Cannot retreive a debtor transaction");
      if (DB::_numRows($result) == 0) {
        // can't return nothing
        Event::error("no debtor trans found for given params", $sql, true);
      }
      if (DB::_numRows($result) > 1) {
        // can't return multiple
        Event::error("duplicate debtor transactions found for given params", $sql, true);
      }
      //return DB::_fetch($result);
      $row          = DB::_fetch($result);
      $row['email'] = $row['email2'];
      return $row;
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
      $sql    = "SELECT trans_no FROM debtor_trans WHERE type=" . DB::_quote($type) . "
        AND trans_no=" . DB::_quote($type_no);
      $result = DB::_query($sql, "Cannot retreive a debtor transaction");
      return (DB::_numRows($result) > 0);
    }
    /***
     * @static
     *
     * @param $type
     * @param $type_no
     *
     * @return mixed
     * retreives the related sales order for a given trans
     */
    public static function get_order($type, $type_no) {
      $sql    = "SELECT order_ FROM debtor_trans WHERE type=" . DB::_quote($type) . " AND trans_no=" . DB::_quote($type_no);
      $result = DB::_query($sql, "The debtor transaction could not be queried");
      $row    = DB::_fetchRow($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     *
     * @return Array|\ADV\Core\DB\Query\Result
     */
    public static function get_details($type, $type_no) {
      $sql
              = "SELECT debtors.name, debtors.curr_code, branches.br_name
        FROM debtors,branches,debtor_trans
        WHERE debtor_trans.type=" . DB::_quote($type) . " AND debtor_trans.trans_no=" . DB::_quote($type_no) . "
        AND debtors.debtor_id = debtor_trans.debtor_id
        AND	branches.branch_id = debtor_trans.branch_id";
      $result = DB::_query($sql, "could not get customer details from trans");
      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    public static function void($type, $type_no) {
      // clear all values and mark as void
      $sql
        = "UPDATE debtor_trans SET ov_amount=0, ov_discount=0, ov_gst=0, ov_freight=0,
        ov_freight_tax=0, alloc=0, version=version+1 WHERE type=" . DB::_quote($type) . " AND trans_no=" . DB::_quote($type_no);
      DB::_query($sql, "could not void debtor transactions for type=$type and trans_no=$type_no");
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    public static function post_void($type, $type_no) {
      switch ($type) {
        case ST_SALESINVOICE :
        case ST_CUSTCREDIT  :
          Sales_Invoice::void($type, $type_no);
          break;
        case ST_CUSTDELIVERY :
          Sales_Delivery::void($type, $type_no);
          break;
        case ST_CUSTPAYMENT :
          Debtor_Payment::void($type, $type_no);
          break;
      }
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     *
     * @return mixed
     */
    public static function get_link($type, $type_no) {
      $row = DB::_query(
        "SELECT trans_link from debtor_trans
        WHERE type=" . DB::_quote($type) . " AND trans_no=" . DB::_quote($type_no), "could not get transaction link for type=$type and trans_no=$type_no"
      );
      return $row[0];
    }
    /**
     * @static
     *
     * @param $tax_items
     * @param $columns
     */
    public static function display_tax_details($tax_items, $columns) {
      while ($tax_item = DB::_fetch($tax_items)) {
        $tax = Num::_priceFormat($tax_item['amount']);
        if ($tax_item['included_in_price']) {
          Table::label(
            _("Included") . " " . $tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%) " . _("Amount") . ": $tax", "", "colspan=$columns class='alignright'", "class='alignright'"
          );
        } else {
          Table::label($tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%)", $tax, "colspan=$columns class='alignright'", "class='alignright'");
        }
      }
    }
  }
