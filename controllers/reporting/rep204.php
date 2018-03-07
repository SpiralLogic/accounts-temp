<?php

  /* * ********************************************************************
       Copyright (C) Advanced Group PTY LTD
       Released under the terms of the GNU General Public License, GPL,
       as published by the Free Software Foundation, either version 3
       of the License, or (at your option) any later version.
       This program is distributed in the hope that it will be useful,
       but WITHOUT ANY WARRANTY; without even the implied warranty of
       MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
       See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
      * ********************************************************************* */

  print_outstanding_GRN();
  /**
   * @param $fromsupp
   *
   * @return null|PDOStatement
   */
  function get_transactions($fromsupp) {
    $sql
      = "SELECT grn_batch.id,
            order_no,
            grn_batch.creditor_id,
            suppliers.name,
            grn_items.item_code,
            grn_items.description,
            qty_recd,
            quantity_inv,
            std_cost_unit,
            act_price,
            unit_price
        FROM grn_items,
            grn_batch,
            purch_order_details,
            suppliers
        WHERE grn_batch.creditor_id=suppliers.creditor_id
        AND grn_batch.id = grn_items.grn_batch_id
        AND grn_items.po_detail_item = purch_order_details.po_detail_item
        AND qty_recd-quantity_inv <>0 ";
    if ($fromsupp != ALL_NUMERIC) {
      $sql .= "AND grn_batch.creditor_id =" . DB::_escape($fromsupp) . " ";
    }
    $sql
      .= "ORDER BY grn_batch.creditor_id,
            grn_batch.id";
    return DB::_query($sql, "No transactions were returned");
  }

  function print_outstanding_GRN() {
    $fromsupp    = $_POST['PARAM_0'];
    $comments    = $_POST['PARAM_1'];
    $destination = $_POST['PARAM_2'];
    if ($destination) {
      $report_type = '\\ADV\\App\\Reports\\Excel';
    } else {
      $report_type = '\\ADV\\App\\Reports\\PDF';
    }
    if ($fromsupp == ALL_NUMERIC) {
      $from = _('All');
    } else {
      $from = Creditor::get_name($fromsupp);
    }
    $dec     = User::_price_dec();
    $cols    = array(0, 40, 80, 190, 250, 320, 385, 450, 515);
    $headers = array(
      _('GRN'),
      _('Order'),
      _('Item') . '/' . _('Description'),
      _('Qty Recd'),
      _('qty Inv'),
      _('Balance'),
      _('Std Cost'),
      _('Value')
    );
    $aligns  = array('left', 'left', 'left', 'right', 'right', 'right', 'right', 'right');
    $params  = array(
      0 => $comments,
      1 => array('text' => _('Supplier'), 'from' => $from, 'to' => '')
    );
    /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
    $rep = new $report_type(_('Outstanding GRNs Report'), "OutstandingGRN", SA_SUPPLIERANALYTIC, User::_page_size());
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();
    $Tot_Val     = 0;
    $Supplier    = '';
    $SuppTot_Val = 0;
    $res         = get_transactions($fromsupp);
    While ($GRNs = DB::_fetch($res)) {
      $dec2 = Item::qty_dec($GRNs['item_code']);
      if ($Supplier != $GRNs['creditor_id']) {
        if ($Supplier != '') {
          $rep->NewLine(2);
          $rep->TextCol(0, 7, _('Total'));
          $rep->AmountCol(7, 8, $SuppTot_Val, $dec);
          $rep->Line($rep->row - 2);
          $rep->NewLine(3);
          $SuppTot_Val = 0;
        }
        $rep->TextCol(0, 6, $GRNs['name']);
        $Supplier = $GRNs['creditor_id'];
      }
      $rep->NewLine();
      $rep->TextCol(0, 1, $GRNs['id']);
      $rep->TextCol(1, 2, $GRNs['order_no']);
      $rep->TextCol(2, 3, $GRNs['item_code'] . '-' . $GRNs['description']);
      $rep->AmountCol(3, 4, $GRNs['qty_recd'], $dec2);
      $rep->AmountCol(4, 5, $GRNs['quantity_inv'], $dec2);
      $QtyOstg = $GRNs['qty_recd'] - $GRNs['quantity_inv'];
      $Value   = ($GRNs['qty_recd'] - $GRNs['quantity_inv']) * $GRNs['std_cost_unit'];
      $rep->AmountCol(5, 6, $QtyOstg, $dec2);
      $rep->AmountCol(6, 7, $GRNs['std_cost_unit'], $dec);
      $rep->AmountCol(7, 8, $Value, $dec);
      $Tot_Val += $Value;
      $SuppTot_Val += $Value;
      $rep->NewLine(0, 1);
    }
    if ($Supplier != '') {
      $rep->NewLine();
      $rep->TextCol(0, 7, _('Total'));
      $rep->AmountCol(7, 8, $SuppTot_Val, $dec);
      $rep->Line($rep->row - 2);
      $rep->NewLine(3);
      $SuppTot_Val = 0;
    }
    $rep->NewLine(2);
    $rep->TextCol(0, 7, _('Grand Total'));
    $rep->AmountCol(7, 8, $Tot_Val, $dec);
    $rep->Line($rep->row - 2);
    $rep->NewLine();
    $rep->End();
  }

