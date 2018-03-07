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
  Page::start(_($help_context = "View Supplier Credit Note"), SA_SUPPTRANSVIEW, true);
  if (isset($_GET["trans_no"])) {
    $trans_no = $_GET["trans_no"];
  } elseif (isset($_POST["trans_no"])) {
    $trans_no = $_POST["trans_no"];
  }
  $creditor_trans             = new Creditor_Trans();
  $creditor_trans->is_invoice = false;
  Purch_Invoice::get($trans_no, ST_SUPPCREDIT, $creditor_trans);
  Display::heading(_("SUPPLIER CREDIT NOTE") . " # " . $trans_no);
  echo "<br>";
  Table::start('standard');
  echo '<tr>';
  Cell::labelled(_("Supplier"), $creditor_trans->supplier_name, "class='tablerowhead'");
  Cell::labelled(_("Reference"), $creditor_trans->reference, "class='tablerowhead'");
  Cell::labelled(_("Supplier's Reference"), $creditor_trans->supplier_reference, "class='tablerowhead'");
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("Invoice Date"), $creditor_trans->tran_date, "class='tablerowhead'");
  Cell::labelled(_("Due Date"), $creditor_trans->due_date, "class='tablerowhead'");
  Cell::labelled(_("Currency"), Bank_Currency::for_creditor($creditor_trans->creditor_id), "class='tablerowhead'");
  echo '</tr>';
  DB_Comments::display_row(ST_SUPPCREDIT, $trans_no);
  Table::end(1);
  $total_gl        = Purch_GLItem::display_items($creditor_trans, 3);
  $total_grn       = Purch_GRN::display_items($creditor_trans, 2);
  $display_sub_tot = Num::_format($total_gl + $total_grn, User::_price_dec());
  Table::start('padded width95');
  Table::label(_("Sub Total"), $display_sub_tot, "class='alignright'", " class='nowrap alignright width17' ");
  $tax_items = GL_Trans::get_tax_details(ST_SUPPCREDIT, $trans_no);
  Creditor_Trans::trans_tax_details($tax_items, 1);
  $display_total = Num::_format(-($creditor_trans->ov_amount + $creditor_trans->ov_gst), User::_price_dec());
  Table::label(_("TOTAL CREDIT NOTE"), $display_total, "colspan=1 class='alignright'", ' class="alignright nowrap"');
  Table::end(1);
  $voided = Voiding::is_voided(ST_SUPPCREDIT, $trans_no, _("This credit note has been voided."));
  if (!$voided) {
    GL_Allocation::from(PT_SUPPLIER, $creditor_trans->creditor_id, ST_SUPPCREDIT, $trans_no, -($creditor_trans->ov_amount + $creditor_trans->ov_gst));
  }
  Page::end(true);

