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
  Page::start(_($help_context = "Inventory Item Cost Update"), SA_STANDARDCOST);
  Validation::check(Validation::COST_ITEMS, _("There are no costable inventory items defined in the system (Purchased or manufactured items)."), STOCK_SERVICE);
  if (isset($_GET['stock_id'])) {
    $_POST['stock_id'] = $_GET['stock_id'];
  }
  if (isset($_POST['UpdateData'])) {
    $old_cost      = $_POST['OldMaterialCost'] + $_POST['OldLabourCost'] + $_POST['OldOverheadCost'];
    $new_cost      = Validation::input_num('material_cost') + Validation::input_num('labour_cost') + Validation::input_num('overhead_cost');
    $should_update = true;
    if (!Validation::post_num('material_cost') || !Validation::post_num('labour_cost') || !Validation::post_num('overhead_cost')
    ) {
      Event::error(_("The entered cost is not numeric."));
      JS::_setFocus('material_cost');
      $should_update = false;
    } elseif ($old_cost == $new_cost) {
      Event::error(_("The new cost is the same as the old cost. Cost was not updated."));
      $should_update = false;
    }
    if ($should_update) {
      $update_no = Item_Price::update_cost(
        $_POST['stock_id'],
        Validation::input_num('material_cost'),
        Validation::input_num('labour_cost'),
        Validation::input_num('overhead_cost'),
        $old_cost
      );
      Event::success(_("Cost has been updated."));
      if ($update_no > 0) {
        Display::note(GL_UI::view(ST_COSTUPDATE, $update_no, _("View the GL Journal Entries for this Cost Update")), 0, 1);
      }
    }
  }
  if (Forms::isListUpdated('stock_id')) {
    Ajax::_activate('cost_table');
  }
  Forms::start();
  if (!Input::_post('stock_id')) {
    Session::_setGlobal('stock_id', $_POST['stock_id']);
  }
  echo "<div class='center'>" . _("Item:") . "&nbsp;";
  echo Item_UI::costable('stock_id', $_POST['stock_id'], false, true);
  echo "</div><hr>";
  Session::_setGlobal('stock_id', $_POST['stock_id']);
  $sql
          = "SELECT description, units, material_cost, labour_cost,
    overhead_cost, mb_flag
    FROM stock_master
    WHERE stock_id=" . DB::_escape($_POST['stock_id']) . "
    GROUP BY description, units, material_cost, labour_cost, overhead_cost, mb_flag";
  $result = DB::_query($sql, "The cost details for the item could not be retrieved");
  $myrow  = DB::_fetch($result);
  Ajax::_start_div('cost_table');
  Forms::hidden("OldMaterialCost", $myrow["material_cost"]);
  Forms::hidden("OldLabourCost", $myrow["labour_cost"]);
  Forms::hidden("OldOverheadCost", $myrow["overhead_cost"]);
  Table::start('standard');
  $dec1                   = $dec2 = $dec3 = 0;
  $_POST['material_cost'] = Num::_priceDecimal($myrow["material_cost"], $dec1);
  $_POST['labour_cost']   = Num::_priceDecimal($myrow["labour_cost"], $dec2);
  $_POST['overhead_cost'] = Num::_priceDecimal($myrow["overhead_cost"], $dec3);
  Forms::AmountRow(_("Standard Material Cost Per Unit"), "material_cost", null, "class='tablerowhead'", null, $dec1);
  if ($myrow["mb_flag"] == STOCK_MANUFACTURE) {
    Forms::AmountRow(_("Standard Labour Cost Per Unit"), "labour_cost", null, "class='tablerowhead'", null, $dec2);
    Forms::AmountRow(_("Standard Overhead Cost Per Unit"), "overhead_cost", null, "class='tablerowhead'", null, $dec3);
  } else {
    Forms::hidden("labour_cost", 0);
    Forms::hidden("overhead_cost", 0);
  }
  Table::end(1);
  Ajax::_end_div();
  Forms::submitCenter('UpdateData', _("Update"), true, false, 'default');
  Forms::end();
  Page::end();

