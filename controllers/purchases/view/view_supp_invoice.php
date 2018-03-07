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
  Page::start(_($help_context = "View Supplier Invoice"), SA_SUPPTRANSVIEW, true);
  if (isset($_GET["trans_no"])) {
    $trans_no = $_GET["trans_no"];
  } elseif (isset($_POST["trans_no"])) {
    $trans_no = $_POST["trans_no"];
  }
  $creditor_trans             = new Creditor_Trans();
  $creditor_trans->is_invoice = true;
  Purch_Invoice::get($trans_no, ST_SUPPINVOICE, $creditor_trans);
  $supplier_curr_code = Bank_Currency::for_creditor($creditor_trans->creditor_id);
  Display::heading(_("SUPPLIER INVOICE") . " # " . $trans_no);
  echo "<br>";
  Table::start('padded width95');
  echo '<tr>';
  Cell::labelled(_("Supplier"), $creditor_trans->supplier_name, "class='tablerowhead'");
  Cell::labelled(_("Reference"), $creditor_trans->reference, "class='tablerowhead'");
  Cell::labelled(_("Supplier's Reference"), $creditor_trans->supplier_reference, "class='tablerowhead'");
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("Invoice Date"), $creditor_trans->tran_date, "class='tablerowhead'");
  Cell::labelled(_("Due Date"), $creditor_trans->due_date, "class='tablerowhead'");
  if (!Bank_Currency::is_company($supplier_curr_code)) {
    Cell::labelled(_("Currency"), $supplier_curr_code, "class='tablerowhead'");
  }
  echo '</tr>';
  DB_Comments::display_row(ST_SUPPINVOICE, $trans_no);
  Table::end(1);
  $total_gl        = Purch_GLItem::display_items($creditor_trans, 2);
  $total_grn       = Purch_GRN::display_items($creditor_trans, 2);
  $display_sub_tot = Num::_format($total_gl + $total_grn, User::_price_dec());
  Table::start('padded width95');
  Table::label(_("Sub Total"), $display_sub_tot, "class='alignright'", "class='alignright nowrap width15'");
  $tax_items     = GL_Trans::get_tax_details(ST_SUPPINVOICE, $trans_no);
  $tax_total     = Creditor_Trans::trans_tax_details($tax_items, 1, $creditor_trans->ov_gst);
  $display_total = Num::_format($creditor_trans->ov_amount + $creditor_trans->ov_gst, User::_price_dec());
  Table::label(_("TOTAL INVOICE"), $display_total, "colspan=1 class='alignright'", ' class="alignright nowrap"');
  Table::end(1);
  Voiding::is_voided(ST_SUPPINVOICE, $trans_no, _("This invoice has been voided."));
  if (Input::_get('frame')) {
    return;
  }
  Page::end(true);

