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
  use ADV\Core\DB\DB;
  use ADV\Core\Input\Input;
  use ADV\App\Item\Item;
  use ADV\App\User;
  use ADV\Core\Num;

  print_invoices();
  function print_invoices() {
    $report_type = '\\ADV\\App\\Reports\\PDF';
    $from        = $_POST['PARAM_0'];
    $to          = $_POST['PARAM_1'];
    $currency    = $_POST['PARAM_2'];
    $email       = $_POST['PARAM_3'];
    $paylink     = $_POST['PARAM_4'];
    $comments    = $_POST['PARAM_5'];
    if ($from == null) {
      $from = 0;
    }
    if ($to == null) {
      $to = 0;
    }
    $dec = User::_price_dec();
    $fno = explode("-", $from);
    $tno = explode("-", $to);
    /**        code,descr,qty,unit,price,disc,tax,total */
    $cols = array(4, 75, 320, 350, 375, 425, 450, 485, 480, 475);
    // $headers in doctext.inc
    $aligns = array('left', 'left', 'center', 'left', 'left', 'left', 'center', 'right');
    $params = array('comments' => $comments);
    $cur    = DB_Company::_get_pref('curr_default');
    if ($email == 0) {
      /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
      $rep           = new $report_type(_('TAX INVOICE'), "InvoiceBulk", $_POST['PARAM_0'] == $_POST['PARAM_1'] ? SA_SALESTRANSVIEW : SA_SALESBULKREP, User::_page_size());
      $rep->currency = $cur;
      $rep->Font();
      $rep->Info($params, $cols, null, $aligns);
    }
    for ($i = $fno[0]; $i <= $tno[0]; $i++) {
      for ($j = ST_SALESINVOICE; $j <= ST_CUSTCREDIT; $j++) {
        if (isset($_POST['PARAM_6']) && $_POST['PARAM_6'] != $j) {
          continue;
        }
        if (!Debtor_Trans::exists($j, $i)) {
          continue;
        }
        $sign                     = $j == ST_SALESINVOICE ? 1 : -1;
        $myrow                    = Debtor_Trans::get($i, $j);
        $baccount                 = Bank_Account::get_default($myrow['curr_code']);
        $params['bankaccount']    = $baccount['id'];
        $branch                   = Sales_Branch::get($myrow["branch_id"]);
        $branch['disable_branch'] = $paylink; // helper
        if ($j == ST_SALESINVOICE) {
          $sales_order = Sales_Order::get_header($myrow["order_"], ST_SALESORDER);
        } else {
          $sales_order = null;
        }
        if ($email == 1) {
          $rep           = new $report_type("", "", $_POST['PARAM_0'] == $_POST['PARAM_1'] ? SA_SALESTRANSVIEW : SA_SALESBULKREP, User::_page_size());
          $rep->currency = $cur;
          $rep->Font();
          if ($j == ST_SALESINVOICE) {
            $rep->title    = _('TAX INVOICE');
            $rep->filename = "Invoice" . $myrow['reference'] . ".pdf";
          } else {
            $rep->title    = _('CREDIT NOTE');
            $rep->filename = "CreditNote" . $myrow['reference'] . ".pdf";
          }
          $rep->Info($params, $cols, null, $aligns);
        } else {
          $rep->title = ($j == ST_SALESINVOICE) ? _('TAX INVOICE') : _('CREDIT NOTE');
        }
        $rep->Header2($myrow, $branch, $sales_order, $baccount, $j);
        $result   = Debtor_TransDetail::get($j, $i);
        $SubTotal = 0;
        while ($myrow2 = DB::_fetch($result)) {
          if ($myrow2["quantity"] == 0) {
            continue;
          }
          $Net = Num::_round(((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]), User::_price_dec());
          $SubTotal += $Net;
          $TaxType      = Tax_ItemType::get_for_item($myrow2['stock_id']);
          $DisplayPrice = Num::_format($myrow2["unit_price"], $dec);
          $DisplayQty   = Num::_format($myrow2["quantity"], Item::qty_dec($myrow2['stock_id']));
          $DisplayNet   = Num::_format($Net, $dec);
          if ($myrow2["discount_percent"] == 0) {
            $DisplayDiscount = "";
          } else {
            $DisplayDiscount = Num::_format($myrow2["discount_percent"] * 100, User::_percent_dec()) . "%";
          }
          $rep->TextCol(0, 1, $myrow2['stock_id'], -2);
          $oldrow = $rep->row;
          $rep->TextColLines(1, 2, $myrow2['StockDescription'], -2);
          $newrow   = $rep->row;
          $rep->row = $oldrow;
          $rep->TextCol(2, 3, $DisplayQty, -2);
          $rep->TextCol(3, 4, $myrow2['units'], -2);
          $rep->TextCol(4, 5, $DisplayPrice, -2);
          $rep->TextCol(5, 6, $DisplayDiscount, -2);
          $rep->TextCol(6, 7, $TaxType[1], -2);
          $rep->TextCol(7, 8, $DisplayNet, -2);
          $rep->row = $newrow;
          //$rep->NewLine(1);
          if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight)) {
            $rep->Header2($myrow, $branch, $sales_order, $baccount, $j);
          }
        }
        $comments = DB_Comments::get($j, $i);
        if ($comments && DB::_numRows($comments)) {
          $rep->NewLine();
          while ($comment = DB::_fetch($comments)) {
            $rep->TextColLines(0, 6, $comment['memo_'], -2);
          }
        }
        $display_sub_total = Num::_format($SubTotal, $dec);
        $display_freight   = Num::_format($sign * $myrow["ov_freight"], $dec);
        $fromBottom        = ($myrow['type'] == ST_SALESINVOICE) ? 15 : 12;
        $rep->row          = $rep->bottomMargin + ($fromBottom * $rep->lineHeight);
        $linetype          = true;
        $doctype           = $j;
        extract($rep->getHeaderArray($doctype));
        $rep->TextCol(3, 7, $rep->doc_sub_total, -2);
        $rep->TextCol(7, 8, $display_sub_total, -2);
        $rep->NewLine();
        $rep->TextCol(3, 7, $rep->doc_shipping, -2);
        $rep->TextCol(7, 8, $display_freight, -2);
        $rep->NewLine();
        $tax_items = GL_Trans::get_tax_details($j, $i);
        while ($tax_item = DB::_fetch($tax_items)) {
          $DisplayTax = Num::_format($tax_item['amount'], $dec);
          if ($tax_item['included_in_price']) {
            $rep->TextCol(3, 7, $rep->doc_included . " " . $tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%) " . $rep->doc_amount . ": " . $DisplayTax, -2);
          } else {
            $rep->TextCol(3, 7, $tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%)", -2);
            $rep->TextCol(7, 8, $DisplayTax, -2);
          }
        }
        $rep->NewLine();
        $display_total = Num::_format(($myrow["ov_freight"] + $myrow["ov_gst"] + $myrow["ov_amount"] + $myrow["ov_freight_tax"]), $dec);
        $rep->Font('bold');
        $rep->TextCol(3, 7, $rep->doc_total_invoice, -2);
        $rep->TextCol(7, 8, $display_total, -2);
        $words = Item_Price::toWords($myrow['Total'], $j);
        if ($myrow['type'] == ST_SALESINVOICE) {
          $rep->NewLine();
          $rep->NewLine();
          $invBalance = Sales_Allocation::get_balance($myrow['type'], $myrow['trans_no']);
          $rep->TextCol(3, 7, 'Total Received', -2);
          $rep->AmountCol(7, 8, $myrow['Total'] - $invBalance, $dec, -2);
          $rep->NewLine();
          $rep->TextCol(3, 7, 'Outstanding Balance', -2);
          $rep->AmountCol(7, 8, $invBalance, $dec, -2);
          $rep->NewLine();
          if ($words != "") {
            $rep->NewLine(1);
            $rep->TextCol(1, 7, $myrow['curr_code'] . ": " . $words, -2);
          }
        }
        $rep->Font();
        if ($email == 1) {
          $myrow['dimension_id'] = $paylink; // helper for pmt link
          $myrow['email']        = $myrow['email'] ? : Input::_get('Email');
          if (!$myrow['email']) {
            $myrow['email']      = $branch['email'];
            $myrow['DebtorName'] = $branch['br_name'];
          }
          $rep->End($email, $rep->doc_invoice_no . " " . $myrow['reference'], $myrow, $j);
        }
      }
    }
    if ($email == 0) {
      $rep->End();
    }
  }

