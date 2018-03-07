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
  Page::start(_($help_context = "Inventory Item Movement"), SA_ITEMSTRANSVIEW);
  Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
  if (Input::_post('ShowMoves')) {
    Ajax::_activate('doc_tbl');
  }
  if (isset($_GET['stock_id'])) {
    $_POST['stock_id'] = $_GET['stock_id'];
  }
  Forms::start();
  if (!Input::_post('stock_id')) {
    Session::_setGlobal('stock_id', $_POST['stock_id']);
  }
  Table::start('noborder');
  Item::cells(_("Select an item:"), 'stock_id', $_POST['stock_id'], false, true, false);
  Inv_Location::cells(_("From Location:"), 'StockLocation', null);
  Forms::dateCells(_("From:"), 'AfterDate', '', null, -30);
  Forms::dateCells(_("To:"), 'BeforeDate');
  Forms::submitCells('ShowMoves', _("Show Movements"), '', _('Refresh Inquiry'), 'default');
  Table::end();
  Forms::end();
  Session::_setGlobal('stock_id', $_POST['stock_id']);
  $before_date = Dates::_dateToSql($_POST['BeforeDate']);
  $after_date  = Dates::_dateToSql($_POST['AfterDate']);
  $sql
               = "SELECT type, trans_no, tran_date, person_id, qty, reference
    FROM stock_moves
    WHERE loc_code=" . DB::_escape($_POST['StockLocation']) . "
    AND tran_date >= '" . $after_date . "'
    AND tran_date <= '" . $before_date . "'
    AND stock_id = " . DB::_escape($_POST['stock_id']) . " ORDER BY tran_date,trans_id";
  $result      = DB::_query($sql, "could not query stock moves");
  Ajax::_start_div('doc_tbl');
  Table::start('padded grid');
  $th = array(
    _("Type"),
    _("#"),
    _("Reference"),
    _("Date"),
    _("Detail"),
    _("Quantity In"),
    _("Quantity Out"),
    _("Quantity On Hand")
  );
  Table::header($th);
  $sql            = "SELECT SUM(qty) FROM stock_moves WHERE stock_id=" . DB::_escape($_POST['stock_id']) . "
    AND loc_code=" . DB::_escape($_POST['StockLocation']) . "
    AND tran_date < '" . $after_date . "'";
  $before_qty     = DB::_query($sql, "The starting quantity on hand could not be calculated");
  $before_qty_row = DB::_fetchRow($before_qty);
  $after_qty      = $before_qty = $before_qty_row[0];
  if (!isset($before_qty_row[0])) {
    $after_qty = $before_qty = 0;
  }
  echo "<tr class='inquirybg'>";
  Cell::label("<span class='bold'>" . _("Quantity on hand before") . " " . $_POST['AfterDate'] . "</span>", "class=center colspan=5");
  Cell::label("&nbsp;", "colspan=2");
  $dec = Item::qty_dec($_POST['stock_id']);
  Cell::qty($before_qty, false, $dec);
  echo '</tr>';
  $j         = 1;
  $k         = 0; //row colour counter
  $total_in  = 0;
  $total_out = 0;
  while ($myrow = DB::_fetch($result)) {
    $trandate  = Dates::_sqlToDate($myrow["tran_date"]);
    $type_name = SysTypes::$names[$myrow["type"]];
    if ($myrow["qty"] > 0) {
      $quantity_formatted = Num::_format($myrow["qty"], $dec);
      $total_in += $myrow["qty"];
    } else {
      $quantity_formatted = Num::_format(-$myrow["qty"], $dec);
      $total_out += -$myrow["qty"];
    }
    $after_qty += $myrow["qty"];
    Cell::label($type_name);
    Cell::label(GL_UI::viewTrans($myrow["type"], $myrow["trans_no"]));
    Cell::label(GL_UI::viewTrans($myrow["type"], $myrow["trans_no"], $myrow["reference"]));
    Cell::label($trandate);
    $person     = $myrow["person_id"];
    $gl_posting = "";
    if (($myrow["type"] == ST_CUSTDELIVERY) || ($myrow["type"] == ST_CUSTCREDIT)) {
      $cust_row = Debtor_Trans::get_details($myrow["type"], $myrow["trans_no"]);
      if (strlen($cust_row['name']) > 0) {
        $person = $cust_row['name'] . " (" . $cust_row['br_name'] . ")";
      }
    } elseif ($myrow["type"] == ST_SUPPRECEIVE || $myrow['type'] == ST_SUPPCREDIT) {
      // get the supplier name
      $sql             = "SELECT name FROM suppliers WHERE creditor_id = '" . $myrow["person_id"] . "'";
      $supplier_result = DB::_query($sql, "check failed");
      $supplier_row    = DB::_fetch($supplier_result);
      if (strlen($supplier_row['name']) > 0) {
        $person = $supplier_row['name'];
      }
    } elseif ($myrow["type"] == ST_LOCTRANSFER || $myrow["type"] == ST_INVADJUST) {
      // get the adjustment type
      $movement_type = Inv_Movement::get_type($myrow["person_id"]);
      $person        = $movement_type["name"];
    } elseif ($myrow["type"] == ST_WORKORDER || $myrow["type"] == ST_MANUISSUE || $myrow["type"] == ST_MANURECEIVE
    ) {
      $person = "";
    }
    Cell::label($person);
    Cell::label((($myrow["qty"] >= 0) ? $quantity_formatted : ""), ' class="alignright nowrap"');
    Cell::label((($myrow["qty"] < 0) ? $quantity_formatted : ""), ' class="alignright nowrap"');
    Cell::qty($after_qty, false, $dec);
    echo '</tr>';
    $j++;
    If ($j == 12) {
      $j = 1;
      Table::header($th);
    }
    //end of page full new headings if
  }
  //end of while loop
  echo "<tr class='inquirybg'>";
  Cell::label("<span class='bold'>" . _("Quantity on hand after") . " " . $_POST['BeforeDate'] . "</span>", "class=center colspan=5");
  Cell::qty($total_in, false, $dec);
  Cell::qty($total_out, false, $dec);
  Cell::qty($after_qty, false, $dec);
  echo '</tr>';
  Table::end(1);
  Ajax::_end_div();
  Page::end();

