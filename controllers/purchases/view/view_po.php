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
  Page::start(_($help_context = "View Purchase Order"), SA_SUPPTRANSVIEW, true);
  if (!isset($_GET['trans_no'])) {
    die ("<br>" . _("This page must be called with a purchase order number to review."));
  }
  $order = new Purch_Order($_GET['trans_no']);
  echo "<br>";
  $order->summary(true);
  Table::start('padded width90 pad6');
  Display::heading(_("Line Details"));
  Table::start('padded grid width100');
  $th = array(
    _("Code"),
    _("Item"),
    _("Qty"),
    _("Unit"),
    _("Price"),
    _("Disc"),
    _("Total"),
    _("Needed By"),
    _("Received"),
    _("Invoiced")
  );
  Table::header($th);
  $total            = $k = 0;
  $overdue_items    = false;
  $still_to_receive = false;
  foreach ($order->line_items as $stock_item) {
    $line_total = $stock_item->quantity * $stock_item->price * (1 - $stock_item->discount);
    // if overdue and outstanding quantities, then highlight as so
    if (($stock_item->quantity - $stock_item->qty_received > 0) && Dates::_isGreaterThan(Dates::_today(), $stock_item->req_del_date)
    ) {
      echo "<tr class='overduebg'>";
      $overdue_items = true;
    } else {
    }
    if ($stock_item->quantity - $stock_item->qty_received > 0) {
      $still_to_receive = true;
    }
    Cell::label($stock_item->stock_id);
    Cell::label($stock_item->description);
    $dec = Item::qty_dec($stock_item->stock_id);
    Cell::qty($stock_item->quantity, false, $dec);
    Cell::label($stock_item->units);
    Cell::amountDecimal($stock_item->price);
    Cell::percent($stock_item->discount * 100);
    Cell::amount($line_total);
    Cell::label($stock_item->req_del_date);
    Cell::qty($stock_item->qty_received, false, $dec);
    Cell::qty($stock_item->qty_inv, false, $dec);
    echo '</tr>';
    $total += $line_total;
  }
  $display_total = Num::_format($total, User::_price_dec());
  Table::label(_("Total Excluding Tax/Shipping"), $display_total, "class='alignright' colspan=6", ' class="alignright nowrap"', 3);
  Table::end();
  if ($overdue_items) {
    Event::warning(_("Marked items are overdue."), 0, 0, "class='overduefg'");
  }
  $k           = 0;
  $grns_result = Purch_GRN::get_for_po($_GET['trans_no']);
  if (DB::_numRows($grns_result) > 0) {
    echo "</td><td class='top'>"; // outer table
    Display::heading(_("Deliveries"));
    Table::start('padded grid');
    $th = array(_("#"), _("Reference"), _("Delivered On"));
    Table::header($th);
    while ($myrow = DB::_fetch($grns_result)) {
      Cell::label(GL_UI::viewTrans(ST_SUPPRECEIVE, $myrow["id"]));
      Cell::label($myrow["reference"]);
      Cell::label(Dates::_sqlToDate($myrow["delivery_date"]));
      echo '</tr>';
    }
    Table::end();
  }
  $invoice_result = Purch_Invoice::get_po_credits($_GET['trans_no']);
  $k              = 0;
  if (DB::_numRows($invoice_result) > 0) {
    echo "</td><td class='top'>"; // outer table
    Display::heading(_("Invoices/Credits"));
    Table::start('padded grid');
    $th = array(_("#"), _("Date"), _("Total"));
    Table::header($th);
    while ($myrow = DB::_fetch($invoice_result)) {
      Cell::label(GL_UI::viewTrans($myrow["type"], $myrow["trans_no"]));
      Cell::label(Dates::_sqlToDate($myrow["tran_date"]));
      Cell::amount($myrow["Total"]);
      echo '</tr>';
    }
    Table::end();
  }
  echo "</td></tr>";
  Table::end(1); // outer table
  if (Input::_get('frame')) {
    return;
  }
  Display::submenu_print(_("Print This Order"), ST_PURCHORDER, $_GET['trans_no'], 'prtopt');
  Display::submenu_option(_("&Edit This Order"), "/purchases/order?ModifyOrder=" . $_GET['trans_no']);
  if ($still_to_receive) {
    Display::submenu_option(_("&Receive Items on this PO"), "/purchases/po_receive_items.php?PONumber=" . $_GET['trans_no']);
  }
  Page::end(true);


