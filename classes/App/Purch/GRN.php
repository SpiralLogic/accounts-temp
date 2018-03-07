<?php
  /**********************************************************************
  Copyright (C) Advanced Group PTY LTD
  Released under the terms of the GNU General Public License, GPL,
  as published by the Free Software Foundation, either version 3
  of the License, or (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
   ***********************************************************************/
  use ADV\App\User;
  use ADV\Core\Input\Input;
  use ADV\Core\Event;
  use ADV\Core\Num;
  use ADV\Core\Cell;
  use ADV\App\Forms;
  use ADV\Core\Table;
  use ADV\App\Display;
  use ADV\App\Dates;
  use ADV\App\Ref;
  use ADV\App\Item\Item;
  use ADV\Core\DB\DB;

  /** **/
  class Purch_GRN
  {
    /**
     * @param      $supplier
     * @param      $stock_id
     * @param      $price
     * @param      $qty
     * @param      $date
     * @param bool $adj_only
     *
     * @return mixed
     */
    public static function update_average_material_cost($supplier, $stock_id, $price, $qty, $date, $adj_only = false) {
      if ($supplier != null) {
        $currency = Bank_Currency::for_creditor($supplier);
      } else {
        $currency = null;
      }
      $dec = User::_price_dec();
      Num::_priceDecimal($price, $dec);
      $price = Num::_round($price, $dec);
      if ($currency != null) {
        $ex_rate                = Bank_Currency::exchange_rate_to_home($currency, $date);
        $price_in_home_currency = $price / $ex_rate;
      } else {
        $price_in_home_currency = $price;
      }
      $sql           = "SELECT material_cost FROM stock_master WHERE stock_id=" . DB::_escape($stock_id);
      $result        = DB::_query($sql);
      $myrow         = DB::_fetch($result);
      $material_cost = $myrow['material_cost'];
      if ($price > -0.0001 && $price < 0.0001) {
        return $material_cost;
      }
      if ($adj_only) {
        $exclude = ST_CUSTDELIVERY;
      } else {
        $exclude = 0;
      }
      $cost_adjust = false;
      $qoh         = Item::get_qoh_on_date($stock_id, null, $date, $exclude);
      if ($adj_only) {
        if ($qoh > 0) {
          $material_cost = ($qoh * $material_cost + $qty * $price_in_home_currency) / $qoh;
        }
      } else {
        if ($qoh < 0) {
          if ($qoh + $qty > 0) {
            $cost_adjust = true;
          }
          $qoh = 0;
        }
        if ($qoh + $qty != 0) {
          $material_cost = ($qoh * $material_cost + $qty * $price_in_home_currency) / ($qoh + $qty);
        }
      }
      $material_cost = Num::_round($material_cost, $dec);
      if ($cost_adjust) // new 2010-02-10
      {
        Item::adjust_deliveries($stock_id, $material_cost, $date);
      }
      $sql = "UPDATE stock_master SET material_cost=" . DB::_escape($material_cost) . "
        WHERE stock_id=" . DB::_escape($stock_id);
      DB::_query($sql, "The cost details for the inventory item could not be updated");
      return $material_cost;
    }
    /**
     * @param $order
     * @param $date_
     * @param $reference
     * @param $location
     *
     * @return mixed
     */
    public static function add(&$order, $date_, $reference, $location) {
      DB::_begin();
      $grn = static::add_batch($order->order_no, $order->creditor_id, $reference, $location, $date_);
      foreach ($order->line_items as $order_line) {
        if ($order_line->receive_qty != 0 && $order_line->receive_qty != "" && isset($order_line->receive_qty)) {
          /*Update sales_order_details for the new quantity received and the standard cost used for postings to GL and recorded in the stock movements for FIFO/LIFO stocks valuations*/
          //------------------- update average material cost ------------------------------------------ Joe Hunt Mar-03-2008
          static::update_average_material_cost($order->creditor_id, $order_line->stock_id, $order_line->price, $order_line->receive_qty, $date_);
          if ($order_line->qty_received == 0) {
            /*This must be the first receipt of goods against this line */
            /*Need to get the standard cost as it is now so we can process GL jorunals later*/
            $order_line->standard_cost = Item_Price::get_standard_cost($order_line->stock_id);
          }
          if ($order_line->price <= $order_line->standard_cost) {
            Purch_Order::add_or_update_data($order->creditor_id, $order_line->stock_id, $order_line->price);
          }
          /*Need to insert a grn item */
          $grn_item = static::add_item(
            $grn,
            $order_line->po_detail_rec,
            $order_line->stock_id,
            $order_line->description,
            $order_line->standard_cost,
            $order_line->receive_qty,
            $order_line->price,
            $order_line->discount
          );
          /* Update location stock records - NB a po cannot be entered for a service/kit parts */
          Inv_Movement::add(
            ST_SUPPRECEIVE,
            $order_line->stock_id,
            $grn,
            $location,
            $date_,
            "",
            $order_line->receive_qty,
            $order_line->standard_cost,
            $order->creditor_id,
            1,
            $order_line->price
          );
        } /*quantity received is != 0 */
      } /*end of order_line loop */
      $grn_item = static::add_item($grn, $order->add_freight($date_), 'Freight', 'Freight Charges', 0, 1, $order->freight, 0);
      Ref::save(ST_SUPPRECEIVE, $reference);
      DB_AuditTrail::add(ST_SUPPRECEIVE, $grn, $date_);
      DB::_commit();
      return $grn;
    }
    /**
     * @param $po_number
     * @param $creditor_id
     * @param $reference
     * @param $location
     * @param $date_
     *
     * @return mixed
     */
    public static function add_batch($po_number, $creditor_id, $reference, $location, $date_) {
      $date = Dates::_dateToSql($date_);
      $sql
            = "INSERT INTO grn_batch (purch_order_no, delivery_date, creditor_id, reference, loc_code)
            VALUES (" . DB::_escape($po_number) . ", " . DB::_escape($date) . ", " . DB::_escape($creditor_id) . ", " . DB::_escape($reference) . ", " . DB::_escape(
        $location
      ) . ")";
      DB::_query($sql, "A grn batch record could not be inserted.");
      return DB::_insertId();
    }
    /**
     * @param $grn_batch_id
     * @param $po_detail_item
     * @param $item_code
     * @param $description
     * @param $standard_unit_cost
     * @param $quantity_received
     * @param $price
     * @param $discount
     *
     * @return mixed
     */
    public static function add_item($grn_batch_id, $po_detail_item, $item_code, $description, $standard_unit_cost, $quantity_received, $price, $discount) {
      $sql
        = "UPDATE purch_order_details
 SET quantity_received = quantity_received + " . DB::_escape($quantity_received) . ",
 std_cost_unit=" . DB::_escape($standard_unit_cost) . ",
 discount=" . DB::_escape($discount) . ",
 act_price=" . DB::_escape($price) . "
 WHERE po_detail_item = " . DB::_escape($po_detail_item);
      DB::_query($sql, "a purchase order details record could not be updated. This receipt of goods has not been processed ");
      $sql
        = "INSERT INTO grn_items (grn_batch_id, po_detail_item, item_code, description, qty_recd, discount)
        VALUES (" . DB::_escape($grn_batch_id) . ", " . DB::_escape($po_detail_item) . ", " . DB::_escape($item_code) . ", " . DB::_escape($description) . ", " . DB::_escape(
        $quantity_received
      ) . ", " . DB::_escape($discount) . ")";
      DB::_query($sql, "A GRN detail item could not be inserted.");
      return DB::_insertId();
    }
    /**
     * @param $item
     *
     * @return mixed
     */
    public static function get_batch_for_item($item) {
      $sql    = "SELECT grn_batch_id FROM grn_items WHERE id=" . DB::_escape($item);
      $result = DB::_query($sql, "Could not retreive GRN batch id");
      $row    = DB::_fetchRow($result);
      return $row[0];
    }
    /**
     * @param $grn
     *
     * @return ADV\Core\DB\Query\Result|Array
     */
    public static function get_batch($grn) {
      $sql    = "SELECT * FROM grn_batch WHERE id=" . DB::_escape($grn);
      $result = DB::_query($sql, "Could not retreive GRN batch id");
      return DB::_fetch($result);
    }
    /**
     * @param $entered_grn
     * @param $supplier
     * @param $transno
     * @param $date
     */
    public static function set_item_credited(&$entered_grn, $supplier, $transno, $date) {
      $mcost = static::update_average_material_cost($supplier, $entered_grn->item_code, $entered_grn->chg_price, $entered_grn->this_quantity_inv, $date);
      $sql
              = "SELECT grn_batch.*, grn_items.*
     FROM grn_batch, grn_items
     WHERE grn_items.grn_batch_id=grn_batch.id
        AND grn_items.id=" . DB::_escape($entered_grn->id) . "
     AND grn_items.item_code=" . DB::_escape($entered_grn->item_code);
      $result = DB::_query($sql, "Could not retreive GRNS");
      $myrow  = DB::_fetch($result);
      $sql
              = "UPDATE purch_order_details
 SET quantity_received = quantity_received + " . DB::_escape($entered_grn->this_quantity_inv) . ",
 quantity_ordered = quantity_ordered + " . DB::_escape($entered_grn->this_quantity_inv) . ",
 qty_invoiced = qty_invoiced + " . DB::_escape($entered_grn->this_quantity_inv) . ",
 std_cost_unit=" . DB::_escape($mcost) . ",
 act_price=" . DB::_escape($entered_grn->chg_price) . "
 WHERE po_detail_item = " . $myrow["po_detail_item"];
      DB::_query($sql, "a purchase order details record could not be updated. This receipt of goods has not been processed ");
      //$sql = "UPDATE ".''."grn_items SET qty_recd=0, quantity_inv=0 WHERE id=$entered_grn->id";
      $sql = "UPDATE grn_items SET qty_recd=qty_recd+" . DB::_escape($entered_grn->this_quantity_inv) . ",quantity_inv=quantity_inv+" . DB::_escape(
        $entered_grn->this_quantity_inv
      ) . " WHERE id=" . DB::_escape($entered_grn->id);
      DB::_query($sql);
      Inv_Movement::add(
        ST_SUPPCREDIT,
        $entered_grn->item_code,
        $transno,
        $myrow['loc_code'],
        $date,
        "",
        $entered_grn->this_quantity_inv,
        $mcost,
        $supplier,
        1,
        $entered_grn->chg_price
      );
    }
    /**
     * @param int    $grn_batch_id
     * @param string $creditor_id
     * @param bool   $outstanding_only
     * @param bool   $is_invoiced_only
     * @param int    $invoice_no
     * @param string $begin
     * @param string $end
     *
     * @return PDOStatement
     */
    public static function get_items($grn_batch_id = 0, $creditor_id = "", $outstanding_only = false, $is_invoiced_only = false, $invoice_no = 0, $begin = "", $end = "") {
      $sql
             = "SELECT  grn_batch.*,  grn_items.*,  purch_order_details.unit_price,  purch_order_details.std_cost_unit, units
      FROM  grn_batch,  grn_items,  purch_order_details,  stock_master ";
      $ponum = Input::_post('PONumber');
      if ($ponum) {
        $sql .= ", purch_orders ";
      }
      if ($invoice_no != 0) {
        $sql .= ", creditor_trans_details";
      }
      $sql .= " WHERE  grn_items.grn_batch_id= grn_batch.id AND  grn_items.po_detail_item= purch_order_details.po_detail_item";
      if ($ponum) {
        $sql .= " AND purch_orders.order_no=purch_order_details.order_no AND (purch_orders.reference LIKE " . DB::_quote(
          '%' . $ponum . '%'
        ) . " OR purch_orders.order_no LIKE " . DB::_quote('%' . $ponum . '%') . ")";
      }
      if ($invoice_no != 0) {
        $sql .= " AND  creditor_trans_details.creditor_trans_type=" . ST_SUPPINVOICE . " AND  creditor_trans_details.creditor_trans_no=$invoice_no AND  grn_items.id= creditor_trans_details.grn_item_id";
      }
      $sql .= " AND  stock_master.stock_id= grn_items.item_code ";
      if ($begin != "") {
        $sql .= " AND grn_batch.delivery_date>='" . Dates::_dateToSql($begin) . "'";
      }
      if ($end != "") {
        $sql .= " AND grn_batch.delivery_date<='" . Dates::_dateToSql($end) . "'";
      }
      if ($grn_batch_id != 0) {
        $sql .= " AND grn_batch.id=" . DB::_escape($grn_batch_id) . " AND grn_items.grn_batch_id=" . DB::_escape($grn_batch_id);
      }
      if ($is_invoiced_only) {
        $sql .= " AND grn_items.quantity_inv > 0";
      }
      if ($outstanding_only) {
        $sql .= " AND grn_items.qty_recd - grn_items.quantity_inv > 0";
      }
      if ($creditor_id != "") {
        $sql .= " AND grn_batch.creditor_id =" . DB::_escape($creditor_id);
      }
      $sql .= " ORDER BY grn_batch.delivery_date, grn_batch.id, grn_items.id";
      return DB::_query($sql, $sql);
    }
    // get the details for a given grn item
    /**
     * @param $grn_item_no
     *
     * @return ADV\Core\DB\Query\Result|Array
     */
    public static function get_item($grn_item_no) {
      $sql
              = "SELECT grn_items.*, purch_order_details.unit_price,
     grn_items.qty_recd - grn_items.quantity_inv AS QtyOstdg,
     purch_order_details.std_cost_unit
        FROM grn_items, purch_order_details, stock_master
        WHERE grn_items.po_detail_item=purch_order_details.po_detail_item
             AND stock_master.stock_id=grn_items.item_code
            AND grn_items.id=" . DB::_escape($grn_item_no);
      $result = DB::_query($sql, "could not retreive grn item details");
      return DB::_fetch($result);
    }
    /**
     * @param $grn_batch
     * @param $order
     */
    public static function get_items_to_order($grn_batch, &$order) {
      $result = static::get_items($grn_batch);
      if (DB::_numRows($result) > 0) {
        while ($myrow = DB::_fetch($result)) {
          if (is_null($myrow["units"])) {
            $units = "";
          } else {
            $units = $myrow["units"];
          }
          $order->add_to_order(
            $order->lines_on_order + 1,
            $myrow["item_code"],
            1,
            $myrow["description"],
            $myrow["unit_price"],
            $units,
            Dates::_sqlToDate($myrow["delivery_date"]),
            $myrow["quantity_inv"],
            $myrow["qty_recd"],
            $myrow['discount']
          );
          $order->line_items[$order->lines_on_order]->po_detail_rec = $myrow["po_detail_item"];
        } /* line po from purchase order details */
      } //end of checks on returned data set
    }
    // read a grn into an order class
    /**
     * @param $grn_batch
     * @param $order
     */
    public static function get($grn_batch, &$order) {
      $sql       = "SELECT *	FROM grn_batch WHERE id=" . DB::_escape($grn_batch);
      $result    = DB::_query($sql, "The grn sent is not valid");
      $row       = DB::_fetch($result);
      $po_number = $row["purch_order_no"];
      $result    = $order->get_header($po_number);
      if ($result) {
        $order->orig_order_date = Dates::_sqlToDate($row["delivery_date"]);
        $order->location        = $row["loc_code"];
        $order->reference       = $row["reference"];
        static::get_items_to_order($grn_batch, $order);
      }
    }
    // get the GRNs (batch info not details) for a given po number
    /**
     * @param $po_number
     *
     * @return PDOStatement
     */
    public static function get_for_po($po_number) {
      $sql = "SELECT * FROM grn_batch WHERE purch_order_no=" . DB::_escape($po_number);
      return DB::_query($sql, "The grns for the po $po_number could not be retreived");
    }
    /**
     * @param $grn_batch
     *
     * @return bool
     */
    public static function exists($grn_batch) {
      $sql    = "SELECT id FROM grn_batch WHERE id=" . DB::_escape($grn_batch);
      $result = DB::_query($sql, "Cannot retreive a grn");
      return (DB::_numRows($result) > 0);
    }
    /**
     * @param $grn_batch
     *
     * @return bool
     */
    public static function exists_on_invoices($grn_batch) {
      $sql
              = "SELECT creditor_trans_details.id FROM creditor_trans_details,grn_items
        WHERE creditor_trans_details.grn_item_id=grn_items.id
        AND quantity != 0
        AND grn_batch_id=" . DB::_escape($grn_batch);
      $result = DB::_query($sql, "Cannot query GRNs");
      return (DB::_numRows($result) > 0);
    }
    /**
     * @param $type
     * @param $grn_batch
     *
     * @return bool
     */
    public static function void($type, $grn_batch) {
      if ($type != ST_SUPPRECEIVE) {
        $type = ST_SUPPRECEIVE;
      }
      if (static::exists_on_invoices($grn_batch)) {
        return false;
      }
      DB::_begin();
      Bank_Trans::void($type, $grn_batch, true);
      GL_Trans::void($type, $grn_batch, true);
      // clear the quantities of the grn items in the POs and invoices
      $result = static::get_items($grn_batch);
      if (DB::_numRows($result) > 0) {
        while ($myrow = DB::_fetch($result)) {
          $sql
            = "UPDATE purch_order_details
 SET quantity_received = quantity_received - " . $myrow["qty_recd"] . "
 WHERE po_detail_item = " . $myrow["po_detail_item"];
          DB::_query($sql, "a purchase order details record could not be voided.");
        }
      }
      // clear the quantities in the grn items
      $sql
        = "UPDATE grn_items SET qty_recd=0, quantity_inv=0
        WHERE grn_batch_id=" . DB::_escape($grn_batch);
      DB::_query($sql, "A grn detail item could not be voided.");
      // clear the stock move items
      Inv_Movement::void($type, $grn_batch);
      DB::_commit();
      return true;
    }
    /**
     * @param      $po
     * @param bool $editable
     */
    public static function display(&$po, $editable = false) {
      Table::start('standard width90');
      echo '<tr>';
      Cell::labelled(_("Supplier"), $po->supplier_name, "class='label'");
      if (!Bank_Currency::is_company($po->curr_code)) {
        Cell::labelled(_("Order Currency"), $po->curr_code, "class='label'");
      }
      Cell::labelled(_("For Purchase Order"), GL_UI::viewTrans(ST_PURCHORDER, $po->order_no), "class='label'");
      Cell::labelled(_("Ordered On"), $po->orig_order_date, "class='label'");
      Cell::labelled(_("Supplier's Reference"), $po->requisition_no, "class='label'");
      echo '</tr>';
      echo '<tr>';
      if ($editable) {
        if (!isset($_POST['ref'])) {
          $_POST['ref'] = Ref::get_next(ST_SUPPRECEIVE);
        }
        Forms::refCells(_("Reference"), 'ref', '', $_POST['ref'], "class='label'");
        if (!isset($_POST['location'])) {
          $_POST['location'] = $po->location;
        }
        Cell::label(_("Deliver Into Location"), "class='label'");
        Inv_Location::cells(null, 'location', $_POST['location']);
        if (!isset($_POST['DefaultReceivedDate'])) {
          $_POST['DefaultReceivedDate'] = Dates::_newDocDate();
        }
        Forms::dateCells(_("Date Items Received"), 'DefaultReceivedDate', '', true, 0, 0, 0, "class='label'");
      } else {
        Cell::labelled(_("Reference"), $po->reference, "class='label'");
        Cell::labelled(_("Deliver Into Location"), Inv_Location::get_name($po->location), "class='label'");
      }
      echo '</tr>';
      if (!$editable) {
        Table::label(_("Delivery Address"), $po->delivery_address, "class='label'", "colspan=9");
      }
      if ($po->Comments != "") {
        Table::label(_("Order Comments"), $po->Comments, "class='label'", "colspan=9");
      }
      Table::end(1);
    }
    //--------------
    /**
     * @param $creditor_trans
     * @param $k
     *
     * @return bool
     */
    public static function display_for_selection($creditor_trans, $k) {
      if ($creditor_trans->is_invoice) {
        $result = Purch_GRN::get_items(0, $creditor_trans->creditor_id, true);
      } else {
        if (isset($_POST['receive_begin']) && isset($_POST['receive_end'])) {
          $result = Purch_GRN::get_items(0, $creditor_trans->creditor_id, false, true, 0, $_POST['receive_begin'], $_POST['receive_end']);
        } else {
          if (isset($_POST['invoice_no'])) {
            $result = Purch_GRN::get_items(0, $creditor_trans->creditor_id, false, true, $_POST['invoice_no']);
          } else {
            $result = Purch_GRN::get_items(0, $creditor_trans->creditor_id, false, true);
          }
        }
      }
      if (DB::_numRows($result) == 0) {
        return false;
      }
      /*Set up a table to show the outstanding GRN items for selection */
      while ($myrow = DB::_fetch($result)) {
        $grn_already_on_invoice = false;
        foreach ($creditor_trans->grn_items as $entered_grn) {
          if ($entered_grn->id == $myrow["id"]) {
            $grn_already_on_invoice = true;
          }
        }
        if ($grn_already_on_invoice == false) {
          if (!isset($_SESSION['delivery_po']) || $myrow["purch_order_no"] == $_SESSION['delivery_po']) {
            $n = $myrow["id"];
            Cell::label(GL_UI::viewTrans(25, $myrow["grn_batch_id"]));
            Cell::label(
              $myrow["id"] . Forms::hidden('qty_recd' . $n, $myrow["qty_recd"], false) . Forms::hidden('item_code' . $n, $myrow["item_code"], false) . Forms::hidden(
                'description' . $n,
                $myrow["description"],
                false
              ) . Forms::hidden(
                'prev_quantity_inv' . $n,
                $myrow['quantity_inv'],
                false
              ) . Forms::hidden('order_price' . $n, $myrow['unit_price'], false) . Forms::hidden(
                'std_cost_unit' . $n,
                $myrow['std_cost_unit'],
                false
              ) . Forms::hidden('po_detail_item' . $n, $myrow['po_detail_item'], false)
            );
            Cell::label(GL_UI::viewTrans(ST_PURCHORDER, $myrow["purch_order_no"]));
            $sql1       = "SELECT supplier_description FROM purch_data WHERE creditor_id=" . DB::_quote($creditor_trans->creditor_id) . " AND stock_id=" . DB::_quote(
              $myrow["item_code"]
            );
            $result1    = DB::_query($sql1, 'Could not get suppliers item code');
            $result1    = DB::_fetch($result1);
            $stock_code = ($myrow["item_code"] != $result1[0]) ? $myrow["item_code"] . '<br>' . $result1[0] : $myrow["item_code"];
            Cell::label($stock_code, "class='stock' data-stock_id='" . $myrow['item_code'] . "'");
            Cell::label($myrow["description"]);
            Cell::label(Dates::_sqlToDate($myrow["delivery_date"]));
            $dec = Item::qty_dec($myrow["item_code"]);
            Cell::qty($myrow["qty_recd"], false, $dec);
            Cell::qty($myrow["quantity_inv"], false, $dec);
            if ($creditor_trans->is_invoice) {
              Forms::qtyCellsSmall(null, 'this_quantity_inv' . $n, Num::_format($myrow["qty_recd"] - $myrow["quantity_inv"], $dec), null, null, $dec);
            } else {
              Forms::qtyCellsSmall(null, 'this_quantityCredited' . $n, Num::_format(max($myrow["quantity_inv"], 0), $dec), null, null, $dec);
            }
            $dec2 = User::_price_dec();
            Forms::amountCellsSmall(null, 'ChgPrice' . $n, Num::_priceFormat($myrow["unit_price"]), null, ['$'], $dec2, 'ChgPriceCalc' . $n);
            Forms::amountCellsSmall(null, 'ExpPrice' . $n, Num::_priceFormat($myrow["unit_price"]), null, ['$'], $dec2, 'ExpPriceCalc' . $n);
            Forms::amountCellsSmall(null, 'ChgDiscount' . $n, Num::_percentFormat($myrow['discount'] * 100), null, '%', User::_percent_dec());
            Cell::amount(
              Num::_priceDecimal(($myrow["unit_price"] * ($myrow["qty_recd"] - $myrow["quantity_inv"]) * (1 - $myrow['discount'])) / $myrow["qty_recd"], $dec2),
              false,
              ' data-dec="' . $dec2 . '"',
              'Ea' . $n
            );
            if ($creditor_trans->is_invoice) {
              Forms::amountCellsSmall(
                null,
                'ChgTotal' . $n,
                Num::_priceDecimal($myrow["unit_price"] * ($myrow["qty_recd"] - $myrow["quantity_inv"]) * (1 - $myrow['discount']), $dec2),
                null,
                ['$'],
                $dec2,
                'ChgTotalCalc' . $n
              );
            } else {
              Forms::amountCellsSmall(
                null,
                'ChgTotal' . $n,
                Num::_priceDecimal($myrow["unit_price"] * ($myrow["qty_recd"] - $myrow["quantity_inv"]) * (1 - $myrow['discount']), $dec2),
                null,
                ['$'],
                $dec2,
                'ChgTotalCalc' . $n
              );
            }
            Forms::submitCells('grn_item_id' . $n, _("Add"), '', ($creditor_trans->is_invoice ? _("Add to Invoice") : _("Add to Credit Note")), true);
            if ($creditor_trans->is_invoice && User::_i()->hasAccess(SA_GRNDELETE)
            ) { // Added 2008-10-18 by Joe Hunt. Special access rights needed.
              Forms::submitCells(
                'void_item_id' . $n,
                _("Remove"),
                '',
                _("WARNING! Be careful with removal. The operation is executed immediately and cannot be undone !!!"),
                true
              );
              Forms::submitConfirm(
                'void_item_id' . $n,
                sprintf(
                  _(
                    'You are about to remove all yet non-invoiced items from delivery line #%d. This operation also irreversibly changes related order line. Do you want to continue ?'
                  ),
                  $n
                )
              );
            }
            echo "<td>";
            Display::link_params("/purchases/order", _("Modify"), "ModifyOrder=" . $myrow["purch_order_no"], ' class="button"');
            echo "</td>\n";
            echo '<tr>';
          }
        }
      }
      if (isset($_SESSION['delivery_grn'])) {
        unset($_SESSION['delivery_grn']);
      }
      return true;
    }
    // $mode = 0 none at the moment
    //		 = 1 display on invoice/credit page
    //		 = 2 display on view invoice
    //		 = 3 display on view credit
    /**
     * @param     $creditor_trans
     * @param int $mode
     *
     * @return int
     */
    public static function display_items($creditor_trans, $mode = 0) {
      $ret = true;
      // if displaying in form, and no items, exit
      if (($mode == 2 || $mode == 3) && count($creditor_trans->grn_items) == 0) {
        return 0;
      }
      Table::startOuter('noborder');
      $heading2 = "";
      if ($mode == 1) {
        if ($creditor_trans->is_invoice) {
          $heading = _("Items Received Yet to be Invoiced");
          if (User::_i()->hasAccess(SA_GRNDELETE)) // Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
          {
            $heading2 = _("WARNING! Be careful with removal. The operation is executed immediately and cannot be undone !!!");
          }
        } else {
          $heading = _("Delivery Item Selected For Adding To A Supplier Credit Note");
        }
      } else {
        if ($creditor_trans->is_invoice) {
          $heading = _("Received Items Charged on this Invoice");
        } else {
          $heading = _("Received Items Credited on this Note");
        }
      }
      Display::heading($heading);
      if ($mode == 1) {
        if (!$creditor_trans->is_invoice && !isset($_POST['invoice_no'])) {
          echo "</tr><tr><table><tr><td>";
          Forms::dateCells(_("Received between"), 'receive_begin', "", null, -30, 0, 0, "class='vmiddle'");
          Forms::dateCells(_("and"), 'receive_end', '', null, 1, 0, 0, "class='vmiddle'");
          Forms::submitCells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), true);
          echo "</td></tr></table></tr><tr>";
        }
        if ($heading2 != "") {
          Event::warning($heading2, 0, 0, "class='overduefg'");
        }
        echo "</td><td width=10% class='alignright'>";
        Forms::submit('InvGRNAll', _("Add All Items"), true, false, 'button-large');
        Table::endOuter(0, false);
        Table::startOuter('center');
        echo '<tr>';
        /*       Forms::dateCells(_("Received between"), 'receive_begin', "", null, -30, 0, 0, "class='vmiddle'");
 Forms::dateCells(_("and"), 'receive_end', '', null, 1, 0, 0, "class='vmiddle'");*/
        Forms::textCells(_("PO #"), "PONumber");
        Forms::submitCells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), true);
        echo '</tr>';
      }
      Table::endOuter(0, false);
      Ajax::_start_div('grn_items');
      Table::start('standard grid width90');
      if ($mode == 1) {
        $th = [
          _("Delivery"),
          _("Seq #"),
          _("P.O."),
          _("Item"),
          _("Description"),
          _("Date"),
          _("Rec"),
          _("Inv"),
          _("Qty"),
          _("Price"),
          _("ExpPrice"),
          _('Disc %'),
          _('Ea Price'),
          _("Total"),
          "",
          "",
          ""
        ];
        // if ($creditor_trans->is_invoice && CurrentUser::_get()->hasAccess(SA_GRNDELETE)) // Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
        // $th[] = "";
        if (!$creditor_trans->is_invoice) {
          unset($th[14]);
          $th[8] = _("Credit QTY");
        }
      } else {
        $th = [
          _("Delivery"),
          _("Item"),
          _("Description"),
          _("Quantity"),
          _("Price"),
          _("Expected Price"),
          _("Disc %"),
          _("Each Price"),
          _("Line Value")
        ];
      }
      Table::header($th);
      $total_grn_value = 0;
      $i               = $k = 0;
      if (count($creditor_trans->grn_items) > 0) {
        foreach ($creditor_trans->grn_items as $entered_grn) {
          $grn_batch = Purch_GRN::get_batch_for_item($entered_grn->id);
          Cell::label(GL_UI::viewTrans(ST_SUPPRECEIVE, $grn_batch));
          if ($mode == 1) {
            Cell::label($entered_grn->id);
            Cell::label(""); // PO
          }
          Cell::label($entered_grn->item_code, "class='stock' data-stock_id='{$entered_grn->item_code}'");
          Cell::label($entered_grn->description);
          $dec = Item::qty_dec($entered_grn->item_code);
          if ($mode == 1) {
            Cell::label("");
            Cell::qty($entered_grn->qty_recd, false, $dec);
            Cell::qty($entered_grn->prev_quantity_inv, false, $dec);
          }
          Cell::qty(abs($entered_grn->this_quantity_inv), true, $dec);
          Cell::amountDecimal($entered_grn->chg_price);
          Cell::amountDecimal($entered_grn->exp_price);
          Cell::percent($entered_grn->discount);
          Cell::amountDecimal(
            Num::_round(($entered_grn->chg_price * abs($entered_grn->this_quantity_inv) * (1 - $entered_grn->discount / 100)) / abs($entered_grn->this_quantity_inv)),
            User::_price_dec()
          );
          Cell::amount(Num::_round($entered_grn->chg_price * abs($entered_grn->this_quantity_inv) * (1 - $entered_grn->discount / 100), User::_price_dec()));
          if ($mode == 1) {
            if ($creditor_trans->is_invoice && User::_i()->hasAccess(SA_GRNDELETE)) {
              Cell::label("");
            }
            Cell::label(""); // PO
            Forms::buttonDeleteCell("Delete" . $entered_grn->id, _("Edit"), _('Edit document line'));
          }
          echo '</tr>';
          $total_grn_value += Num::_round($entered_grn->chg_price * abs($entered_grn->this_quantity_inv) * (1 - $entered_grn->discount / 100), User::_price_dec());
          $i++;
          if ($i > 15) {
            $i = 0;
            Table::header($th);
          }
        }
      }
      if ($mode == 1) {
        $ret     = Purch_GRN::display_for_selection($creditor_trans, $k);
        $colspan = 13;
      } else {
        $colspan = 8;
      }
      Table::label(_("Total"), Num::_priceFormat($total_grn_value), "colspan=$colspan class='alignright bold'", "nowrap class='alignright bold'");
      if (!$ret) {
        echo '<tr>';
        echo "<td colspan=" . ($colspan + 1) . ">";
        if ($creditor_trans->is_invoice) {
          Event::warning(_("There are no outstanding items received from this supplier that have not been invoiced by them."), 0, 0);
        } else {
          Event::warning(_("There are no received items for the selected supplier that have been invoiced.<br>Credits can only be applied to invoiced items."));
        }
        echo "</td>";
        echo '</tr>';
      }
      Table::end(1);
      Ajax::_end_div();
      return $total_grn_value;
    }
  }
