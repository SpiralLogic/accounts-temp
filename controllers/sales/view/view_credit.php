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
  use ADV\Core\DB\DB;
  use ADV\Core\Cell;
  use ADV\App\Display;
  use ADV\Core\Table;

  JS::_openWindow(950, 500);
  Page::start(_($help_context = "View Credit Note"), SA_SALESTRANSVIEW, true);
  if (isset($_GET["trans_no"])) {
    $trans_id = $_GET["trans_no"];
  } elseif (isset($_POST["trans_no"])) {
    $trans_id = $_POST["trans_no"];
  }
  $myrow  = Debtor_Trans::get($trans_id, ST_CUSTCREDIT);
  $branch = Sales_Branch::get($myrow["branch_id"]);
  Display::heading("<font color=red>" . sprintf(_("CREDIT NOTE #%d"), $trans_id) . "</font>");
  echo "<br>";
  Table::start('standard width95');
  echo "<tr class='top'><td>"; // outer table
  /*Now the customer charged to details in a sub table*/
  Table::start('padded width100');
  $th = array(_("Customer"));
  Table::header($th);
  Table::label(null, $myrow["DebtorName"] . "<br>" . nl2br($myrow["address"]), ' class="nowrap"');
  Table::end();
  /*end of the small table showing charge to account details */
  echo "</td><td>"; // outer table
  Table::start('padded width100');
  $th = array(_("Branch"));
  Table::header($th);
  Table::label(null, $branch["br_name"] . "<br>" . nl2br($branch["br_address"]), ' class="nowrap"');
  Table::end();
  echo "</td><td>"; // outer table
  Table::start('padded width100');
  echo '<tr>';
  Cell::labelled(_("Ref"), $myrow["reference"], "class='tablerowhead'");
  Cell::labelled(_("Date"), Dates::_sqlToDate($myrow["tran_date"]), "class='tablerowhead'");
  Cell::labelled(_("Currency"), $myrow["curr_code"], "class='tablerowhead'");
  echo '</tr>';
  echo '<tr>';
  Cell::labelled(_("Sales Type"), $myrow["sales_type"], "class='tablerowhead'");
  Cell::labelled(_("Shipping Company"), $myrow["shipper_name"], "class='tablerowhead'");
  echo '</tr>';
  DB_Comments::display_row(ST_CUSTCREDIT, $trans_id);
  Table::end();
  echo "</td></tr>";
  Table::end(1); // outer table
  $sub_total = 0;
  $result    = Debtor_TransDetail::get(ST_CUSTCREDIT, $trans_id);
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
      if ($myrow2["quantity"] == 0) {
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
      Cell::label($display_discount, "class='alignright'");
      Cell::amount($value);
      echo '</tr>';
    } //end while there are line items to print out
  } else {
    Event::warning(_("There are no line items on this credit note."), 1, 2);
  }
  $display_sub_tot = Num::_priceFormat($sub_total);
  $display_freight = Num::_priceFormat($myrow["ov_freight"]);
  $credit_total    = $myrow["ov_freight"] + $myrow["ov_gst"] + $myrow["ov_amount"] + $myrow["ov_freight_tax"];
  $display_total   = Num::_priceFormat($credit_total);
  /*Print out the invoice text entered */
  if ($sub_total != 0) {
    Table::label(_("Sub Total"), $display_sub_tot, "colspan=6 class='alignright'", " class='nowrap alignright width15'");
  }
  Table::label(_("Shipping"), $display_freight, "colspan=6 class='alignright'", ' class="alignright nowrap"');
  $tax_items = GL_Trans::get_tax_details(ST_CUSTCREDIT, $trans_id);
  Debtor_Trans::display_tax_details($tax_items, 6);
  Table::label("<font color=red>" . _("TOTAL CREDIT") . "</font", "<span class='red'>$display_total</span>", "colspan=6 class='alignright'", ' class="alignright nowrap"');
  Table::end(1);
  $voided = Voiding::is_voided(ST_CUSTCREDIT, $trans_id, _("This credit note has been voided."));
  if (!$voided) {
    GL_Allocation::from(PT_CUSTOMER, $myrow['debtor_id'], ST_CUSTCREDIT, $trans_id, $credit_total);
  }
  if (Input::_get('frame')) {
    return;
  }
  /* end of check to see that there was an invoice record to print */
  Display::submenu_print(_("&Print This Credit Note"), ST_CUSTCREDIT, $_GET['trans_no'], 'prtopt');
  Page::end();


