<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  if (isset($_GET['stock_id'])) {
    $_POST['stock_id'] = $_GET['stock_id'];
    Page::start(_($help_context = "Inventory Item Status"), SA_ITEMSSTATVIEW, true);
  } else {
    Page::start(_($help_context = "Inventory Item Status"));
  }
  if (Input::_post('stock_id')) {
    Ajax::_activate('status_tbl');
  }
  Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
  Forms::start();
  if (!Input::_post('stock_id')) {
    Session::_setGlobal('stock_id', $_POST['stock_id']);
  }
  echo "<div class='center bold pad10 font13'> ";
  Item::cells(_("Item:"), 'stock_id', $_POST['stock_id'], false, true, false, false);
  echo "</div>";
  Session::_setGlobal('stock_id', $_POST['stock_id']);
  $mb_flag           = WO::get_mb_flag($_POST['stock_id']);
  $kitset_or_service = false;
  Ajax::_start_div('status_tbl');
  if (Input::_post('mb_flag') == STOCK_SERVICE) {
    Event::warning(_("This is a service and cannot have a stock holding, only the total quantity on outstanding sales orders is shown."), 0, 1);
    $kitset_or_service = true;
  }
  $loc_details = Inv_Location::get_details($_POST['stock_id']);
  Table::start('padded grid');
  if ($kitset_or_service == true) {
    $th = array(_("Location"), _("Demand"));
  } else {
    $th = array(
      _("Location"),
      _("Quantity On Hand"),
      _("Re-Order Level"),
      _("Demand"),
      _("Available"),
      _("On Order")
    );
  }
  Table::header($th);
  $dec = Item::qty_dec($_POST['stock_id']);
  $j   = 1;
  $k   = 0; //row colour counter
  while ($myrow = DB::_fetch($loc_details)) {
    $demand_qty = Item::get_demand($_POST['stock_id'], $myrow["loc_code"]);
    $demand_qty += WO::get_demand_asm_qty($_POST['stock_id'], $myrow["loc_code"]);
    $qoh = Item::get_qoh_on_date($_POST['stock_id'], $myrow["loc_code"]);
    if ($kitset_or_service == false) {
      $qoo = WO::get_on_porder_qty($_POST['stock_id'], $myrow["loc_code"]);
      $qoo += WO::get_on_worder_qty($_POST['stock_id'], $myrow["loc_code"]);
      Cell::label($myrow["location_name"]);
      Cell::qty($qoh, false, $dec);
      Cell::qty($myrow["reorder_level"], false, $dec);
      Cell::qty($demand_qty, false, $dec);
      Cell::qty($qoh - $demand_qty, false, $dec);
      Cell::qty($qoo, false, $dec);
      echo '</tr>';
    } else {
      /* It must be a service or kitset part */
      Cell::label($myrow["location_name"]);
      Cell::qty($demand_qty, false, $dec);
      echo '</tr>';
    }
    $j++;
    If ($j == 12) {
      $j = 1;
      Table::header($th);
    }
  }
  Table::end();
  Ajax::_end_div();
  Forms::end();
  Page::end();


