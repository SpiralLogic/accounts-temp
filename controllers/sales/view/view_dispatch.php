<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  use ADV\Core\JS;
  use ADV\Core\Input\Input;
  use ADV\App\Item\Item;
  use ADV\App\User;
  use ADV\App\Dates;
  use ADV\App\Debtor\Debtor;
  use ADV\Core\DB\DB;
  use ADV\Core\Cell;
  use ADV\App\Display;
  use ADV\Core\Table;

  JS::_openWindow(950, 600);
  Page::start(_($help_context = "View Sales Dispatch"), SA_SALESTRANSVIEW, true);
  if (isset($_GET["trans_no"])) {
    $trans_id = $_GET["trans_no"];
  } elseif (isset($_POST["trans_no"])) {
    $trans_id = $_POST["trans_no"];
  }
  // 3 different queries to get the information - what a JOKE !!!!
  $myrow       = Debtor_Trans::get($trans_id, ST_CUSTDELIVERY);
  $branch      = Sales_Branch::get($myrow["branch_id"]);
  $sales_order = Sales_Order::get_header($myrow["order_"], ST_SALESORDER);
  Table::start('standard width90');
  echo "<tr class='tablerowhead top'><th colspan=6>";
  Display::heading(sprintf(_("DISPATCH NOTE #%d"), $trans_id));
  echo "</td></tr>";
  echo "<tr class='top'><td colspan=3>";
  Table::start('padded width100');
  Table::label(_("Charge To"), $myrow["DebtorName"] . "<br>" . nl2br($myrow["address"]), "class='label' nowrap", "colspan=5");
  echo '<tr>';
  Cell::labelled(_("Charge Branch"), $branch["br_name"] . "<br>" . nl2br($branch["br_address"]), "class='label' nowrap", "colspan=2");
  Cell::labelled(_("Delivered To"), $sales_order["deliver_to"] . "<br>" . nl2br($sales_order["delivery_address"]), "class='label' nowrap", "colspan=2");
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("Reference"), $myrow["reference"], "class='label'");
  Cell::labelled(_("Currency"), $sales_order["curr_code"], "class='label'");
  Cell::labelled(_("Our Order No"), Debtor::viewTrans(ST_SALESORDER, $sales_order["order_no"]), "class='label'");
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("PO#"), $sales_order["customer_ref"], "class='label'");
  Cell::labelled(_("Shipping Company"), $myrow["shipper_name"], "class='label'");
  Cell::labelled(_("Sales Type"), $myrow["sales_type"], "class='label'");
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("Dispatch Date"), Dates::_sqlToDate($myrow["tran_date"]), "class='label'", ' class="nowrap"');
  Cell::labelled(_("Due Date"), Dates::_sqlToDate($myrow["due_date"]), "class='label'", ' class="nowrap"');
  Cell::labelled(_("Deliveries"), Debtor::viewTrans(ST_CUSTDELIVERY, Debtor_Trans::get_parent(ST_SALESINVOICE, $trans_id)), "class='label'");
  echo '</tr>';
  DB_Comments::display_row(ST_CUSTDELIVERY, $trans_id);
  Table::end();
  echo "</td></tr>";
  Table::end(1); // outer table
  $result = Debtor_TransDetail::get(ST_CUSTDELIVERY, $trans_id);
  Table::start('padded grid width95');
  if (DB::_numRows($result) > 0) {
    $th = array(
      _("Item Code"),
      _("Item Description"),
      _("Quantity"),
      _("Unit"),
      _("Price"),
      _("Discount %"),
      _("Total")
    );
    Table::header($th);
    $k         = 0; //row colour counter
    $sub_total = 0;
    while ($myrow2 = DB::_fetch($result)) {
      if ($myrow2['quantity'] == 0) {
        continue;
      }
      $value = Num::_round(((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]), User::_price_dec());
      $sub_total += $value;
      if ($myrow2["discount_percent"] == 0) {
        $display_discount = "";
      } else {
        $display_discount = Num::_percentFormat($myrow2["discount_percent"] * 100) . "%";
      }
      Cell::label($myrow2["stock_id"]);
      Cell::label($myrow2["StockDescription"]);
      Cell::qty($myrow2["quantity"], false, Item::qty_dec($myrow2["stock_id"]));
      Cell::label($myrow2["units"], "class='alignright'");
      Cell::amount($myrow2["unit_price"]);
      Cell::label($display_discount, ' class="alignright nowrap"');
      Cell::amount($value);
      echo '</tr>';
    } //end while there are line items to print out
  } else {
    Event::warning(_("There are no line items on this dispatch."), 1, 2);
  }
  $display_sub_tot = Num::_priceFormat($sub_total);
  $display_freight = Num::_priceFormat($myrow["ov_freight"]);
  /*Print out the delivery note text entered */
  Table::label(_("Sub-total"), $display_sub_tot, "colspan=6 class='alignright'", " class='alignright nowrap width15'");
  Table::label(_("Shipping"), $display_freight, "colspan=6 class='alignright'", ' class="alignright nowrap"');
  $tax_items = GL_Trans::get_tax_details(ST_CUSTDELIVERY, $trans_id);
  Debtor_Trans::display_tax_details($tax_items, 6);
  $display_total = Num::_priceFormat($myrow["ov_freight"] + $myrow["ov_amount"] + $myrow["ov_freight_tax"] + $myrow["ov_gst"]);
  Table::label(_("TOTAL VALUE"), $display_total, "colspan=6 class='alignright'", ' class="alignright nowrap"');
  Table::end(1);
  Voiding::is_voided(ST_CUSTDELIVERY, $trans_id, _("This dispatch has been voided."));
  if (Input::_get('frame')) {
    return;
  }
  Display::submenu_print(_("&Print This Delivery Note"), ST_CUSTDELIVERY, $_GET['trans_no'], 'prtopt');
  Page::end();
