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

  print_inventory_valuation_report();
  /**
   * @param $category
   * @param $location
   *
   * @return null|PDOStatement
   */
  function get_transactions($category, $location) {
    $sql
      = "SELECT stock_master.category_id,
            stock_category.description AS cat_name,
            stock_master.stock_id,
            stock_master.description, stock_master.inactive,
            stock_moves.loc_code,
            SUM(stock_moves.qty) AS QtyOnHand,
            stock_master.material_cost + stock_master.labour_cost + stock_master.overhead_cost AS UnitCost,
            SUM(stock_moves.qty) *(stock_master.material_cost + stock_master.labour_cost + stock_master.overhead_cost) AS ItemTotal
        FROM stock_master,
            stock_category,
            stock_moves
        WHERE stock_master.stock_id=stock_moves.stock_id
        AND stock_master.category_id=stock_category.category_id
        GROUP BY stock_master.category_id,
            stock_category.description, ";
    if ($location != 'all') {
      $sql .= "stock_moves.loc_code, ";
    }
    $sql
      .= "UnitCost,
            stock_master.stock_id,
            stock_master.description
        HAVING SUM(stock_moves.qty) != 0";
    if ($category != 0) {
      $sql .= " AND stock_master.category_id = " . DB::_escape($category);
    }
    if ($location != 'all') {
      $sql .= " AND stock_moves.loc_code = " . DB::_escape($location);
    }
    $sql
      .= " ORDER BY stock_master.category_id,
            stock_master.stock_id";

    return DB::_query($sql, "No transactions were returned");
  }

  function print_inventory_valuation_report() {
    $category    = $_POST['PARAM_0'];
    $location    = $_POST['PARAM_1'];
    $detail      = $_POST['PARAM_2'];
    $comments    = $_POST['PARAM_3'];
    $destination = $_POST['PARAM_4'];
    if ($destination) {

      $report_type = '\\ADV\\App\\Reports\\Excel';
    } else {

      $report_type = '\\ADV\\App\\Reports\\PDF';
    }
    $detail = !$detail;
    $dec    = User::_price_dec();
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
    $cols    = array(0, 100, 250, 350, 450, 515);
    $headers = array(_('Category'), '', _('Quantity'), _('Unit Cost'), _('Value'));
    $aligns  = array('left', 'left', 'right', 'right', 'right');
    $params  = array(
      0 => $comments,
      1 => array(
        'text' => _('Category'),
        'from' => $cat,
        'to'   => ''
      ),
      2 => array(
        'text' => _('Location'),
        'from' => $loc,
        'to'   => ''
      )
    );
    /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
    $rep = new $report_type(_('Inventory Valuation Report'), "InventoryValReport", SA_ITEMSVALREP, User::_page_size());
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();
    $res   = get_transactions($category, $location);
    $total = $grandtotal = 0.0;
    $catt  = '';
    while ($trans = DB::_fetch($res)) {
      if ($catt != $trans['cat_name']) {
        if ($catt != '') {
          if ($detail) {
            $rep->NewLine(2, 3);
            $rep->TextCol(0, 4, _('Total'));
          }
          $rep->AmountCol(4, 5, $total, $dec);
          if ($detail) {
            $rep->Line($rep->row - 2);
            $rep->NewLine();
          }
          $rep->NewLine();
          $total = 0.0;
        }
        $rep->TextCol(0, 1, $trans['category_id']);
        $rep->TextCol(1, 2, $trans['cat_name']);
        $catt = $trans['cat_name'];
        if ($detail) {
          $rep->NewLine();
        }
      }
      if ($detail) {
        $rep->NewLine();
        $rep->fontsize -= 2;
        $rep->TextCol(0, 1, $trans['stock_id']);
        $rep->TextCol(1, 2, $trans['description'] . ($trans['inactive'] == 1 ? " (" . _("Inactive") . ")" : ""), -1);
        $rep->AmountCol(2, 3, $trans['QtyOnHand'], Item::qty_dec($trans['stock_id']));
        $rep->AmountCol(3, 4, $trans['UnitCost'], $dec);
        $rep->AmountCol(4, 5, $trans['ItemTotal'], $dec);
        $rep->fontsize += 2;
      }
      $total += $trans['ItemTotal'];
      $grandtotal += $trans['ItemTotal'];
    }
    if ($detail) {
      $rep->NewLine(2, 3);
      $rep->TextCol(0, 4, _('Total'));
    }
    $rep->Amountcol(4, 5, $total, $dec);
    if ($detail) {
      $rep->Line($rep->row - 2);
      $rep->NewLine();
    }
    $rep->NewLine(2, 1);
    $rep->TextCol(0, 4, _('Grand Total'));
    $rep->AmountCol(4, 5, $grandtotal, $dec);
    $rep->Line($rep->row - 4);
    $rep->NewLine();
    $rep->End();
  }

