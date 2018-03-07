<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Inv_Transfer {
    /**
     * @static
     *
     * @param $Items
     * @param $location_from
     * @param $location_to
     * @param $date_
     * @param $type
     * @param $reference
     * @param $memo_
     *
     * @return int
     */
    public static function add($Items, $location_from, $location_to, $date_, $type, $reference, $memo_) {
      DB::_begin();
      $transfer_id = SysTypes::get_next_trans_no(ST_LOCTRANSFER);
      foreach ($Items as $line_item) {
        Inv_Transfer::add_item($transfer_id, $line_item->stock_id, $location_from, $location_to, $date_, $type, $reference, $line_item->quantity);
      }
      DB_Comments::add(ST_LOCTRANSFER, $transfer_id, $date_, $memo_);
      Ref::save(ST_LOCTRANSFER, $reference);
      DB_AuditTrail::add(ST_LOCTRANSFER, $transfer_id, $date_);
      DB::_commit();
      return $transfer_id;
    }
    /***
     * @static
     *
     * @param $transfer_id
     * @param $stock_id
     * @param $location_from
     * @param $location_to
     * @param $date_ is display date (not sql)
     * @param $type
     * @param $reference
     * @param $quantity
     *               add 2 stock_moves entries for a stock transfer

     */
    public static function add_item($transfer_id, $stock_id, $location_from, $location_to, $date_, $type, $reference, $quantity) {
      Inv_Movement::add(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_from, $date_, $reference, -$quantity, 0, $type);
      Inv_Movement::add(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_to, $date_, $reference, $quantity, 0, $type);
    }
    /**
     * @static
     *
     * @param $trans_no
     *
     * @return array
     */
    public static function get($trans_no) {
      $result = Inv_Transfer::get_items($trans_no);
      if (DB::_numRows($result) < 2) {
        Event::error("transfer with less than 2 items : $trans_no", "");
      }
      // this public static function is very bad that it assumes that 1st record and 2nd record contain the
      // from and to locations - if get_stock_moves uses a different ordering than trans_no then
      // it will bomb
      $move1 = DB::_fetch($result);
      $move2 = DB::_fetch($result);
      // return an array of (From, To)
      if ($move1['qty'] < 0) {
        return array($move1, $move2);
      } else {
        return array($move2, $move1);
      }
    }
    /**
     * @static
     *
     * @param $trans_no
     *
     * @return null|PDOStatement
     */
    public static function get_items($trans_no) {
      $result = Inv_Movement::get(ST_LOCTRANSFER, $trans_no);
      if (DB::_numRows($result) == 0) {
        return null;
      }
      return $result;
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    public static function void($type, $type_no) {
      Inv_Movement::void($type, $type_no);
    }
    /**
     * @static
     *
     * @param $type
     * @param $stock_id
     * @param $from
     * @param $to
     * @param $pid
     * @param $cost
     */
    public static function update_pid($type, $stock_id, $from, $to, $pid, $cost) {
      $from = Dates::_dateToSql($from);
      $to   = Dates::_dateToSql($to);
      $sql  = "UPDATE stock_moves SET standard_cost=" . DB::_escape($cost) . " WHERE type=" . DB::_escape($type) . "	AND stock_id=" . DB::_escape($stock_id) . " AND tran_date>='$from' AND tran_date<='$to'
                AND person_id = " . DB::_escape($pid);
      DB::_query($sql, "The stock movement standard_cost cannot be updated");
    }
    public static function header() {
      Table::startOuter('padded width70');
      Table::section(1);
      Inv_Location::row(_("From Location:"), 'FromStockLocation', null);
      Inv_Location::row(_("To Location:"), 'ToStockLocation', null);
      Table::section(2, "33%");
      Forms::refRow(_("Reference:"), 'ref', '', Ref::get_next(ST_LOCTRANSFER));
      Forms::dateRow(_("Date:"), 'AdjDate', '', true);
      Table::section(3, "33%");
      Inv_Movement::row(_("Transfer Type:"), 'type', null);
      Table::endOuter(1); // outer table
    }
    /**
     * @static
     *
     * @param $title
     * @param $order
     */
    public static function display_items($title, $order) {
      Display::heading($title);
      Ajax::_start_div('items_table');
      Table::start('padded grid width90');
      $th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), '');
      if (count($order->line_items)) {
        $th[] = '';
      }
      Table::header($th);
      $k  = 0; //row colour counter
      $id = Forms::findPostPrefix(MODE_EDIT);
      foreach ($order->line_items as $line_no => $stock_item) {
        if ($id != $line_no) {
          Item_UI::status_cell($stock_item->stock_id);
          Cell::label($stock_item->description);
          Cell::qty($stock_item->quantity, false, Item::qty_dec($stock_item->stock_id));
          Cell::label($stock_item->units);
          Forms::buttonEditCell("Edit$line_no", _("Edit"), _('Edit document line'));
          Forms::buttonDeleteCell("Delete$line_no", _("Delete"), _('Remove line from document'));
          echo '</tr>';
        } else {
          Inv_Transfer::item_controls($order, $line_no);
        }
      }
      if ($id == -1) {
        Inv_Transfer::item_controls($order);
      }
      Table::end();
      Ajax::_end_div();
    }
    /**
     * @static
     *
     * @param $order
     * @param $line_no
     */
    public static function item_controls($order, $line_no = -1) {
      echo '<tr>';
      $id = Forms::findPostPrefix(MODE_EDIT);
      if ($line_no != -1 && $line_no == $id) {
        $_POST['stock_id'] = $order->line_items[$id]->stock_id;
        $_POST['qty']      = Item::qty_format($order->line_items[$id]->quantity, $order->line_items[$id]->stock_id, $dec);
        $_POST['units']    = $order->line_items[$id]->units;
        Forms::hidden('stock_id', $_POST['stock_id']);
        Cell::label($_POST['stock_id']);
        Cell::label($order->line_items[$id]->description);
        Ajax::_activate('items_table');
      } else {
        Item_UI::costable_cells(null, 'stock_id', null, false, true);
        if (Forms::isListUpdated('stock_id')) {
          Ajax::_activate('units');
          Ajax::_activate('qty');
        }
        $item_info      = Item::get_edit_info(Input::_post('stock_id'));
        $dec            = $item_info['decimals'];
        $_POST['qty']   = Num::_format(0, $dec);
        $_POST['units'] = $item_info["units"];
      }
      Forms::qtyCellsSmall(null, 'qty', $_POST['qty'], null, null, $dec);
      Cell::label($_POST['units'], '', 'units');
      if ($id != -1) {
        Forms::buttonCell('updateItem', _("Update"), _('Confirm changes'), ICON_UPDATE);
        Forms::buttonCell('cancelItem', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
        Forms::hidden('LineNo', $line_no);
        JS::_setFocus('qty');
      } else {
        Forms::submitCells('addLine', _("Add Item"), "colspan=2", _('Add new item to document'), true);
      }
      echo '</tr>';
    }
    public static function option_controls() {
      echo "<br>";
      Table::start();
      Forms::textareaRow(_("Memo"), 'memo_', null, 50, 3);
      Table::end(1);
    }
  }
