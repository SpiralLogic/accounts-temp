<?php
  use ADV\App\Debtor\Debtor;
  use ADV\App\Tax\Tax;
  use ADV\App\Validation;
  use ADV\Core\Ajax;
  use ADV\App\Ref;
  use ADV\App\Reporting;
  use ADV\App\Item\Item;
  use ADV\Core\JS;
  use ADV\App\Orders;
  use ADV\App\Dates;
  use ADV\App\Forms;
  use ADV\Core\Cell;
  use ADV\App\Display;
  use ADV\Core\Table;

  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  //	Entry/Modify Sales Invoice against single delivery
  //	Entry/Modify Batch Sales Invoice against batch of deliveries
  //
  JS::_openWindow(950, 500);
  $page_title = 'Sales Invoice Complete';
  if (isset($_GET[Orders::MODIFY_INVOICE])) {
    $page_title   = sprintf(_("Modifying Sales Invoice # %d."), $_GET[Orders::MODIFY_INVOICE]);
    $help_context = "Modifying Sales Invoice";
  } elseif (isset($_GET['DeliveryNumber'])) {
    $page_title = _($help_context = "Issue an Invoice for Delivery Note");
  } elseif (isset($_GET[Orders::BATCH_INVOICE])) {
    $page_title = _($help_context = "Issue Batch Invoice for Delivery Notes");
  } elseif (isset($_GET[Orders::VIEW_INVOICE])) {
    $page_title = sprintf(_("View Sales Invoice # %d."), $_GET[Orders::VIEW_INVOICE]);
  }
  Page::start($page_title, SA_SALESINVOICE);
  $order = Orders::session_get() ? : null;
  if (isset($_GET[ADDED_ID])) {
    $order      = new Sales_Order(ST_SALESINVOICE, $_GET[ADDED_ID]);
    $customer   = new Debtor($order->debtor_id);
    $emails     = $customer->getEmailAddresses();
    $invoice_no = $_GET[ADDED_ID];
    $reference  = $order->reference;
    Event::success(_("Invoice $reference has been entered."));
    $trans_type = ST_SALESINVOICE;
    Event::success(_("Selected deliveries has been processed"), true);
    Display::note(Debtor::viewTrans($trans_type, $invoice_no, _("&View This Invoice"), false, 'button'), 0, 1);
    Display::note(Reporting::print_doc_link($invoice_no, _("&Print This Invoice"), true, ST_SALESINVOICE));
    Reporting::email_link($invoice_no, _("Email This Invoice"), true, ST_SALESINVOICE, 'EmailLink', null, $emails, 1);
    Display::link_params("/sales/payment", _("Apply a customer payment"), '', true, 'class="button"');
    Display::note(GL_UI::view($trans_type, $invoice_no, _("View the GL &Journal Entries for this Invoice"), false, 'button'), 1);
    Display::link_params("/sales/search/deliveries", _("Select Another &Delivery For Invoicing"), "OutstandingOnly=1", true, 'class="button"');
    Page::footer_exit();
  } elseif (isset($_GET[UPDATED_ID])) {
    $order      = new Sales_Order(ST_SALESINVOICE, $_GET[UPDATED_ID]);
    $customer   = new Debtor($order->debtor_id);
    $emails     = $customer->getEmailAddresses();
    $invoice_no = $_GET[UPDATED_ID];
    Event::success(sprintf(_('Sales Invoice # %d has been updated.'), $invoice_no));
    Display::note(GL_UI::viewTrans(ST_SALESINVOICE, $invoice_no, _("&View This Invoice")));
    echo '<br>';
    Display::note(Reporting::print_doc_link($invoice_no, _("&Print This Invoice"), true, ST_SALESINVOICE));
    Reporting::email_link($invoice_no, _("Email This Invoice"), true, ST_SALESINVOICE, 'EmailLink', null, $emails, 1);
    Display::link_params("/sales/search/transactions", _("Select A Different &Invoice to Modify"));
    Page::footer_exit();
  } elseif (isset($_GET['RemoveDN'])) {
    for ($line_no = 0; $line_no < count($order->line_items); $line_no++) {
      $line = $order->line_items[$line_no];
      if ($line->src_no == $_GET['RemoveDN']) {
        $line->quantity       = $line->qty_done;
        $line->qty_dispatched = 0;
      }
    }
    unset($line);
    // Remove also src_doc delivery note
    $sources = $order->src_docs;
    unset($sources[$_GET['RemoveDN']]);
  }
  if ((isset($_GET['DeliveryNumber']) && ($_GET['DeliveryNumber'] > 0)) || isset($_GET[Orders::BATCH_INVOICE])) {
    if (isset($_GET[Orders::BATCH_INVOICE])) {
      $src = $_SESSION['DeliveryBatch'];
      unset($_SESSION['DeliveryBatch']);
    } else {
      $src = array($_GET['DeliveryNumber']);
    }
    /* read in all the selected deliveries into the Items order */
    $order = new Sales_Order(ST_CUSTDELIVERY, $src, true);
    if ($order->count_items() == 0) {
      Display::link_params("/sales/search/deliveries", _("Select a different delivery to invoice"), "OutstandingOnly=1");
      die("<br><span class='bold'>" . _("There are no delivered items with a quantity left to invoice. There is nothing left to invoice.") . "</span>");
    }
    $order->trans_type = ST_SALESINVOICE;
    $order->src_docs   = $order->trans_no;
    $order->trans_no   = 0;
    $order->reference  = Ref::get_next(ST_SALESINVOICE);
    $order->due_date   = Sales_Order::get_invoice_duedate($order->debtor_id, $order->document_date);
    Sales_Invoice::copyToPost($order);
  } elseif (isset($_GET[Orders::MODIFY_INVOICE]) && $_GET[Orders::MODIFY_INVOICE] > 0) {
    if (Debtor_Trans::get_parent(ST_SALESINVOICE, $_GET[Orders::MODIFY_INVOICE]) == 0) { // 1.xx compatibility hack
      echo"<div class='center'><br><span class='bold'>" . _("There are no delivery notes for this invoice.") . "</span></div>";
      Page::footer_exit();
    }
    $order = new Sales_Order(ST_SALESINVOICE, $_GET[Orders::MODIFY_INVOICE]);
    $order->start();
    Sales_Invoice::copyToPost($order);
    if ($order->count_items() == 0) {
      echo "<div class='center'><br><span class='bold'>" . _(
        "All quantities on this invoice have been credited. There is
            nothing to modify on this invoice"
      ) . "</span></div>";
    }
  } elseif (isset($_GET[Orders::VIEW_INVOICE]) && $_GET[Orders::VIEW_INVOICE] > 0) {
    $order = new Sales_Order(ST_SALESINVOICE, $_GET[Orders::VIEW_INVOICE]);
    $order->start();
    Sales_Invoice::copyToPost($order);
  } elseif (!$order && !isset($_GET['order_id'])) {
    /* This page can only be called with a delivery for invoicing or invoice no for edit */
    Event::error(_("This page can only be opened after delivery selection. Please select delivery to invoicing first."));
    Display::link_params("/sales/search/deliveries", _("Select Delivery to Invoice"));
    Page::end();
    exit;
  } elseif ($order && !Sales_Invoice::check_quantities($order)) {
    Event::error(_("Selected quantity cannot be less than quantity credited nor more than quantity not invoiced yet."));
  }
  if (isset($_POST['Update'])) {
    Ajax::_activate('Items');
  }
  if (isset($_POST['_InvoiceDate_changed'])) {
    $_POST['due_date'] = Sales_Order::get_invoice_duedate($order->debtor_id, $_POST['InvoiceDate']);
    Ajax::_activate('due_date');
  }
  if (isset($_POST['process_invoice']) && Sales_Invoice::check_data($order)) {
    $newinvoice = $order->trans_no == 0;
    Sales_Invoice::copyFromPost($order);
    if ($newinvoice) {
      Dates::_newDocDate($order->document_date);
    }
    $invoice_no = $order->write();
    $order->finish();
    if ($newinvoice) {
      Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$invoice_no");
    } else {
      //	Display::meta_forward($_SERVER['DOCUMENT_URI'], "UpdatedID=$invoice_no");
    }
  }
  // find delivery spans for batch invoice display
  $dspans  = [];
  $lastdn  = '';
  $spanlen = 1;
  for ($line_no = 0; $line_no < count($order->line_items); $line_no++) {
    $line = $order->line_items[$line_no];
    if ($line->quantity == $line->qty_done) {
      continue;
    }
    if ($line->src_no == $lastdn) {
      $spanlen++;
    } else {
      if ($lastdn != '') {
        $dspans[] = $spanlen;
        $spanlen  = 1;
      }
    }
    $lastdn = $line->src_no;
  }
  $dspans[]         = $spanlen;
  $is_batch_invoice = count($order->src_docs) > 1;
  $is_edition       = $order->trans_type == ST_SALESINVOICE && $order->trans_no != 0;
  Forms::start();
  Forms::hidden('order_id');
  Table::start('standard width90 pad5');
  echo '<tr>';
  Cell::labelled(_("Customer"), $order->customer_name, "class='tablerowhead'");
  Cell::labelled(_("Branch"), Sales_Branch::get_name($order->Branch), "class='tablerowhead'");
  Cell::labelled(_("Currency"), $order->customer_currency, "class='tablerowhead'");
  echo '</tr>';
  echo '<tr>';
  if ($order->trans_no == 0) {
    Forms::refCells(_("Reference"), 'ref', '', null, "class='tablerowhead'");
  } else {
    Cell::labelled(_("Reference"), $order->reference, "class='tablerowhead'");
  }
  Cell::labelled(_("Delivery Notes:"), Debtor::viewTrans(ST_CUSTDELIVERY, array_keys($order->src_docs)), "class='tablerowhead'");
  Cell::labelled(_("Sales Type"), $order->sales_type_name, "class='tablerowhead'");
  echo '</tr>';
  echo '<tr>';
  if (!isset($_POST['ship_via'])) {
    $_POST['ship_via'] = $order->ship_via;
  }
  Cell::label(_("Shipping Company"), "class='label'");
  if (!$order->view_only || !isset($order->ship_via)) {
    Sales_UI::shippers_cells(null, 'ship_via', $_POST['ship_via']);
  } else {
    Cell::label($order->ship_via);
  }
  if (!isset($_POST['InvoiceDate']) || !Dates::_isDate($_POST['InvoiceDate'])) {
    $_POST['InvoiceDate'] = Dates::_newDocDate();
    if (!Dates::_isDateInFiscalYear($_POST['InvoiceDate'])) {
      $_POST['InvoiceDate'] = Dates::_endFiscalYear();
    }
  }
  if (!$order->view_only) {
    Forms::dateCells(_("Date"), 'InvoiceDate', '', $order->trans_no == 0, 0, 0, 0, "class='tablerowhead'", true);
  } else {
    Cell::labelled(_('Invoice Date:'), $_POST['InvoiceDate']);
  }
  if (!isset($_POST['due_date']) || !Dates::_isDate($_POST['due_date'])) {
    $_POST['due_date'] = Sales_Order::get_invoice_duedate($order->debtor_id, $_POST['InvoiceDate']);
  }
  if (!$order->view_only) {
    Forms::dateCells(_("Due Date"), 'due_date', '', null, 0, 0, 0, "class='tablerowhead'");
  } else {
    Cell::labelled(_('Due Date'), $_POST['due_date']);
  }
  echo '</tr>';
  Table::end();
  $row = Sales_Order::get_customer($order->debtor_id);
  if ($row['dissallow_invoices'] == 1) {
    Event::error(_("The selected customer account is currently on hold. Please contact the credit control personnel to discuss."));
    Forms::end();
    Page::end();
    exit();
  }
  Display::heading(_("Invoice Items"));
  Ajax::_start_div('Items');
  Table::start('padded grid width90');
  $th = array(
    _("Item Code"),
    _("Item Description"),
    _("Delivered"),
    _("Units"),
    _("Invoiced"),
    _("This Invoice"),
    _("Price"),
    _("Tax Type"),
    _("Discount"),
    _("Total")
  );
  if ($is_batch_invoice) {
    $th[] = _("DN");
    $th[] = "";
  }
  if ($is_edition) {
    $th[4] = _("Credited");
  }
  Table::header($th);
  $k           = 0;
  $has_marked  = false;
  $show_qoh    = true;
  $dn_line_cnt = 0;
  foreach ($order->line_items as $line_no => $line) {
    if (!$order->view_only && $line->quantity == $line->qty_done) {
      continue; // this line was fully invoiced
    }
    Item_UI::status_cell($line->stock_id);
    if (!$order->view_only) {
      Forms::textareaCells(null, 'Line' . $line_no . 'Desc', $line->description, 30, 3);
    } else {
      Cell::label($line->description);
    }
    $dec = Item::qty_dec($line->stock_id);
    Cell::qty($line->quantity, false, $dec);
    Cell::label($line->units);
    Cell::qty($line->qty_done, false, $dec);
    if ($is_batch_invoice) {
      // for batch invoices we can only remove whole deliveries
      echo '<td class="alignright nowrap">';
      Forms::hidden('Line' . $line_no, $line->qty_dispatched);
      echo Num::_format($line->qty_dispatched, $dec) . '</td>';
    } elseif ($order->view_only) {
      Forms::hidden('viewing');
      Cell::qty($line->quantity, false, $dec);
    } else {
      Forms::qtyCellsSmall(null, 'Line' . $line_no, Item::qty_format($line->qty_dispatched, $line->stock_id, $dec), null, null, $dec);
    }
    $display_discount_percent = Num::_percentFormat($line->discount_percent * 100) . " %";
    $line_total               = ($line->qty_dispatched * $line->price * (1 - $line->discount_percent));
    Cell::amount($line->price);
    Cell::label($line->tax_type_name);
    Cell::label($display_discount_percent, ' class="alignright nowrap"');
    Cell::amount($line_total);
    if ($is_batch_invoice) {
      if ($dn_line_cnt == 0) {
        $dn_line_cnt = $dspans[0];
        $dspans      = array_slice($dspans, 1);
        Cell::label($line->src_no, "rowspan=$dn_line_cnt class=oddrow");
        Cell::label("<a href='" . $_SERVER['DOCUMENT_URI'] . "?RemoveDN=" . $line->src_no . "'>" . _("Remove") . "</a>", "rowspan=$dn_line_cnt class=oddrow");
      }
      $dn_line_cnt--;
    }
    echo '</tr>';
  }
  /* Don't re-calculate freight if some of the order has already been delivered -
depending on the business logic required this condition may not be required.
It seems unfair to charge the customer twice for freight if the order
was not fully delivered the first time ?? */
  if (!isset($_POST['ChargeFreightCost']) || $_POST['ChargeFreightCost'] == "") {
    if ($order->any_already_delivered() == 1) {
      $_POST['ChargeFreightCost'] = Num::_priceFormat(0);
    } else {
      $_POST['ChargeFreightCost'] = Num::_priceFormat($order->freight_cost);
    }
    if (!Validation::post_num('ChargeFreightCost')) {
      $_POST['ChargeFreightCost'] = Num::_priceFormat(0);
    }
  }
  $accumulate_shipping = DB_Company::_get_pref('accumulate_shipping');
  if ($is_batch_invoice && $accumulate_shipping) {
    Sales_Invoice::set_delivery_shipping_sum(array_keys($order->src_docs));
  }
  $colspan = 9;
  Table::foot();
  echo '<tr>';
  Cell::label(_("Shipping Cost"), "colspan=$colspan class='alignright bold'");
  if (!$order->view_only) {
    Forms::amountCellsSmall(null, 'ChargeFreightCost', null);
  } else {
    Cell::amount($order->freight_cost);
  }
  if ($is_batch_invoice) {
    Cell::label('', 'colspan=2');
  }
  echo '</tr>';
  $inv_items_total   = $order->get_items_total_dispatch();
  $display_sub_total = Num::_priceFormat($inv_items_total + Validation::input_num('ChargeFreightCost'));
  Table::label(
    _("Sub-total"),
    $display_sub_total,
    "colspan=$colspan class='alignright bold'",
    "class='alignright'",
    ($is_batch_invoice ? 2 : 0)
  );
  $taxes         = $order->get_taxes(Validation::input_num('ChargeFreightCost'));
  $tax_total     = Tax::edit_items($taxes, $colspan, $order->tax_included, $is_batch_invoice ? 2 : 0);
  $display_total = Num::_priceFormat(($inv_items_total + Validation::input_num('ChargeFreightCost') + $tax_total));
  Table::label(
    _("Invoice Total"),
    $display_total,
    "colspan=$colspan class='alignright bold'",
    "class='alignright'",
    ($is_batch_invoice ? 2 : 0)
  );
  Table::footEnd();
  Table::end(1);
  Ajax::_end_div();
  Table::start('standard');
  Forms::textareaRow(_("Memo"), 'Comments', null, 50, 4);
  Table::end(1);
  Table::start('center red bold');
  if (!$order->view_only) {
    Cell::label(
      _(
        "DON'T PRESS THE PROCESS TAX INVOICE BUTTON UNLESS YOU ARE 100% CERTAIN THAT YOU WON'T NEED TO EDIT ANYTHING IN THE
    FUTURE ON THIS
    INVOICE"
      )
    );
  }
  Table::end();
  if (!$order->view_only) {
    Forms::submitCenterBegin('Update', _("Update"), _('Refresh document page'), true);
    Forms::submitCenterEnd('process_invoice', _("Process Invoice"), _('Check entered data and save document'), 'default');
    Table::start('center red bold');
    Cell::label(_("DON'T FUCK THIS UP, YOU WON'T BE ABLE TO EDIT ANYTHING AFTER THIS. DON'T MAKE YOURSELF FEEL AND LOOK LIKE A DICK!"), 'center');
  }
  Table::end();
  Forms::end();
  Page::end(false);
