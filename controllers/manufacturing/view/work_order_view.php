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
  Page::start(_($help_context = "View Work Order"), SA_MANUFTRANSVIEW, true);
  $woid = 0;
  if ($_GET['trans_no'] != "") {
    $woid = $_GET['trans_no'];
  }
  Display::heading(SysTypes::$names[ST_WORKORDER] . " # " . $woid);
  echo "<br>";
  $myrow = WO::get($woid);
  if ($myrow["type"] == WO_ADVANCED) {
    WO_Cost::display($woid, true);
  } else {
    WO_Quick::display($woid, true);
  }
  echo "<div class='center'>";
  // display the WO requirements
  echo "<br>";
  if ($myrow["released"] == false) {
    Display::heading(_("BOM for item:") . " " . $myrow["StockItemName"]);
    WO::display_bom($myrow["stock_id"]);
  } else {
    Display::heading(_("Work Order Requirements"));
    WO_Requirements::display($woid, $myrow["units_reqd"]);
    if ($myrow["type"] == WO_ADVANCED) {
      echo "<br><table cellspacing=7><tr class='top'><td>";
      Display::heading(_("Issues"));
      WO_Issue::display($woid);
      echo "</td><td>";
      Display::heading(_("Productions"));
      WO_Produce::display($woid);
      echo "</td><td>";
      Display::heading(_("Additional Costs"));
      WO_Cost::display_payments($woid);
      echo "</td></tr></table>";
    } else {
      echo "<br><table cellspacing=7><tr class='top'><td>";
      Display::heading(_("Additional Costs"));
      WO_Cost::display_payments($woid);
      echo "</td></tr></table>";
    }
  }
  echo "<br></div>";
  Voiding::is_voided(ST_WORKORDER, $woid, _("This work order has been voided."));
  Page::end(true);


