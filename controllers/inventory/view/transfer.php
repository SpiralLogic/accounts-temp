<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "View Inventory Transfer"), SA_ITEMSTRANSVIEW, true);
  if (isset($_GET["trans_no"])) {
    $trans_no = $_GET["trans_no"];
  }
  $transfer_items = Inv_Transfer::get($trans_no);
  $from_trans     = $transfer_items[0];
  $to_trans       = $transfer_items[1];
  Display::heading(SysTypes::$names[ST_LOCTRANSFER] . " #$trans_no");
  echo "<br>";
  Table::start('standard width90');
  echo '<tr>';
  Cell::labelled(_("Item"), $from_trans['stock_id'] . " - " . $from_trans['description'], "class='tablerowhead'");
  Cell::labelled(_("From Location"), $from_trans['location_name'], "class='tablerowhead'");
  Cell::labelled(_("To Location"), $to_trans['location_name'], "class='tablerowhead'");
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("Reference"), $from_trans['reference'], "class='tablerowhead'");
  $adjustment_type = Inv_Movement::get_type($from_trans['person_id']);
  Cell::labelled(_("Adjustment Type"), $adjustment_type['name'], "class='tablerowhead'");
  Cell::labelled(_("Date"), Dates::_sqlToDate($from_trans['tran_date']), "class='tablerowhead'");
  echo '</tr>';
  DB_Comments::display_row(ST_LOCTRANSFER, $trans_no);
  Table::end(1);
  echo "<br>";
  Table::start('padded grid width90');
  $th = array(_("Item"), _("Description"), _("Quantity"), _("Units"));
  Table::header($th);
  $transfer_items = Inv_Movement::get(ST_LOCTRANSFER, $trans_no);
  $k              = 0;
  while ($item = DB::_fetch($transfer_items)) {
    if ($item['loc_code'] == $to_trans['loc_code']) {
      Cell::label($item['stock_id']);
      Cell::label($item['description']);
      Cell::qty($item['qty'], false, Item::qty_dec($item['stock_id']));
      Cell::label($item['units']);
      echo '</tr>';
      ;
    }
  }
  Table::end(1);
  Voiding::is_voided(ST_LOCTRANSFER, $trans_no, _("This transfer has been voided."));
  Page::end(true);

