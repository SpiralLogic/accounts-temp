<?php
  use ADV\Core\Table;
  use ADV\Core\Input\Input;
  use ADV\App\Tax\Tax;
  use ADV\App\Item\Item;
  use ADV\App\WO\WO;
  use ADV\Core\Cell;
  use ADV\App\Forms;
  use ADV\Core\Ajax;
  use ADV\App\Validation;
  use ADV\App\Dates;
  use ADV\App\Ref;
  use ADV\App\Reporting;
  use ADV\App\Debtor\Debtor;
  use ADV\App\Display;
  use ADV\App\Orders;
  use ADV\Core\JS;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  //
  //	Entry/Modify Delivery Note against Sales Order
  //
  JS::_openWindow(950, 500);
  $page_title = _($help_context = "Deliver Items for a Sales Order");
  if (isset($_GET[Orders::MODIFY_DELIVERY])) {
    $page_title   = sprintf(_("Modifying Delivery Note # %d."), $_GET[Orders::MODIFY_DELIVERY]);
    $help_context = "Modifying Delivery Note";
  }
  Page::start($page_title, SA_SALESDELIVERY);
  if (isset($_GET[ADDED_ID])) {
    $dispatch_no = $_GET[ADDED_ID];
    Event::success(sprintf(_("Delivery # %d has been entered."), $dispatch_no));
    Display::note(Debtor::viewTrans(ST_CUSTDELIVERY, $dispatch_no, _("&View This Delivery"), 0, 'button button-large'), 1, 0);
    Display::note(Reporting::print_doc_link($dispatch_no, _("&Print Delivery Note"), true, ST_CUSTDELIVERY), 1, 0);
    Display::note(Reporting::print_doc_link($dispatch_no, _("&Email Delivery Note"), true, ST_CUSTDELIVERY, false, "printlink button", "", 1), 1, 0);
    Display::note(Reporting::print_doc_link($dispatch_no, _("P&rint as Packing Slip"), true, ST_CUSTDELIVERY, false, "printlink button"), 1, 0);
    Display::note(Reporting::print_doc_link($dispatch_no, _("E&mail as Packing Slip"), true, ST_CUSTDELIVERY, false, "printlink button", "", 1, 1), 1, 0);
    Display::note(GL_UI::view(13, $dispatch_no, _("View the GL Journal Entries"), 0, 'button button-large'), 1, 0);
    Display::submenu_option(_("Invoice This Delivery"), "/sales/customer_invoice.php?DeliveryNumber=$dispatch_no");
    Display::submenu_option(_("Select Another Order For Dispatch"), "/sales/search/orders?OutstandingOnly=1");
    Page::footer_exit();
  } elseif (isset($_GET[UPDATED_ID])) {
    $delivery_no = $_GET[UPDATED_ID];
    Event::success(sprintf(_('Delivery Note # %d has been updated.'), $delivery_no));
    Display::note(GL_UI::viewTrans(ST_CUSTDELIVERY, $delivery_no, _("View this delivery"), 0, 'button  button-large'), 1, 0);
    Display::note(Reporting::print_doc_link($delivery_no, _("&Print Delivery Note"), true, ST_CUSTDELIVERY));
    Display::note(Reporting::print_doc_link($delivery_no, _("&Email Delivery Note"), true, ST_CUSTDELIVERY, false, "printlink button", "", 1), 1, 1);
    Display::note(Reporting::print_doc_link($delivery_no, _("P&rint as Packing Slip"), true, ST_CUSTDELIVERY, false, "printlink button", "", 0, 1));
    Display::note(Reporting::print_doc_link($delivery_no, _("E&mail as Packing Slip"), true, ST_CUSTDELIVERY, false, "printlink button", "", 1, 1), 1);
    Display::link_params("/sales/customer_invoice.php", _("Confirm Delivery and Invoice"), "DeliveryNumber=$delivery_no");
    Display::link_params("/sales/search/deliveries", _("Select A Different Delivery"), "OutstandingOnly=1");
    Page::footer_exit();
  }
  $order = Orders::session_get() ? : null;
  if (isset($_GET['OrderNumber']) && $_GET['OrderNumber'] > 0) {
    $order = new Sales_Order(ST_SALESORDER, $_GET['OrderNumber'], true);
    /*read in all the selected order into the Items order */
    if ($order->count_items() == 0) {
      Display::link_params("/sales/search/orders", _("Select a different sales order to delivery"), "OutstandingOnly=1");
      die ("<br><span class='bold'>" . _("This order has no items. There is nothing to delivery.") . "</span>");
    }
    $order->trans_type    = ST_CUSTDELIVERY;
    $order->src_docs      = $order->trans_no;
    $order->order_no      = key($order->trans_no);
    $order->trans_no      = 0;
    $order->reference     = Ref::get_next(ST_CUSTDELIVERY);
    $order->document_date = Dates::_newDocDate();
    Sales_Delivery::copyToPost($order);
  } elseif (isset($_GET[Orders::MODIFY_DELIVERY]) && $_GET[Orders::MODIFY_DELIVERY] > 0) {
    $order = new Sales_Order(ST_CUSTDELIVERY, $_GET['ModifyDelivery']);
    Sales_Delivery::copyToPost($order);
    if ($order->count_items() == 0) {
      Display::link_params("/sales/search/orders", _("Select a different delivery"), "OutstandingOnly=1");
      echo "<br><div class='center'><span class='bold'>" . _("This delivery has all items invoiced. There is nothing to modify.") . "</div></span>";
      Page::footer_exit();
    }
  } elseif (!Orders::session_exists($order)) {
    /* This page can only be called with an order number for invoicing*/
    Event::error(_("This page can only be opened if an order or delivery note has been selected. Please select it first."));
    Display::link_params("/sales/search/orders", _("Select a Sales Order to Delivery"), "OutstandingOnly=1");
    Page::end();
    exit;
  } else {
    if (!Sales_Delivery::check_quantities($order)) {
      Event::error(_("Selected quantity cannot be less than quantity invoiced nor more than quantity	not dispatched on sales order."));
    } elseif (!Validation::post_num('ChargeFreightCost', 0)) {
      Event::error(_("Freight cost cannot be less than zero"));
      JS::_setFocus('ChargeFreightCost');
    }
  }
  if (isset($_POST['process_delivery']) && Sales_Delivery::check_data($order) && Sales_Delivery::check_qoh($order)) {
    $dn = $order;
    if ($_POST['bo_policy']) {
      $bo_policy = 0;
    } else {
      $bo_policy = 1;
    }
    $newdelivery = ($dn->trans_no == 0);
    Sales_Delivery::copyFromPost($order);
    if ($newdelivery) {
      Dates::_newDocDate($dn->document_date);
    }
    $delivery_no = $dn->write($bo_policy);
    $dn->finish();
    if ($newdelivery) {
      Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$delivery_no");
    } else {
      Display::meta_forward($_SERVER['DOCUMENT_URI'], "UpdatedID=$delivery_no");
    }
  }
  if (isset($_POST['Update']) || isset($_POST['_location_update'])) {
    Ajax::_activate('Items');
  }
  Forms::start();
  Forms::hidden('order_id');
  Table::start('standard width90 pad5');
  echo "<tr><td>"; // outer table
  Table::start('padded width100');
  echo '<tr>';
  Cell::labelled(_("Customer"), $order->customer_name, "class='label'");
  Cell::labelled(_("Branch"), Sales_Branch::get_name($order->Branch), "class='label'");
  Cell::labelled(_("Currency"), $order->customer_currency, "class='label'");
  echo '</tr>';
  echo '<tr>';
  //if (!isset($_POST['ref']))
  //	$_POST['ref'] = Ref::get_next(ST_CUSTDELIVERY);
  if ($order->trans_no == 0) {
    Forms::refCells(_("Reference"), 'ref', '', Ref::get_next(ST_CUSTDELIVERY), "class='label'");
  } else {
    Cell::labelled(_("Reference"), $order->reference, "class='label'");
    Forms::hidden('ref', $order->reference);
  }
  Cell::labelled(_("For Sales Order"), Debtor::viewTrans(ST_SALESORDER, $order->order_no), "class='tablerowhead'");
  Cell::labelled(_("Sales Type"), $order->sales_type_name, "class='label'");
  echo '</tr>';
  echo '<tr>';
  if (!isset($_POST['location'])) {
    $_POST['location'] = $order->location;
  }
  Cell::label(_("Delivery From"), "class='label'");
  Inv_Location::cells(null, 'location', null, false, true);
  if (!isset($_POST['ship_via'])) {
    $_POST['ship_via'] = $order->ship_via;
  }
  Cell::label(_("Shipping Company"), "class='label'");
  Sales_UI::shippers_cells(null, 'ship_via', $_POST['ship_via']);
  // set this up here cuz it's used to calc qoh
  if (!isset($_POST['DispatchDate']) || !Dates::_isDate($_POST['DispatchDate'])) {
    $_POST['DispatchDate'] = Dates::_newDocDate();
    if (!Dates::_isDateInFiscalYear($_POST['DispatchDate'])) {
      $_POST['DispatchDate'] = Dates::_endFiscalYear();
    }
  }
  Forms::dateCells(_("Date"), 'DispatchDate', '', $order->trans_no == 0, 0, 0, 0, "class='label'");
  echo '</tr>';
  Table::end();
  echo "</td><td>"; // outer table
  Table::start('padded width90');
  if (!isset($_POST['due_date']) || !Dates::_isDate($_POST['due_date'])) {
    $_POST['due_date'] = $order->get_invoice_duedate($order->debtor_id, $_POST['DispatchDate']);
  }
  echo '<tr>';
  Forms::dateCells(_("Invoice Dead-line"), 'due_date', '', null, 0, 0, 0, "class='label'");
  echo '</tr>';
  Table::end();
  echo "</td></tr>";
  Table::end(1); // outer table
  $row = Sales_Order::get_customer($order->debtor_id);
  if ($row['dissallow_invoices'] == 1) {
    Event::error(_("The selected customer account is currently on hold. Please contact the credit control personnel to discuss."));
    Forms::end();
    Page::end();
    exit();
  }
  Display::heading(_("Delivery Items"));
  Ajax::_start_div('Items');
  Table::start('padded grid width90');
  $new = $order->trans_no == 0;
  $th  = array(
    _("Item Code"),
    _("Item Description"),
    $new ? _("Ordered") : _("Max. delivery"),
    _("Units"),
    $new ? _("Delivered") : _("Invoiced"),
    _("This Delivery"),
    _("Price"),
    _("Tax Type"),
    _("Discount"),
    _("Total")
  );
  Table::header($th);
  $k          = 0;
  $has_marked = false;
  foreach ($order->line_items as $line_no => $line) {
    if ($line->quantity == $line->qty_done) {
      continue; //this line is fully delivered
    }
    // if it's a non-stock item (eg. service) don't show qoh
    $show_qoh = true;
    if (DB_Company::_get_pref('allow_negative_stock') || !WO::has_stock_holding($line->mb_flag) || $line->qty_dispatched == 0
    ) {
      $show_qoh = false;
    }
    if ($show_qoh) {
      $qoh = Item::get_qoh_on_date($line->stock_id, $_POST['location'], $_POST['DispatchDate']);
    }
    if ($show_qoh && ($line->qty_dispatched > $qoh)) {
      // oops, we don't have enough of one of the component items
      echo "<tr class='stockmankobg'>";
      $has_marked = true;
    } else {
    }
    Item_UI::status_cell($line->stock_id);
    Forms::textCells(null, 'Line' . $line_no . 'Desc', $line->description, 30, 50);
    $dec = Item::qty_dec($line->stock_id);
    Cell::qty($line->quantity, false, $dec);
    Cell::label($line->units);
    Cell::qty($line->qty_done, false, $dec);
    Forms::qtyCellsSmall(null, 'Line' . $line_no, Item::qty_format($line->qty_dispatched, $line->stock_id, $dec), null, null, $dec);
    $display_discount_percent = Num::_percentFormat($line->discount_percent * 100) . "%";
    $line_total               = ($line->qty_dispatched * $line->price * (1 - $line->discount_percent));
    Cell::amount($line->price);
    Cell::label($line->tax_type_name);
    Cell::label($display_discount_percent, ' class="alignright nowrap"');
    Cell::amount($line_total);
    echo '</tr>';
  }
  $_POST['ChargeFreightCost'] = Input::_post('ChargeFreightCost', null, Num::_priceFormat($order->freight_cost));
  $colspan                    = 9;
  Table::foot();
  echo '<tr>';
  Cell::label(_("Shipping Cost"), "colspan=$colspan class='alignright'");
  Forms::amountCellsSmall(null, 'ChargeFreightCost', $order->freight_cost);
  echo '</tr>';
  $inv_items_total   = $order->get_items_total_dispatch();
  $display_sub_total = Num::_priceFormat($inv_items_total + Validation::input_num('ChargeFreightCost'));
  Table::label(_("Sub-total"), $display_sub_total, "colspan=$colspan class='alignright'", "class='alignright'");
  $taxes         = $order->get_taxes(Validation::input_num('ChargeFreightCost'));
  $tax_total     = Tax::edit_items($taxes, $colspan, $order->tax_included);
  $display_total = Num::_priceFormat(($inv_items_total + Validation::input_num('ChargeFreightCost') + $tax_total));
  Table::label(_("Amount Total"), $display_total, "colspan=$colspan class='alignright'", "class='alignright'");
  Table::footEnd();
  Table::end(1);
  if ($has_marked) {
    Event::warning(_("Marked items have insufficient quantities in stock as on day of delivery."), 0, 1, "class='red'");
  }
  Table::start('standard');
  Sales_UI::policy_row(_("Action For Balance"), "bo_policy", null);
  Forms::textareaRow(_("Memo"), 'Comments', null, 50, 4);
  Table::end(1);
  Ajax::_end_div();
  Forms::submitCenterBegin('Update', _("Update"), _('Refresh document page'), true);
  Forms::submitCenterEnd('process_delivery', _("Process Dispatch"), _('Check entered data and save document'), 'default');
  Forms::end();
  Page::end();
