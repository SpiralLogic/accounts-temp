<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::_openWindow(950, 500);
  Page::start(_($help_context = "Receive Purchase Order Items"), SA_GRN);
  if (isset($_GET[ADDED_ID])) {
    $grn        = $_GET[ADDED_ID];
    $trans_type = ST_SUPPRECEIVE;
    Event::success(_("Purchase Order Delivery has been processed"));
    Display::note(GL_UI::viewTrans($trans_type, $grn, _("&View this Delivery")));
    Display::link_params("/purchases/invoice", _("Enter purchase &invoice for this receival"), "New=1&PONumber=" . $_GET[ADDED_ID]);
    Display::link_params("/purchases/search/orders", _("Select a different &purchase order for receiving items against"));
    Page::footer_exit();
  }
  $order = Orders::session_get() ? : null;
  if (isset($_GET['PONumber']) && $_GET['PONumber'] > 0 && !isset($_POST['Update'])) {
    $order = new Purch_Order($_GET['PONumber']);
  } elseif ((!isset($_GET['PONumber']) || $_GET['PONumber'] == 0) && !isset($_POST['order_id'])) {
    Event::error(_("This page can only be opened if a purchase order has been selected. Please select a purchase order first."));
    Page::footer_exit();
  }
  $order             = Purch_Order::check_edit_conflicts($order);
  $_POST['order_id'] = $order->order_id;
  Orders::session_set($order);
  /*read in all the selected order into the Items order */
  if (isset($_POST['Update']) || isset($_POST['ProcessGoodsReceived'])) {
    /* if update quantities button is hit page has been called and ${$line->line_no} would have be
set from the post to the quantity to be received in this receival*/
    foreach ($order->line_items as $line) {
      if (($line->quantity - $line->qty_received) > 0) {
        $_POST[$line->line_no] = max($_POST[$line->line_no], 0);
        if (!Validation::post_num($line->line_no)) {
          $_POST[$line->line_no] = Num::_format(0, Item::qty_dec($line->stock_id));
        }
        if (!isset($_POST['DefaultReceivedDate']) || $_POST['DefaultReceivedDate'] == "") {
          $_POST['DefaultReceivedDate'] = Dates::_newDocDate();
        }
        $order->line_items[$line->line_no]->receive_qty = Validation::input_num($line->line_no);
        if (isset($_POST[$line->stock_id . "Desc"]) && strlen($_POST[$line->stock_id . "Desc"]) > 0) {
          $order->line_items[$line->line_no]->description = $_POST[$line->stock_id . "Desc"];
        }
      }
    }
    Ajax::_activate('grn_items');
  }
  if (isset($_POST['ProcessGoodsReceived']) && $order->can_receive()) {
    Session::_setGlobal('creditor_id', $order->creditor_id);
    if ($order->has_changed()) {
      Event::error(
        _(
          "This order has been changed or invoiced since this delivery was started to be actioned. Processing halted. To enter a delivery against this purchase order, it must be re-selected and re-read again to update the changes made by the other user."
        )
      );
      Display::link_params("/purchases/search/orders", _("Select a different purchase order for receiving goods against"));
      Display::link_params("/purchases/po_receive_items.php", _("Re-Read the updated purchase order for receiving goods against"), "PONumber=" . $order->order_no);
      unset($order->line_items, $order, $_POST['ProcessGoodsReceived']);
      Ajax::_activate('_page_body');
      Page::footer_exit();
    }
    $grn                     = Purch_GRN::add($order, $_POST['DefaultReceivedDate'], $_POST['ref'], $_POST['location']);
    $_SESSION['delivery_po'] = $order->order_no;
    Dates::_newDocDate($_POST['DefaultReceivedDate']);
    unset($order->line_items);
    $order->finish($_POST['order_id']);
    unset($order);
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$grn");
  }
  Forms::start();
  Forms::hidden('order_id');
  Purch_GRN::display($order, true);
  Display::heading(_("Items to Receive"));
  Ajax::_start_div('grn_items');
  Table::start('padded grid width90');
  $th = array(
    _("Item Code"),
    _("Description"),
    _("Ordered"),
    _("Units"),
    _("Received"),
    _("Outstanding"),
    _("This Delivery"),
    _("Price"),
    _('Discount %'),
    _("Total")
  );
  Table::header($th);
  /*show the line items on the order with the quantity being received for modification */
  $total = 0;
  $k     = 0; //row colour counter
  if (count($order->line_items) > 0) {
    foreach ($order->line_items as $line) {
      $qty_outstanding = $line->quantity - $line->qty_received;
      if (!isset($_POST['Update']) && !isset($_POST['ProcessGoodsReceived']) && $line->receive_qty == 0) { //If no quantites yet input default the balance to be received
        $line->receive_qty = $qty_outstanding;
      }
      $line_total = ($line->receive_qty * $line->price * (1 - $line->discount));
      $total += $line_total;
      Cell::label($line->stock_id);
      if ($qty_outstanding > 0) {
        Forms::textCells(null, $line->stock_id . "Desc", $line->description, 30, 50);
      } else {
        Cell::label($line->description);
      }
      $dec = Item::qty_dec($line->stock_id);
      Cell::qty($line->quantity, false, $dec);
      Cell::label($line->units);
      Cell::qty($line->qty_received, false, $dec);
      Cell::qty($qty_outstanding, false, $dec);
      if ($qty_outstanding > 0) {
        Forms::qtyCells(null, $line->line_no, Num::_format($line->receive_qty, $dec), "class='alignright'", null, $dec);
      } else {
        Cell::label(Num::_format($line->receive_qty, $dec), "class='alignright'");
      }
      Cell::amountDecimal($line->price);
      Cell::percent($line->discount * 100);
      Cell::amount($line_total);
      echo '</tr>';
    }
  }
  Cell::label(_("Freight"), "colspan=9 class='alignright'");
  Forms::amountCellsSmall(null, 'freight', Num::_priceFormat($order->freight));
  $display_total = Num::_format($total + $_POST['freight'], User::_price_dec());
  Table::label(_("Total value of items received"), $display_total, "colspan=9 class='alignright'", ' class="alignright nowrap"');
  Table::end();
  Ajax::_end_div();
  Display::link_params("/purchases/order", _("Edit This Purchase Order"), "ModifyOrder=" . $order->order_no);
  echo '<br>';
  Forms::submitCenterBegin('Update', _("Update Totals"), '', true);
  Forms::submitCenterEnd('ProcessGoodsReceived', _("Process Receive Items"), _("Clear all GL entry fields"), 'default');
  Forms::end();
  Page::end();
