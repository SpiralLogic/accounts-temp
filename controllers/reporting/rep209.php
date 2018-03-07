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
  include_once(__DIR__ . DS . 'includes/lang/en_AU/report.php'); //TODO  change to language from config
  use \Reports\Report as Report;
  use ADV\App\Item\Item;
  use ADV\Core\Num;
  use ADV\App\User;
  use ADV\Core\DB\DB;

  print_po();
  /**
   * @param $order_no
   *
   * @return \ADV\Core\DB\Query\Result|Array
   */
  function get_po($order_no) {
    # __ADVANCEDEDIT__ BEGIN # include suppliers phone and fax number
    $sql
            = "SELECT purch_orders.*, suppliers.name, suppliers.account_no,
         suppliers.curr_code, suppliers.payment_terms, suppliers.phone, suppliers.fax, locations.location_name,
         suppliers.email, suppliers.address,suppliers.city,suppliers.state,suppliers.postcode, suppliers.contact
        FROM purch_orders, suppliers, locations
        WHERE purch_orders.creditor_id = suppliers.creditor_id
        AND locations.loc_code = into_stock_location
        AND purch_orders.order_no = " . DB::_escape($order_no);
    $result = DB::_query($sql, "The order cannot be retrieved");
    return DB::_fetch($result);
  }

  /**
   * @param $order_no
   *
   * @return null|PDOStatement
   */
  function get_po_details($order_no) {
    $sql
      = "SELECT purch_order_details.*, units
        FROM purch_order_details
        LEFT JOIN stock_master
        ON purch_order_details.item_code=stock_master.stock_id
        WHERE order_no =" . DB::_escape($order_no) . " ";
    $sql .= " ORDER BY po_detail_item";
    return DB::_query($sql, "Retreive order Line Items");
  }

  function print_po() {
    $report_type = '\\ADV\\App\\Reports\\PDF';
    $from        = $_POST['PARAM_0'];
    $to          = $_POST['PARAM_1'];
    $currency    = $_POST['PARAM_2'];
    $email       = $_POST['PARAM_3'];
    $comments    = $_POST['PARAM_4'];
    if ($from == null) {
      $from = 0;
    }
    if ($to == null) {
      $to = 0;
    }
    $dec  = User::_price_dec();
    $cols = array(4, 80, 310, 330, 380, 410, 450, 460, 300);
    // $headers in doctext.inc
    $aligns = array('left', 'left', 'center', 'center', 'right', 'right', 'right', 'right');
    $params = array('comments' => $comments);
    $cur    = DB_Company::_get_pref('curr_default');
    if ($email == 0) {
      /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
      $rep           = new $report_type(_('PURCHASE ORDER'), "PurchaseOrderBulk", $_POST['PARAM_0'] == $_POST['PARAM_1'] ? SA_SUPPTRANSVIEW : SA_SUPPBULKREP, User::_page_size());
      $rep->currency = $cur;
      $rep->Font();
      $rep->Info($params, $cols, null, $aligns);
    }
    for ($i = $from; $i <= $to; $i++) {
      $myrow                 = get_po($i);
      $baccount              = Bank_Account::get_default($myrow['curr_code']);
      $params['bankaccount'] = $baccount['id'];
      if ($email == 1) {
        $rep           = new $report_type("", "", $_POST['PARAM_0'] == $_POST['PARAM_1'] ? SA_SUPPTRANSVIEW : SA_SUPPBULKREP, User::_page_size());
        $rep->currency = $cur;
        $rep->Font();
        $rep->title    = _('PURCHASE ORDER');
        $rep->filename = "PurchaseOrder" . $i . ".pdf";
        $rep->Info($params, $cols, null, $aligns);
      } else {
        $rep->title = _('PURCHASE ORDER');
      }
      $rep->Header2($myrow, null, $myrow, $baccount, ST_PURCHORDER);
      $result   = get_po_details($i);
      $SubTotal = 0;
      while ($myrow2 = DB::_fetch($result)) {
        if ($myrow2['item_code'] != 'freight' || $myrow['freight'] != $myrow2['unit_price']) {
          $data = Purch_Order::get_data($myrow['creditor_id'], $myrow2['item_code']);
          if ($data !== false) {
            if ($data['supplier_description'] != "") {
              $myrow2['item_code'] = $data['supplier_description'];
            }
            if ($data['suppliers_uom'] != "") {
              $myrow2['units'] = $data['suppliers_uom'];
            }
            if ($data['conversion_factor'] > 1) {
              $myrow2['unit_price']       = Num::_round($myrow2['unit_price'] * $data['conversion_factor'], User::_price_dec());
              $myrow2['quantity_ordered'] = Num::_round($myrow2['quantity_ordered'] / $data['conversion_factor'], User::_qty_dec());
            }
          }
          $Net = Num::_round(($myrow2["unit_price"] * $myrow2["quantity_ordered"] * (1 - $myrow2["discount"])), User::_price_dec());
          $SubTotal += $Net;
          $price           = $myrow2["unit_price"];
          $DisplayPrice    = Num::_priceFormat($price);
          $DisplayDiscount = Num::_percentFormat($myrow2['discount'] * 100);
          $DisplayDiscount .= $DisplayDiscount > 0 ? '%' : '';
          $DisplayQty = Num::_format($myrow2["quantity_ordered"], Item::qty_dec($myrow2['item_code']));
          $DisplayNet = Num::_format($Net, $dec);
          $rep->TextCol(0, 1, $myrow2['item_code'], -2);
          $oldrow = $rep->row;
          $rep->TextColLines(1, 2, $myrow2['description'], -2);
          $newrow   = $rep->row;
          $rep->row = $oldrow;
          $rep->TextCol(2, 3, $DisplayQty, -2);
          $rep->TextCol(4, 5, $DisplayPrice, -2);
          $rep->TextCol(3, 4, $myrow2['units'], -2);
          $rep->TextCol(5, 6, $DisplayDiscount, -2);
          $rep->TextCol(6, 7, $DisplayNet, -2);
          $rep->row = $newrow;
          if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight)) {
            $rep->Header2($myrow, null, $myrow, $baccount, ST_PURCHORDER);
          }
        }
      }
      if ($myrow['comments'] != "") {
        $rep->NewLine();
        $rep->TextColLines(1, 5, $myrow['comments'], -2);
      }
      $display_sub_total = Num::_format($SubTotal, $dec);
      $rep->row          = $rep->bottomMargin + (15 * $rep->lineHeight);
      $linetype          = true;
      $doctype           = ST_PURCHORDER;
      extract($rep->getHeaderArray($doctype, false, $linetype));
      $rep->TextCol(3, 6, Report::SUBTOTAL, -2);
      $rep->TextCol(6, 7, $display_sub_total, -2);
      $rep->NewLine();
      $rep->TextCol(3, 6, 'Freight:', -2);
      $rep->TextCol(6, 7, Num::_format($myrow['freight'], $dec), -2);
      $rep->NewLine();
      $display_total = Num::_format($SubTotal + $myrow['freight'], $dec);
      $rep->Font('bold');
      $rep->TextCol(3, 6, Report::TOTAL_PO_EX_TAX, -2);
      $rep->TextCol(6, 7, $display_total, -2);
      $words = Item_Price::toWords($SubTotal, ST_PURCHORDER);
      if ($words != "" && isset($myrow['curr_code'])) {
        $rep->NewLine(1);
        $rep->TextCol(1, 7, $myrow['curr_code'] . ": " . $words, -2);
      }
      $rep->Font();
      if ($email == 1) {
        $myrow['contact_email'] = $myrow['email'];
        if (isset($myrow['contact'])) {
          $myrow['DebtorName'] = $myrow['contact'];
        } elseif (isset($myrow['name'])) {
          $myrow['DebtorName'] = $myrow['name'];
        }
        if ($myrow['reference'] == "") {
          $myrow['reference'] = $myrow['order_no'];
        }
        $rep->End($email, Report::ORDER_NO . " " . $myrow['reference'], $myrow);
      }
    }
    if ($email == 0) {
      $rep->End();
    }
  }

