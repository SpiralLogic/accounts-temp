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

  print_bill_of_material();
  /**
   * @param $from
   * @param $to
   *
   * @return null|PDOStatement
   */
  function get_transactions($from, $to) {
    $sql
      = "SELECT bom.parent,
            bom.component,
            stock_master.description as CompDescription,
            bom.quantity,
            bom.loc_code,
            bom.workcentre_added
        FROM
            stock_master,
            bom
        WHERE stock_master.stock_id=bom.component
        AND bom.parent >= " . DB::_escape($from) . "
        AND bom.parent <= " . DB::_escape($to) . "
        ORDER BY
            bom.parent,
            bom.component";

    return DB::_query($sql, "No transactions were returned");
  }

  function print_bill_of_material() {
    $frompart    = $_POST['PARAM_0'];
    $topart      = $_POST['PARAM_1'];
    $comments    = $_POST['PARAM_2'];
    $destination = $_POST['PARAM_3'];
    if ($destination) {

      $report_type = '\\ADV\\App\\Reports\\Excel';
    } else {

      $report_type = '\\ADV\\App\\Reports\\PDF';
    }
    $cols    = array(0, 50, 305, 375, 445, 515);
    $headers = array(_('Component'), _('Description'), _('Loc'), _('Wrk Ctr'), _('Quantity'));
    $aligns  = array('left', 'left', 'left', 'left', 'right');
    $params  = array(
      0 => $comments,
      1 => array(
        'text' => _('Component'),
        'from' => $frompart,
        'to'   => $topart
      )
    );
    /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
    $rep = new $report_type(_('Bill of Material Listing'), "BillOfMaterial", SA_BOMREP, User::_page_size());
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();
    $res    = get_transactions($frompart, $topart);
    $parent = '';
    while ($trans = DB::_fetch($res)) {
      if ($parent != $trans['parent']) {
        if ($parent != '') {
          $rep->Line($rep->row - 2);
          $rep->NewLine(2, 3);
        }
        $rep->TextCol(0, 1, $trans['parent']);
        $desc = Item::get($trans['parent']);
        $rep->TextCol(1, 2, $desc['description']);
        $parent = $trans['parent'];
        $rep->NewLine();
      }
      $rep->NewLine();
      $dec = Item::qty_dec($trans['component']);
      $rep->TextCol(0, 1, $trans['component']);
      $rep->TextCol(1, 2, $trans['CompDescription']);
      //$rep->TextCol(2, 3, $trans['loc_code']);
      //$rep->TextCol(3, 4, $trans['workcentre_added']);
      $wc = WO_WorkCentre::get($trans['workcentre_added']);
      $rep->TextCol(2, 3, Inv_Location::get_name($trans['loc_code']));
      $rep->TextCol(3, 4, $wc['name']);
      $rep->AmountCol(4, 5, $trans['quantity'], $dec);
    }
    $rep->Line($rep->row - 4);
    $rep->NewLine();
    $rep->End();
  }

