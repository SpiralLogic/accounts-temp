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

  print_grn_valuation();
  /**
   * @param $from
   * @param $to
   *
   * @return null|PDOStatement
   */
  function get_transactions($from, $to) {
    $from = Dates::_dateToSql($from);
    $to   = Dates::_dateToSql($to);
    $sql
          = "SELECT DISTINCT grn_batch.creditor_id,
 purch_order_details.*,
 stock_master.description, stock_master.inactive
 FROM stock_master,
 purch_order_details,
 grn_batch
 WHERE stock_master.stock_id=purch_order_details.item_code
 AND grn_batch.purch_order_no=purch_order_details.order_no
 AND purch_order_details.quantity_received>0
 AND grn_batch.delivery_date>='$from'
 AND grn_batch.delivery_date<='$to'
 ORDER BY stock_master.stock_id, grn_batch.delivery_date";

    return DB::_query($sql, "No transactions were returned");
  }

  function print_grn_valuation() {
    $from        = $_POST['PARAM_0'];
    $to          = $_POST['PARAM_1'];
    $comments    = $_POST['PARAM_2'];
    $destination = $_POST['PARAM_3'];
    if ($destination) {

      $report_type = '\\ADV\\App\\Reports\\Excel';
    } else {

      $report_type = '\\ADV\\App\\Reports\\PDF';
    }
    $dec     = User::_price_dec();
    $cols    = array(0, 75, 225, 275, 345, 390, 445, 515);
    $headers = array(
      _('Stock ID'),
      _('Description'),
      _('PO No'),
      _('Qty Received'),
      _('Unit Price'),
      _('Actual Price'),
      _('Total')
    );
    $aligns  = array('left', 'left', 'left', 'right', 'right', 'right', 'right');
    $params  = array(
      0 => $comments,
      1 => array(
        'text' => _('Period'),
        'from' => $from,
        'to'   => $to
      )
    );
    /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
    $rep = new $report_type(_('GRN Valuation Report'), "GRNValuationReport", SA_SUPPLIERANALYTIC, User::_page_size());
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();
    $res      = get_transactions($from, $to);
    $total    = $qtotal = $grandtotal = 0.0;
    $stock_id = '';
    while ($trans = DB::_fetch($res)) {
      if ($stock_id != $trans['item_code']) {
        if ($stock_id != '') {
          $rep->Line($rep->row - 4);
          $rep->NewLine(2);
          $rep->TextCol(0, 3, _('Total'));
          $rep->AmountCol(3, 4, $qtotal, $qdec);
          $rep->AmountCol(6, 7, $total, $dec);
          $rep->NewLine();
          $total = $qtotal = 0;
        }
        $stock_id = $trans['item_code'];
      }
      $curr = Bank_Currency::for_creditor($trans['creditor_id']);
      $rate = Bank_Currency::exchange_rate_from_home($curr, Dates::_sqlToDate($trans['delivery_date']));
      $trans['unit_price'] *= $rate;
      $trans['act_price'] *= $rate;
      $rep->NewLine();
      $rep->TextCol(0, 1, $trans['item_code']);
      $rep->TextCol(1, 2, $trans['description'] . ($trans['inactive'] == 1 ? " (" . _("Inactive") . ")" : ""), -1);
      $rep->TextCol(2, 3, $trans['order_no']);
      $qdec = Item::qty_dec($trans['item_code']);
      $rep->AmountCol(3, 4, $trans['quantity_received'], $qdec);
      $rep->AmountCol(4, 5, $trans['unit_price'], $dec);
      $rep->AmountCol(5, 6, $trans['act_price'], $dec);
      $amt = Num::_round($trans['quantity_received'] * $trans['act_price'], $dec);
      $rep->AmountCol(6, 7, $amt, $dec);
      $total += $amt;
      $qtotal += $trans['quantity_received'];
      $grandtotal += $amt;
    }
    if ($stock_id != '') {
      $rep->Line($rep->row - 4);
      $rep->NewLine(2);
      $rep->TextCol(0, 3, _('Total'));
      $rep->AmountCol(3, 4, $qtotal, $qdec);
      $rep->AmountCol(6, 7, $total, $dec);
      $rep->Line($rep->row - 4);
      $rep->NewLine(2);
      $rep->TextCol(0, 6, _('Grand Total'));
      $rep->AmountCol(6, 7, $grandtotal, $dec);
    }
    $rep->Line($rep->row - 4);
    $rep->NewLine();
    $rep->End();
  }

