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
  Page::start(_($help_context = "View Purchase Order Delivery"), SA_SUPPTRANSVIEW, true);
  if (!isset($_GET['trans_no'])) {
    die ("<BR>" . _("This page must be called with a Purchase Order Delivery number to review."));
  }
  $purchase_order = new Purch_Order;
  Purch_GRN::get($_GET["trans_no"], $purchase_order);
  Display::heading(_("Purchase Order Delivery") . " #" . $_GET['trans_no']);
  echo "<br>";
  Purch_GRN::display($purchase_order);
  Display::heading(_("Line Details"));
  Table::start('padded grid width90');
  $th = array(
    _("Item Code"),
    _("Item Description"),
    _("Delivery Date"),
    _("Quantity"),
    _("Unit"),
    _("Price"),
    _("Line Total"),
    _("Quantity Invoiced")
  );
  Table::header($th);
  $total = 0;
  $k     = 0; //row colour counter
  foreach ($purchase_order->line_items as $stock_item) {
    $line_total = $stock_item->qty_received * $stock_item->price;
    Cell::label($stock_item->stock_id);
    Cell::label($stock_item->description);
    Cell::label($stock_item->req_del_date, ' class="alignright nowrap"');
    $dec = Item::qty_dec($stock_item->stock_id);
    Cell::qty($stock_item->qty_received, false, $dec);
    Cell::label($stock_item->units);
    Cell::amountDecimal($stock_item->price);
    Cell::amount($line_total);
    Cell::qty($stock_item->qty_inv, false, $dec);
    echo '</tr>';
    $total += $line_total;
  }
  $display_total = Num::_format($total, User::_price_dec());
  Table::label(_("Total Excluding Tax/Shipping"), $display_total, "colspan=6", ' class="alignright nowrap"');
  Table::end(1);
  Voiding::is_voided(ST_SUPPRECEIVE, $_GET['trans_no'], _("This delivery has been voided."));
  Page::end(true);


