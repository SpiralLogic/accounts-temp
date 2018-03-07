<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "View Inventory Adjustment"), SA_ITEMSTRANSVIEW, true);
  if (isset($_GET["trans_no"])) {
    $trans_no = $_GET["trans_no"];
  }
  Display::heading(SysTypes::$names[ST_INVADJUST] . " #$trans_no");
  echo "<br>";
  $adjustment_items = Inv_Adjustment::get($trans_no);
  $k                = 0;
  $header_shown     = false;
  while ($adjustment = DB::_fetch($adjustment_items)) {
    if (!$header_shown) {
      $adjustment_type = Inv_Movement::get_type($adjustment['person_id']);
      Table::start('standard width90');
      echo '<tr>';
      Cell::labelled(_("At Location"), $adjustment['location_name'], "class='tablerowhead'");
      Cell::labelled(_("Reference"), $adjustment['reference'], "class='tablerowhead'", "colspan=6");
      Cell::labelled(_("Date"), Dates::_sqlToDate($adjustment['tran_date']), "class='tablerowhead'");
      Cell::labelled(_("Adjustment Type"), $adjustment_type['name'], "class='tablerowhead'");
      echo '</tr>';
      DB_Comments::display_row(ST_INVADJUST, $trans_no);
      Table::end();
      $header_shown = true;
      echo "<br>";
      Table::start('padded grid width90');
      $th = array(
        _("Item"),
        _("Description"),
        _("Quantity"),
        _("Units"),
        _("Unit Cost")
      );
      Table::header($th);
    }
    Cell::label($adjustment['stock_id']);
    Cell::label($adjustment['description']);
    Cell::qty($adjustment['qty'], false, Item::qty_dec($adjustment['stock_id']));
    Cell::label($adjustment['units']);
    Cell::amountDecimal($adjustment['standard_cost']);
    echo '</tr>';
  }
  Table::end(1);
  Voiding::is_voided(ST_INVADJUST, $trans_no, _("This adjustment has been voided."));
  Page::end(true);

