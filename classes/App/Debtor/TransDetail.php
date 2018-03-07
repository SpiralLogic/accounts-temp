<?php
  use ADV\Core\DB\DB;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Debtor_TransDetail
  {
    /**
     * @static
     *
     * @param $debtor_trans_type
     * @param $debtor_trans_no
     *
     * @return null|PDOStatement
     */
    public static function get($debtor_trans_type, $debtor_trans_no)
    {
      if (!is_array($debtor_trans_no)) {
        $debtor_trans_no = array(0 => $debtor_trans_no);
      }
      $sql
          = "SELECT debtor_trans_details.*,
        debtor_trans_details.unit_price+debtor_trans_details.unit_tax AS FullUnitPrice,
        debtor_trans_details.description As StockDescription,
        stock_master.units
        FROM debtor_trans_details,stock_master
        WHERE (";
      $tr = [];
      foreach ($debtor_trans_no as $trans_no) {
        $tr[] = 'debtor_trans_no=' . $trans_no;
      }
      $sql .= implode(' OR ', $tr);
      $sql .= ") AND debtor_trans_type=" . DB::_escape($debtor_trans_type) . "
        AND stock_master.id=debtor_trans_details.id
        ORDER BY id";

      return DB::_query($sql, "The debtor transaction detail could not be queried");
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    public static function void($type, $type_no)
    {
      $sql
        = "UPDATE debtor_trans_details SET quantity=0, unit_price=0,
        unit_tax=0, discount_percent=0, standard_cost=0
        WHERE debtor_trans_no=" . DB::_escape($type_no) . "
        AND debtor_trans_type=" . DB::_escape($type);
      DB::_query($sql, "The debtor transaction details could not be voided");
      // clear the stock move items
      Inv_Movement::void($type, $type_no);
    }
    /**
     * @static
     *
     * @param     $debtor_trans_type
     * @param     $debtor_trans_no
     * @param     $stock_id
     * @param     $description
     * @param     $quantity
     * @param     $unit_price
     * @param     $unit_tax
     * @param     $discount_percent
     * @param     $std_cost
     * @param int $line_id
     */
    public static function add($debtor_trans_type, $debtor_trans_no, $stock_id, $description, $quantity, $unit_price, $unit_tax, $discount_percent, $std_cost, $line_id = 0)
    {
      if ($line_id != 0) {
        $sql
          = "UPDATE debtor_trans_details SET
            stock_id=" . DB::_escape($stock_id) . ",
            description=" . DB::_escape($description) . ",
            quantity=$quantity,
            unit_price=$unit_price,
            unit_tax=$unit_tax,
            discount_percent=$discount_percent,
            standard_cost=$std_cost WHERE
            id=" . DB::_escape($line_id);
      } else {
        $sql
          = "INSERT INTO debtor_trans_details (debtor_trans_no,
                debtor_trans_type, stock_id, description, quantity, unit_price,
                unit_tax, discount_percent, standard_cost)
            VALUES (" . DB::_escape($debtor_trans_no) . ", " . DB::_escape($debtor_trans_type) . ", " . DB::_escape($stock_id) . ", " . DB::_escape($description) . ",
                $quantity, $unit_price, $unit_tax, $discount_percent, $std_cost)";
      }
      DB::_query($sql, "The debtor transaction detail could not be written");
    }
    // add a debtor-related gl transaction
    // $date_ is display date (non-sql)
    // $amount is in CUSTOMER'S currency
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
     * @param        $debtor_id
     * @param string $err_msg
     * @param int    $rate
     *
     * @return float
     */
    public static function add_gl_trans($type, $type_no, $date_, $account, $dimension, $dimension2, $amount, $debtor_id, $err_msg = "", $rate = 0)
    {
      if ($err_msg == "") {
        $err_msg = "The customer GL transaction could not be inserted";
      }

      return GL_Trans::add($type, $type_no, $date_, $account, $dimension, $dimension2, "", $amount, Bank_Currency::for_debtor($debtor_id), PT_CUSTOMER, $debtor_id, $err_msg, $rate);
    }
  }
