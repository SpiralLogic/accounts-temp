<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  $js = "";
  Page::start(_($help_context = "Balance Sheet Drilldown"), SA_GLANALYTIC);
  // Ajax updates
  if (Input::_post('Show')) {
    Ajax::_activate('balance_tbl');
  }
  if (isset($_GET["TransFromDate"])) {
    $_POST["TransFromDate"] = $_GET["TransFromDate"];
  }
  if (isset($_GET["TransToDate"])) {
    $_POST["TransToDate"] = $_GET["TransToDate"];
  }
  if (isset($_GET["AccGrp"])) {
    $_POST["AccGrp"] = $_GET["AccGrp"];
  }
  Forms::start();
  inquiry_controls();
  display_balance_sheet();
  Forms::end();
  Page::end();
  /**
   * @param $type
   * @param $typename
   * @param $from
   * @param $to
   * @param $convert
   * @param $drilldown
   *
   * @return int|mixed
   */
  function display_type($type, $typename, $from, $to, $convert, $drilldown) {
    global $levelptr, $k;
    $dimension  = $dimension2 = 0;
    $acctstotal = 0;
    $typestotal = 0;
    //Get Accounts directly under this group/type
    $result = GL_Account::getAll(null, null, $type);
    while ($account = DB::_fetch($result)) {
      $prev_balance = GL_Trans::get_balance_from_to("", $from, $account["account_code"], $dimension, $dimension2);
      $curr_balance = GL_Trans::get_from_to($from, $to, $account["account_code"], $dimension, $dimension2);
      if (!$prev_balance && !$curr_balance) {
        continue;
      }
      if ($drilldown && $levelptr == 0) {
        $url = "<a href='" . ROOT_URL . "gl/search/account?TransFromDate=" . $from . "&TransToDate=" . $to . "&account=" . $account['account_code'] . "'>" . $account['account_code'] . " " . $account['account_name'] . "</a>";
        echo "<tr class='stockmankobg'>";
        Cell::label($url);
        Cell::amount(($curr_balance + $prev_balance) * $convert);
        echo '</tr>';
      }
      $acctstotal += $curr_balance + $prev_balance;
    }
    $levelptr = 1;
    //Get Account groups/types under this group/type
    $result = GL_Type::getAll(false, false, $type);
    while ($accounttype = DB::_fetch($result)) {
      $typestotal += display_type($accounttype["id"], $accounttype["name"], $from, $to, $convert, $drilldown);
    }
    //Display Type Summary if total is != 0
    if (($acctstotal + $typestotal) != 0) {
      if ($drilldown && $type == $_POST["AccGrp"]) {
        echo "<tr class='inquirybg' style='font-weight:bold'>";
        Cell::label(_('Total') . " " . $typename);
        Cell::amount(($acctstotal + $typestotal) * $convert);
        echo '</tr>';
      }
      //START Patch#1 : Display only direct child types
      $acctype1 = GL_Type::get($type);
      $parent1  = $acctype1["parent"];
      if ($drilldown && $parent1 == $_POST["AccGrp"]
      ) //END Patch#2
        //elseif ($drilldown && $type != $_POST["AccGrp"])
      {
        $url = "<a href='" . ROOT_URL . "banking/inquiry/balance_sheet.php?TransFromDate=" . $from . "&TransToDate=" . $to . "&AccGrp=" . $type . "'>" . $typename . "</a>";
        Cell::label($url);
        Cell::amount(($acctstotal + $typestotal) * $convert);
        echo '</tr>';
      }
    }
    return ($acctstotal + $typestotal);
  }

  function inquiry_controls() {
    Table::start('noborder');
    Forms::dateCells(_("As at:"), 'TransToDate');
    Forms::submitCells('Show', _("Show"), '', '', 'default');
    Table::end();
    Forms::hidden('TransFromDate');
    Forms::hidden('AccGrp');
  }

  function display_balance_sheet() {
    $from      = Dates::_beginFiscalYear();
    $to        = $_POST['TransToDate'];
    $dim       = DB_Company::_get_pref('use_dimension');
    $dimension = $dimension2 = 0;
    $lconvert  = $econvert = 1;
    if (isset($_POST["AccGrp"]) && (strlen($_POST['AccGrp']) > 0)) {
      $drilldown = 1;
    } // Deeper Level
    else {
      $drilldown = 0;
    } // Root level
    Ajax::_start_div('balance_tbl');
    Table::start('padded grid width30');
    if (!$drilldown) //Root Level
    {
      $equityclose = $lclose = $calculateclose = 0.0;
      $parent      = -1;
      //Get classes for BS
      $classresult = GL_Class::getAll(false, 1);
      while ($class = DB::_fetch($classresult)) {
        $classclose = 0.0;
        $convert    = SysTypes::get_class_type_convert($class["ctype"]);
        $ctype      = $class["ctype"];
        $classname  = $class["class_name"];
        //Print class Name
        Table::sectionTitle($class["class_name"]);
        //Get Account groups/types under this group/type
        $typeresult = GL_Type::getAll(false, $class['cid'], -1);
        while ($accounttype = DB::_fetch($typeresult)) {
          $TypeTotal = display_type($accounttype["id"], $accounttype["name"], $from, $to, $convert, $drilldown);
          //Print Summary
          if ($TypeTotal != 0) {
            $url = "<a href='" . ROOT_URL . "banking/inquiry/balance_sheet.php?TransFromDate=" . $from . "&TransToDate=" . $to . "&AccGrp=" . $accounttype['id'] . "'>" . $accounttype['name'] . "</a>";
            Cell::label($url);
            Cell::amount($TypeTotal * $convert);
            echo '</tr>';
          }
          $classclose += $TypeTotal;
        }
        //Print class Summary
        echo "<tr class='inquirybg' style='font-weight:bold'>";
        Cell::label(_('Total') . " " . $class["class_name"]);
        Cell::amount($classclose * $convert);
        echo '</tr>';
        if ($ctype == CL_EQUITY) {
          $equityclose += $classclose;
          $econvert = $convert;
        }
        if ($ctype == CL_LIABILITIES) {
          $lclose += $classclose;
          $lconvert = $convert;
        }
        $calculateclose += $classclose;
      }
      if ($lconvert == 1) {
        $calculateclose *= -1;
      }
      //Final Report Summary
      $url = "<a href='" . ROOT_URL . "banking/inquiry/profit_loss.php?TransFromDate=" . $from . "&TransToDate=" . $to . "&Compare=0'>" . _('Calculated Return') . "</a>";
      echo "<tr class='inquirybg' style='font-weight:bold'>";
      Cell::label($url);
      Cell::amount($calculateclose);
      echo '</tr>';
      echo "<tr class='inquirybg' style='font-weight:bold'>";
      Cell::label(_('Total') . " " . _('Liabilities') . _(' and ') . _('Equities'));
      Cell::amount($lclose * $lconvert + $equityclose * $econvert + $calculateclose);
      echo '</tr>';
    } else //Drill Down
    {
      //Level Pointer : Global variable defined in order to control display of root
      global $levelptr;
      $levelptr    = 0;
      $accounttype = GL_Type::get($_POST["AccGrp"]);
      $classid     = $accounttype["class_id"];
      $class       = GL_Class::get($classid);
      $convert     = SysTypes::get_class_type_convert($class["ctype"]);
      //Print class Name
      Table::sectionTitle(GL_Type::get_name($_POST["AccGrp"]));
      $classclose = display_type($accounttype["id"], $accounttype["name"], $from, $to, $convert, $drilldown, ROOT_URL);
    }
    Table::end(1); // outer table
    Ajax::_end_div();
  }
