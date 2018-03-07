<?php
  /**********************************************************************
  Copyright (C) Advanced Group PTY LTD
  Released under the terms of the GNU General Public License, GPL,
  as published by the Free Software Foundation, either version 3
  of the License, or (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
   ***********************************************************************/

  print_workorders();
  function print_workorders() {
    $report_type = '\\ADV\\App\\Reports\\PDF';
    $from        = $_POST['PARAM_0'];
    $to          = $_POST['PARAM_1'];
    $email       = $_POST['PARAM_2'];
    $comments    = $_POST['PARAM_3'];
    if ($from == null) {
      $from = 0;
    }
    if ($to == null) {
      $to = 0;
    }
    $dec  = User::_price_dec();
    $fno  = explode("-", $from);
    $tno  = explode("-", $to);
    $cols = array(4, 60, 190, 255, 320, 385, 450, 515);
    // $headers in doctext.inc
    $aligns = array('left', 'left', 'left', 'left', 'right', 'right', 'right');
    $params = array('comments' => $comments);
    $cur    = DB_Company::_get_pref('curr_default');
    if ($email == 0) {
      /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
      $rep           = new $report_type(_('WORK ORDER'), "WorkOrderBulk", $_POST['PARAM_0'] == $_POST['PARAM_1'] ? SA_MANUFTRANSVIEW : SA_MANUFBULKREP, User::_page_size());
      $rep->currency = $cur;
      $rep->Font();
      $rep->Info($params, $cols, null, $aligns);
    }
    for ($i = $fno[0]; $i <= $tno[0]; $i++) {
      $myrow = WO::get($i);
      if ($myrow === false) {
        continue;
      }
      $date_ = Dates::_sqlToDate($myrow["date_"]);
      if ($email == 1) {
        $rep           = new $report_type("", "", $_POST['PARAM_0'] == $_POST['PARAM_1'] ? SA_MANUFTRANSVIEW : SA_MANUFBULKREP, User::_page_size());
        $rep->currency = $cur;
        $rep->Font();
        $rep->title    = _('WORK ORDER');
        $rep->filename = "WorkOrder" . $myrow['reference'] . ".pdf";
        $rep->Info($params, $cols, null, $aligns);
      } else {
        $rep->title = _('WORK ORDER');
      }
      $rep->Header2($myrow, null, null, '', 26);
      $result = WO_Requirements::get($i);
      $rep->TextCol(0, 5, _("Work Order Requirements"), -2);
      $rep->NewLine(2);
      $has_marked = false;
      while ($myrow2 = DB::_fetch($result)) {
        $qoh      = 0;
        $show_qoh = true;
        // if it's a non-stock item (eg. service) don't show qoh
        if (!WO::has_stock_holding($myrow2["mb_flag"])) {
          $show_qoh = false;
        }
        if ($show_qoh) {
          $qoh = Item::get_qoh_on_date($myrow2["stock_id"], $myrow2["loc_code"], $date_);
        }
        if ($show_qoh && ($myrow2["units_req"] * $myrow["units_issued"] > $qoh) && !DB_Company::_get_pref('allow_negative_stock')
        ) {
          // oops, we don't have enough of one of the component items
          $has_marked = true;
        } else {
          $has_marked = false;
        }
        if ($has_marked) {
          $str = $myrow2['stock_id'] . " ***";
        } else {
          $str = $myrow2['stock_id'];
        }
        $rep->TextCol(0, 1, $str, -2);
        $rep->TextCol(1, 2, $myrow2['description'], -2);
        $rep->TextCol(2, 3, $myrow2['location_name'], -2);
        $rep->TextCol(3, 4, $myrow2['WorkCentreDescription'], -2);
        $dec = Item::qty_dec($myrow2["stock_id"]);
        $rep->AmountCol(4, 5, $myrow2['units_req'], $dec, -2);
        $rep->AmountCol(5, 6, $myrow2['units_req'] * $myrow['units_issued'], $dec, -2);
        $rep->AmountCol(6, 7, $myrow2['units_issued'], $dec, -2);
        $rep->NewLine(1);
        if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight)) {
          $rep->Header2($myrow, null, null, '', 26);
        }
      }
      $rep->NewLine(1);
      $rep->TextCol(0, 5, " *** = " . _("Insufficient stock"), -2);
      $comments = DB_Comments::get(ST_WORKORDER, $i);
      if ($comments && DB::_numRows($comments)) {
        $rep->NewLine();
        while ($comment = DB::_fetch($comments)) {
          $rep->TextColLines(0, 6, $comment['memo_'], -2);
        }
      }
    }
    if ($email == 0) {
      $rep->End();
    }
  }

