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

  print_inventory_sales();
  /**
   * @param $category
   * @param $location
   * @param $fromcust
   * @param $from
   * @param $to
   *
   * @return null|PDOStatement
   */
  function get_transactions($category, $location, $fromcust, $from, $to) {
    $from = Dates::_dateToSql($from);
    $to   = Dates::_dateToSql($to);
    $sql
          = "SELECT stock_master.category_id,
            stock_category.description AS cat_name,
            stock_master.stock_id,
            stock_master.description, stock_master.inactive,
            stock_moves.loc_code,
            debtor_trans.debtor_id,
            debtors.name AS debtor_name,
            stock_moves.tran_date,
            SUM(-stock_moves.qty) AS qty,
            SUM(-stock_moves.qty*stock_moves.price*(1-stock_moves.discount_percent)) AS amt,
            SUM(-stock_moves.qty *(stock_master.material_cost + stock_master.labour_cost + stock_master.overhead_cost)) AS cost
        FROM stock_master,
            stock_category,
            debtor_trans,
            debtors,
            stock_moves
        WHERE stock_master.stock_id=stock_moves.stock_id
        AND stock_master.category_id=stock_category.category_id
        AND debtor_trans.debtor_id=debtors.debtor_id
        AND stock_moves.type=debtor_trans.type
        AND stock_moves.trans_no=debtor_trans.trans_no
        AND stock_moves.tran_date>='$from'
        AND stock_moves.tran_date<='$to'
        AND ((debtor_trans.type=" . ST_CUSTDELIVERY . " AND debtor_trans.version=1) OR stock_moves.type=" . ST_CUSTCREDIT . ")
        AND (stock_master.mb_flag='" . STOCK_PURCHASED . "' OR stock_master.mb_flag='" . STOCK_MANUFACTURE . "')";
    if ($category != 0) {
      $sql .= " AND stock_master.category_id = " . DB::_escape($category);
    }
    if ($location != 'all') {
      $sql .= " AND stock_moves.loc_code = " . DB::_escape($location);
    }
    if ($fromcust != -1) {
      $sql .= " AND debtors.debtor_id = " . DB::_escape($fromcust);
    }
    $sql
      .= " GROUP BY stock_master.stock_id, debtors.name ORDER BY stock_master.category_id,
            stock_master.stock_id, debtors.name";
    return DB::_query($sql, "No transactions were returned");
  }

  function print_inventory_sales() {
    $from        = $_POST['PARAM_0'];
    $to          = $_POST['PARAM_1'];
    $category    = $_POST['PARAM_2'];
    $location    = $_POST['PARAM_3'];
    $fromcust    = $_POST['PARAM_4'];
    $comments    = $_POST['PARAM_5'];
    $destination = $_POST['PARAM_6'];
    if ($destination) {
      $report_type = '\\ADV\\App\\Reports\\Excel';
    } else {
      $report_type = '\\ADV\\App\\Reports\\PDF';
    }
    $dec = User::_price_dec();
    if ($category == ALL_NUMERIC) {
      $category = 0;
    }
    if ($category == 0) {
      $cat = _('All');
    } else {
      $cat = Item_Category::get_name($category);
    }
    if ($location == ALL_TEXT) {
      $location = 'all';
    }
    if ($location == 'all') {
      $loc = _('All');
    } else {
      $loc = Inv_Location::get_name($location);
    }
    if ($fromcust == ALL_NUMERIC) {
      $fromc = _('All');
    } else {
      $fromc = Debtor::get_name($fromcust);
    }
    $cols    = array(0, 75, 175, 250, 300, 375, 450, 515);
    $headers = array(_('Category'), _('Description'), _('Customer'), _('Qty'), _('Sales'), _('Cost'), _('Contribution'));
    if ($fromcust != ALL_NUMERIC) {
      $headers[2] = '';
    }
    $aligns = array('left', 'left', 'left', 'right', 'right', 'right', 'right');
    $params = array(
      0 => $comments,
      1 => array(
        'text' => _('Period'),
        'from' => $from,
        'to'   => $to
      ),
      2 => array(
        'text' => _('Category'),
        'from' => $cat,
        'to'   => ''
      ),
      3 => array(
        'text' => _('Location'),
        'from' => $loc,
        'to'   => ''
      ),
      4 => array(
        'text' => _('Customer'),
        'from' => $fromc,
        'to'   => ''
      )
    );
    /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
    $rep = new $report_type(_('Inventory Sales Report'), "InventorySalesReport", SA_SALESANALYTIC, User::_page_size());
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();
    $res    = get_transactions($category, $location, $fromcust, $from, $to);
    $total  = $grandtotal = 0.0;
    $total1 = $grandtotal1 = 0.0;
    $total2 = $grandtotal2 = 0.0;
    $catt   = '';
    while ($trans = DB::_fetch($res)) {
      if ($catt != $trans['cat_name']) {
        if ($catt != '') {
          $rep->NewLine(2, 3);
          $rep->TextCol(0, 4, _('Total'));
          $rep->AmountCol(4, 5, $total, $dec);
          $rep->AmountCol(5, 6, $total1, $dec);
          $rep->AmountCol(6, 7, $total2, $dec);
          $rep->Line($rep->row - 2);
          $rep->NewLine();
          $rep->NewLine();
          $total = $total1 = $total2 = 0.0;
        }
        $rep->TextCol(0, 1, $trans['category_id']);
        $rep->TextCol(1, 6, $trans['cat_name']);
        $catt = $trans['cat_name'];
        $rep->NewLine();
      }
      $curr = Bank_Currency::for_debtor($trans['debtor_id']);
      $rate = Bank_Currency::exchange_rate_from_home($curr, Dates::_sqlToDate($trans['tran_date']));
      $trans['amt'] *= $rate;
      $cb = $trans['amt'] - $trans['cost'];
      $rep->NewLine();
      $rep->fontsize -= 2;
      $rep->TextCol(0, 1, $trans['stock_id']);
      if ($fromcust == ALL_NUMERIC) {
        $rep->TextCol(1, 2, $trans['description'] . ($trans['inactive'] == 1 ? " (" . _("Inactive") . ")" : ""), -1);
        $rep->TextCol(2, 3, $trans['debtor_name']);
      } else {
        $rep->TextCol(1, 3, $trans['description'] . ($trans['inactive'] == 1 ? " (" . _("Inactive") . ")" : ""), -1);
      }
      $rep->AmountCol(3, 4, $trans['qty'], Item::qty_dec($trans['stock_id']));
      $rep->AmountCol(4, 5, $trans['amt'], $dec);
      $rep->AmountCol(5, 6, $trans['cost'], $dec);
      $rep->AmountCol(6, 7, $cb, $dec);
      $rep->fontsize += 2;
      $total += $trans['amt'];
      $total1 += $trans['cost'];
      $total2 += $cb;
      $grandtotal += $trans['amt'];
      $grandtotal1 += $trans['cost'];
      $grandtotal2 += $cb;
    }
    $rep->NewLine(2, 3);
    $rep->TextCol(0, 4, _('Total'));
    $rep->AmountCol(4, 5, $total, $dec);
    $rep->AmountCol(5, 6, $total1, $dec);
    $rep->AmountCol(6, 7, $total2, $dec);
    $rep->Line($rep->row - 2);
    $rep->NewLine();
    $rep->NewLine(2, 1);
    $rep->TextCol(0, 4, _('Grand Total'));
    $rep->AmountCol(4, 5, $grandtotal, $dec);
    $rep->AmountCol(5, 6, $grandtotal1, $dec);
    $rep->AmountCol(6, 7, $grandtotal2, $dec);
    $rep->Line($rep->row - 4);
    $rep->NewLine();
    $rep->End();
  }

