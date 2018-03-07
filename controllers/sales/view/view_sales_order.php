<?php
  use ADV\Core\JS;
  use ADV\App\Orders;
  use ADV\Core\Input\Input;
  use ADV\App\Item\Item;
  use ADV\App\User;
  use ADV\App\Dates;
  use ADV\App\Debtor\Debtor;
  use ADV\Core\DB\DB;
  use ADV\App\Forms;
  use ADV\Core\Cell;
  use ADV\App\Display;
  use ADV\Core\Table;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::_openWindow(950, 600);
  if ($_GET['trans_type'] == ST_SALESQUOTE) {
    Page::start(_($help_context = "View Sales Quotation"), SA_SALESTRANSVIEW, true);
  } else {
    Page::start(_($help_context = "View Sales Order"), true);
  }
  if (isset($_SESSION['View'])) {
    unset ($_SESSION['View']);
  }
  $_SESSION['View'] = $view = new Sales_Order($_GET['trans_type'], $_GET['trans_no'], true);
  Table::start('tablesstyle2 pad0 width95');
  echo "<tr class='tablerowhead top'><th colspan=4>";
  if ($_GET['trans_type'] != ST_SALESQUOTE) {
    Display::heading(sprintf(_("Sales Order #%d"), $_GET['trans_no']));
  } else {
    Display::heading(sprintf(_("Sales Quotation #%d"), $_GET['trans_no']));
  }
  echo "</td></tr>";
  echo "<tr class='top'><td colspan=4>";
  Table::start('padded width100');
  echo '<tr>';
  Cell::labelled(_("Customer Name"), $_SESSION['View']->customer_name, "class='label pointer debtor_id_label'", 'class="pointer" id="debtor_id_label"');
  Forms::hidden("debtor_id", $_SESSION['View']->debtor_id);
  Cell::labelled(_("Deliver To Branch"), $_SESSION['View']->deliver_to, "class='label'");
  Cell::labelled(_("Person Ordering"), nl2br($_SESSION['View']->name), "class='label'");
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("Reference"), $_SESSION['View']->reference, "class='label'");
  if ($_GET['trans_type'] == ST_SALESQUOTE) {
    Cell::labelled(_("Valid until"), $_SESSION['View']->due_date, "class='label'");
  } else {
    Cell::labelled(_("Requested Delivery"), $_SESSION['View']->due_date, "class='label'");
  }
  Cell::labelled(_("Telephone"), $_SESSION['View']->phone, "class='label'");
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("Customer PO #"), $_SESSION['View']->cust_ref, "class='label'");
  Cell::labelled(_("Deliver From Location"), $_SESSION['View']->location_name, "class='label'");
  Cell::labelled(_("Delivery Address"), nl2br($_SESSION['View']->delivery_address), "class='label'");
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("Order Currency"), $_SESSION['View']->customer_currency, "class='label'");
  Cell::labelled(_("Ordered On"), $_SESSION['View']->document_date, "class='label'");
  Cell::labelled(_("Email"), "<a href='mailto:" . $_SESSION['View']->email . "'>" . $_SESSION['View']->email . "</a>", "class='label'", "colspan=3");
  echo '</tr>';
  Table::label(_("Comments"), $_SESSION['View']->Comments, "class='label'", "colspan=5");
  Table::end();
  if ($_GET['trans_type'] != ST_SALESQUOTE) {
    echo "</td></tr><tr><td class='top'>";
    Table::start('padded grid width90');
    Display::heading(_("Delivery Notes"));
    $th = array(_("#"), _("Ref"), _("Date"), _("Total"));
    Table::header($th);
    $sql            = "SELECT * FROM debtor_trans WHERE type=" . ST_CUSTDELIVERY . " AND order_=" . DB::_escape($_GET['trans_no']);
    $result         = DB::_query($sql, "The related delivery notes could not be retreived");
    $delivery_total = 0;
    $k              = 0;
    $dn_numbers     = [];
    while ($del_row = DB::_fetch($result)) {
      $dn_numbers[] = $del_row["trans_no"];
      $this_total   = $del_row["ov_freight"] + $del_row["ov_amount"] + $del_row["ov_freight_tax"] + $del_row["ov_gst"];
      $delivery_total += $this_total;
      Cell::label(Debtor::viewTrans($del_row["type"], $del_row["trans_no"]));
      Cell::label($del_row["reference"]);
      Cell::label(Dates::_sqlToDate($del_row["tran_date"]));
      Cell::amount($this_total);
      echo '</tr>';
    }
    Table::label(null, Num::_priceFormat($delivery_total), " ", "colspan=4 class='alignright'");
    Table::end();
    echo "</td><td class='top'>";
    Table::start('padded grid width90');
    Display::heading(_("Sales Invoices"));
    $th = array(_("#"), _("Ref"), _("Date"), _("Total"));
    Table::header($th);
    $inv_numbers    = [];
    $invoices_total = 0;
    if (count($dn_numbers)) {
      $sql    = "SELECT * FROM debtor_trans WHERE type=" . ST_SALESINVOICE . " AND trans_no IN(" . implode(',', array_values($dn_numbers)) . ")";
      $result = DB::_query($sql, "The related invoices could not be retreived");
      $k      = 0;
      while ($inv_row = DB::_fetch($result)) {
        $this_total = $inv_row["ov_freight"] + $inv_row["ov_freight_tax"] + $inv_row["ov_gst"] + $inv_row["ov_amount"];
        $invoices_total += $this_total;
        $inv_numbers[] = $inv_row["trans_no"];
        Cell::label(Debtor::viewTrans($inv_row["type"], $inv_row["trans_no"]));
        Cell::label($inv_row["reference"]);
        Cell::label(Dates::_sqlToDate($inv_row["tran_date"]));
        Cell::amount($this_total);
        echo '</tr>';
      }
    }
    Table::label(null, Num::_priceFormat($invoices_total), " ", "colspan=4 class='alignright'");
    Table::end();
    echo "</td><td class='top'>";
    Table::start('padded grid width90');
    Display::heading(_("Payments"));
    $th = array(_("#"), _("Ref"), _("Date"), _("Total"));
    Table::header($th);
    $payments_total = 0;
    if (count($inv_numbers)) {
      $sql    = "SELECT a.*, d.reference,d.tran_date as date2 FROM debtor_allocations a, debtor_trans d WHERE a.trans_type_from=" . ST_CUSTPAYMENT . " AND a.trans_no_from=d.trans_no AND d.type=" . ST_CUSTPAYMENT . " AND a.trans_no_to IN(" . implode(
        ',', array_values($inv_numbers)
      ) . ")";
      $result = DB::_query($sql, "The related payments could not be retreived");
      $k      = 0;
      while ($payment_row = DB::_fetch($result)) {
        $this_total = $payment_row["amt"];
        $payments_total += $this_total;
        Cell::label(Debtor::viewTrans($payment_row["trans_type_from"], $payment_row["trans_no_from"]));
        Cell::label($payment_row["reference"]);
        Cell::label(Dates::_sqlToDate($payment_row["date2"]));
        Cell::amount($this_total);
        echo '</tr>';
      }
    }
    Table::label(null, Num::_priceFormat($payments_total), " ", "colspan=4 class='alignright'");
    Table::end();
    echo "</td><td class='top'>";
    Table::start('padded grid width90');
    Display::heading(_("Credit Notes"));
    $th = array(_("#"), _("Ref"), _("Date"), _("Total"));
    Table::header($th);
    $credits_total = 0;
    if (count($inv_numbers)) {
      // FIXME - credit notes retrieved here should be those linked to invoices containing
      // at least one line from this order
      $sql    = "SELECT * FROM debtor_trans WHERE type=" . ST_CUSTCREDIT . " AND trans_link IN(" . implode(',', array_values($inv_numbers)) . ")";
      $result = DB::_query($sql, "The related credit notes could not be retreived");
      $k      = 0;
      while ($credits_row = DB::_fetch($result)) {
        $this_total = $credits_row["ov_freight"] + $credits_row["ov_freight_tax"] + $credits_row["ov_gst"] + $credits_row["ov_amount"];
        $credits_total += $this_total;
        Cell::label(Debtor::viewTrans($credits_row["type"], $credits_row["trans_no"]));
        Cell::label($credits_row["reference"]);
        Cell::label(Dates::_sqlToDate($credits_row["tran_date"]));
        Cell::amount(-$this_total);
        echo '</tr>';
      }
    }
    Table::label(null, "<font color=red>" . Num::_priceFormat(-$credits_total) . "</font>", " ", "colspan=4 class='alignright'");
    Table::end();
    echo "</td></tr>";
    Table::end();
  }
  echo "<div class='center'>";
  if ($_SESSION['View']->so_type == 1) {
    Event::warning(_("This Sales Order is used as a Template."), 0, 0, "class='currentfg'");
  }
  Display::heading(_("Line Details"));
  Table::start('padded grid width95');
  $th = array(
    _("Item Code"),
    _("Item Description"),
    _("Quantity"),
    _("Unit"),
    _("Price"),
    _("Discount"),
    _("Total"),
    _("Quantity Delivered")
  );
  Table::header($th);
  $k = 0; //row colour counter
  foreach ($_SESSION['View']->line_items as $stock_item) {
    $line_total = Num::_round($stock_item->quantity * $stock_item->price * (1 - $stock_item->discount_percent), User::_price_dec());
    Cell::label($stock_item->stock_id);
    Cell::label($stock_item->description);
    $dec = Item::qty_dec($stock_item->stock_id);
    Cell::qty($stock_item->quantity, false, $dec);
    Cell::label($stock_item->units);
    Cell::amount($stock_item->price);
    Cell::amount($stock_item->discount_percent * 100);
    Cell::amount($line_total);
    Cell::qty($stock_item->qty_done, false, $dec);
    echo '</tr>';
  }
  $display_total = 0;
  $qty_remaining = array_sum(
    array_map(
      function ($line) {
        return ($line->quantity - $line->qty_done);
      }, $_SESSION['View']->line_items
    )
  );
  $items_total   = $_SESSION['View']->get_items_total();
  Table::foot();
  Table::label(_("Shipping"), Num::_priceFormat($_SESSION['View']->freight_cost), "class='alignright' colspan=6", ' class="alignright nowrap"', 1);
  $taxes = $view->get_taxes_for_order();
  foreach ($taxes as $tax) {
    $display_total += $tax['Value'];
    Table::label(_("Tax: " . $tax['tax_type_name']), Num::_priceFormat($tax['Value']), "class='alignright' colspan=6", ' class="alignright nowrap"', 1);
  }
  $display_total = Num::_priceFormat($items_total + $_SESSION['View']->freight_cost);
  Table::label(_("Total Order Value"), $display_total, "class='alignright' colspan=6", ' class="alignright nowrap"', 1);
  Table::footEnd();
  Table::end(2);
  if (Input::_get('frame')) {
    return;
  }
  if (Input::_get('trans_type') == ST_SALESORDER) {
    Display::submenu_option(_("Clone This Order"), "/sales/order?CloneOrder={$_GET['trans_no']}' target='_top' ");
    Display::submenu_option(_('Edit Order'), "/sales/order?" . Orders::UPDATE . "=" . $_GET['trans_no'] . "&type=" . ST_SALESORDER . "' target='_top' ");
    Display::submenu_print(_("&Print Order"), ST_SALESORDER, $_GET['trans_no'], 'prtopt');
    Display::submenu_print(_("Print Proforma Invoice"), ST_PROFORMA, $_GET['trans_no'], 'prtopt');
    if ($qty_remaining > 0) {
      Display::submenu_option(_("Make &Delivery Against This Order"), "/sales/customer_delivery.php?OrderNumber={$_GET['trans_no']}' target='_top' ");
    } else {
      Display::submenu_option(_("Invoice Items On This Order"), "/sales/customer_delivery.php?OrderNumber={$_GET['trans_no']}' target='_top' ");
    }
    Display::submenu_option(_("Enter a &New Order"), "/sales/order?" . Orders::ADD . "=0&type=30' target='_top' ");
  } elseif (Input::_get('trans_type') == ST_SALESQUOTE) {
    Display::submenu_option(_('Edit Quote'), "/sales/order?" . Orders::UPDATE . "=" . $_GET['trans_no'] . "&type=" . ST_SALESQUOTE . "' target='_top' ");
    Display::submenu_print(_("&Print Quote"), ST_SALESQUOTE, $_GET['trans_no'], 'prtopt');
    Display::submenu_print(_("Print Proforma Invoice"), ST_PROFORMAQ, $_GET['trans_no'], 'prtopt');
    Display::submenu_option(_("Make &Order from This Quote"), "/sales/order?" . Orders::QUOTE_TO_ORDER . '=' . Input::_get('trans_no') . "' target='_top' ");
    Display::submenu_option(_("&New Quote"), "/sales/order?" . Orders::ADD . "=0&type=" . ST_SALESQUOTE . "' target='_top' ");
  }
  //UploadHandler::insert($_GET['trans_no']);
  Debtor::addEditDialog();
  Page::end();

