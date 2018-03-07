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

  use GL_Trans;
  use ADV\Core\Table;
  use ADV\Core\DB\DB;
  use DB_AuditTrail;
  use WO_Produce;
  use WO_Issue;
  use ADV\App\WO\WO;
  use Creditor_Trans;
  use Inv_Adjustment;
  use Inv_Transfer;
  use Debtor_Trans;
  use Bank_Trans;
  use GL_Journal;

  /** **/
  class Voiding
  {
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     * @param $date_
     * @param $memo_
     *
     * @return bool
     */
    public static function void($type, $type_no, $date_, $memo_) {
      $void_entry = static::get($type, $type_no);
      if ($void_entry != null) {
        return false;
      }
      switch ($type) {
        case ST_JOURNAL : // it's a journal entry
          if (!GL_Trans::exists($type, $type_no)) {
            return false;
          }
          GL_Journal::void($type, $type_no);
          break;
        case ST_BANKPAYMENT : // it's a payment
        case ST_BANKDEPOSIT : // it's a deposit
        case ST_BANKTRANSFER : // it's a transfer
          if (!Bank_Trans::exists($type, $type_no)) {
            return false;
          }
          Bank_Trans::void($type, $type_no);
          break;
        case ST_SALESINVOICE : // it's a customer invoice
        case ST_CUSTCREDIT : // it's a customer credit note
        case ST_CUSTPAYMENT : // it's a customer payment
        case ST_CUSTREFUND : // it's a customer refund
        case ST_CUSTDELIVERY : // it's a customer dispatch
          if (!Debtor_Trans::exists($type, $type_no)) {
            return false;
          }
          if ($type == ST_CUSTDELIVERY) {
            $delivery = Debtor_Trans::get($type_no, $type);
            if (!$delivery['trans_link']) {
              if (static::get(ST_SALESINVOICE, $delivery['trans_link']) !== false) {
                return false;
              }
            }
          }
          Debtor_Trans::post_void($type, $type_no);
          break;
        case ST_LOCTRANSFER : // it's a stock transfer
          if (Inv_Transfer::get_items($type_no) == null) {
            return false;
          }
          Inv_Transfer::void(ST_LOCTRANSFER, $type_no);
          break;
        case ST_INVADJUST : // it's a stock adjustment
          if (Inv_Adjustment::get($type_no) == null) {
            return false;
          }
          Inv_Adjustment::void(ST_INVADJUST, $type_no);
          break;
        case ST_PURCHORDER : // it's a PO
        case ST_SUPPRECEIVE : // it's a GRN
          return false;
        case ST_SUPPINVOICE : // it's a suppler invoice
        case ST_SUPPCREDIT : // it's a supplier credit note
        case ST_SUPPAYMENT : // it's a supplier payment
          if (!Creditor_Trans::exists($type, $type_no)) {
            return false;
          }
          if (!Creditor_Trans::post_void($type, $type_no)) {
            return false;
          }
          break;
        case ST_WORKORDER : // it's a work order
          if (!WO::get($type_no, true)) {
            return false;
          }
          WO::void(ST_WORKORDER, $type_no);
          break;
        case ST_MANUISSUE : // it's a work order issue
          if (!WO_Issue::exists($type_no)) {
            return false;
          }
          WO_Issue::void(ST_MANUISSUE, $type_no);
          break;
        case ST_MANURECEIVE : // it's a work order production
          if (!WO_Produce::exists($type_no)) {
            return false;
          }
          WO_Produce::void(ST_MANURECEIVE, $type_no);
          break;
        case ST_SALESORDER: // it's a sales order
        case ST_SALESQUOTE: // it's a sales quotation
          return false;
        case ST_COSTUPDATE : // it's a stock cost update
          return false;
          break;
      }
      // only add an entry if it's actually been voided
      DB_AuditTrail::add($type, $type_no, $date_, _("Voided.") . "\n" . $memo_);
      static::add($type, $type_no, $date_, $memo_);
      return true;
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get($type, $type_no) {
      $sql    = "SELECT * FROM voided WHERE type=" . DB::_escape($type) . " AND id=" . DB::_escape($type_no);
      $result = DB::_query($sql, "could not query voided transaction table");
      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     *
     * @return int
     */
    public static function has($type, $type_no) {
      $sql    = "SELECT * FROM voided WHERE type=" . DB::_escape($type) . " AND id=" . DB::_escape($type_no);
      $result = DB::_query($sql, "could not query voided transaction table");
      return DB::_numRows($result);
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     * @param $date_
     * @param $memo_
     */
    public static function add($type, $type_no, $date_, $memo_) {
      $date = Dates::_dateToSql($date_);
      $sql
            = "INSERT INTO voided (type, id, date_, memo_)
			VALUES (" . DB::_escape($type) . ", " . DB::_escape($type_no) . ", " . DB::_escape($date) . ", " . DB::_escape($memo_) . ")";
      DB::_query($sql, "could not add voided transaction entry");
    }
    /**
     * @static
     *
     * @param $type
     * @param $id
     * @param $label
     *
     * @return bool
     */
    public static function is_voided($type, $id, $label) {
      $void_entry = Voiding::get($type, $id);
      if ($void_entry == null) {
        return false;
      }
      Table::start('padded width50');
      echo "<tr><td class=center><span class='red'>$label</span><br>";
      echo "<span class='red'>" . _("Date Voided:") . " " . Dates::_sqlToDate($void_entry["date_"]) . "</span><br>";
      if (strlen($void_entry["memo_"]) > 0) {
        echo "<div class='center'><span class='red'>" . _("Memo:") . " " . $void_entry["memo_"] . "</span></div><br>";
      }
      echo "</td></tr>";
      Table::end(1);
      return true;
    }
  }
