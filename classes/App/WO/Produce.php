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
  use ADV\Core\DB\DB;
  use ADV\App\User;
  use ADV\App\Item\Item;
  use ADV\Core\Cell;
  use ADV\Core\Table;
  use ADV\App\Display;
  use ADV\App\Ref;
  use ADV\App\Dates;
  use ADV\App\WO\WO;

  /** **/
  class WO_Produce
  {
    /**
     * @static
     *
     * @param $woid
     * @param $ref
     * @param $quantity
     * @param $date_
     * @param $memo_
     * @param $close_wo
     */
    public static function add($woid, $ref, $quantity, $date_, $memo_, $close_wo) {
      DB::_begin();
      $details = WO::get($woid);
      if (strlen($details[0]) == 0) {
        echo _("The order number sent is not valid.");
        exit;
      }
      if (WO::is_closed($woid)) {
        Event::error("UNEXPECTED : Producing Items for a closed Work Order");
        DB::_cancel();
        exit;
      }
      $date = Dates::_dateToSql($date_);
      $sql
            = "INSERT INTO wo_manufacture (workorder_id, reference, quantity, date_)
        VALUES (" . DB::_escape($woid) . ", " . DB::_escape($ref) . ", " . DB::_escape($quantity) . ", '$date')";
      DB::_query($sql, "A work order manufacture could not be added");
      $id = DB::_insertId();
      // -------------------------------------------------------------------------
      WO_Quick::costs($woid, $details["stock_id"], $quantity, $date_, $id);
      // -------------------------------------------------------------------------
      // insert a +ve stock move for the item being manufactured
      // negative means "unproduce" or unassemble
      Inv_Movement::add(ST_MANURECEIVE, $details["stock_id"], $id, $details["loc_code"], $date_, $memo_, $quantity, 0);
      // update wo quantity and close wo if requested
      WO::update_finished_quantity($woid, $quantity, $close_wo);
      if ($memo_) {
        DB_Comments::add(ST_MANURECEIVE, $id, $date_, $memo_);
      }
      Ref::save(ST_MANURECEIVE, $ref);
      DB_AuditTrail::add(ST_MANURECEIVE, $id, $date_, _("Production."));
      DB::_commit();
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get($id) {
      $sql    = "SELECT wo_manufacture.*,workorders.stock_id, " . "stock_master.description AS StockDescription
        FROM wo_manufacture, workorders, stock_master
        WHERE wo_manufacture.workorder_id=workorders.id
        AND stock_master.stock_id=workorders.stock_id
        AND wo_manufacture.id=" . DB::_escape($id);
      $result = DB::_query($sql, "The work order production could not be retrieved");
      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $woid
     *
     * @return null|PDOStatement
     */
    public static function getAll($woid) {
      $sql = "SELECT * FROM wo_manufacture WHERE workorder_id=" . DB::_escape($woid) . " ORDER BY id";
      return DB::_query($sql, "The work order issues could not be retrieved");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return bool
     */
    public static function exists($id) {
      $sql    = "SELECT id FROM wo_manufacture WHERE id=" . DB::_escape($id);
      $result = DB::_query($sql, "Cannot retreive a wo production");
      return (DB::_numRows($result) > 0);
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    public static function void($type, $type_no) {
      if ($type != ST_MANURECEIVE) {
        $type = ST_MANURECEIVE;
      }
      DB::_begin();
      $row = WO_Produce::get($type_no);
      // deduct the quantity of this production from the parent work order
      WO::update_finished_quantity($row["workorder_id"], -$row["quantity"]);
      WO_Quick::costs($row['workorder_id'], $row['stock_id'], -$row['quantity'], Dates::_sqlToDate($row['date_']), $type_no);
      // clear the production record
      $sql = "UPDATE wo_manufacture SET quantity=0 WHERE id=" . DB::_escape($type_no);
      DB::_query($sql, "Cannot void a wo production");
      // void all related stock moves
      Inv_Movement::void($type, $type_no);
      // void any related gl trans
      GL_Trans::void($type, $type_no, true);
      DB::_commit();
    }
    /**
     * @static
     *
     * @param $woid
     */
    public static function display($woid) {
      $result = WO_Produce::getAll($woid);
      if (DB::_numRows($result) == 0) {
        Display::note(_("There are no Productions for this Order."), 1, 1);
      } else {
        Table::start('padded grid');
        $th = array(_("#"), _("Reference"), _("Date"), _("Quantity"));
        Table::header($th);
        $total_qty = 0;
        while ($myrow = DB::_fetch($result)) {
          $total_qty += $myrow['quantity'];
          Cell::label(GL_UI::viewTrans(29, $myrow["id"]));
          Cell::label($myrow['reference']);
          Cell::label(Dates::_sqlToDate($myrow["date_"]));
          Cell::qty($myrow['quantity'], false, Item::qty_dec($myrow['reference']));
          echo '</tr>';
        }
        //end of while
        Table::label(_("Total"), Num::_format($total_qty, User::_qty_dec()), "colspan=3", ' class="alignright nowrap"');
        Table::end();
      }
    }
  }

