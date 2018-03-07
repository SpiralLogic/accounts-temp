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
  Page::start(_($help_context = "View Work Order Issue"), SA_MANUFTRANSVIEW, true);
  if ($_GET['trans_no'] != "") {
    $wo_issue_no = $_GET['trans_no'];
  }
  /**
   * @param $issue_no
   */
  function display_wo_issue($issue_no) {
    $myrow = WO_Issue::get($issue_no);
    echo "<br>";
    Table::start('padded');
    $th = array(
      _("Issue #"),
      _("Reference"),
      _("For Work Order #"),
      _("Item"),
      _("From Location"),
      _("To Work Centre"),
      _("Date of Issue")
    );
    Table::header($th);
    echo '<tr>';
    Cell::label($myrow["issue_no"]);
    Cell::label($myrow["reference"]);
    Cell::label(GL_UI::viewTrans(ST_WORKORDER, $myrow["workorder_id"]));
    Cell::label($myrow["stock_id"] . " - " . $myrow["description"]);
    Cell::label($myrow["location_name"]);
    Cell::label($myrow["WorkCentreName"]);
    Cell::label(Dates::_sqlToDate($myrow["issue_date"]));
    echo '</tr>';
    DB_Comments::display_row(28, $issue_no);
    Table::end(1);
    Voiding::is_voided(28, $issue_no, _("This issue has been voided."));
  }

  /**
   * @param $issue_no
   */
  function display_wo_issue_details($issue_no) {
    $result = WO_Issue::get_details($issue_no);
    if (DB::_numRows($result) == 0) {
      Event::warning(_("There are no items for this issue."));
    } else {
      Table::start('padded grid');
      $th = array(_("Component"), _("Quantity"), _("Units"));
      Table::header($th);
      $j          = 1;
      $k          = 0; //row colour counter
      $total_cost = 0;
      while ($myrow = DB::_fetch($result)) {
        Cell::label($myrow["stock_id"] . " - " . $myrow["description"]);
        Cell::qty($myrow["qty_issued"], false, Item::qty_dec($myrow["stock_id"]));
        Cell::label($myrow["units"]);
        echo '</tr>';
        ;
        $j++;
        If ($j == 12) {
          $j = 1;
          Table::header($th);
        }
        //end of page full new headings if
      }
      //end of while
      Table::end();
    }
  }

  Display::heading(SysTypes::$names[ST_MANUISSUE] . " # " . $wo_issue_no);
  display_wo_issue($wo_issue_no);
  Display::heading(_("Items for this Issue"));
  display_wo_issue_details($wo_issue_no);
  echo "<br>";
  Page::end(true);



