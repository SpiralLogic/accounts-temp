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
  print_sales_quotations();
  function print_sales_quotations() {
    global $print_as_quote;
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
    $cols = array(4, 70, 300, 320, 360, 395, 450, 475, 515);
    // $headers in doctext.inc
    $aligns = array('left', 'left', 'center', 'left', 'left', 'left', 'left', 'right');
    $params = array('comments' => $comments);
    $cur    = DB_Company::_get_pref('curr_default');
    if ($email == 0) {
      /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
      $rep           = new $report_type(_("SALES QUOTATION"), "SalesQuotationBulk", $_POST['PARAM_0'] == $_POST['PARAM_1'] ? SA_SALESTRANSVIEW : SA_SALESBULKREP, User::_page_size(
      ));
      $rep->currency = $cur;
      $rep->Font();
      $rep->Info($params, $cols, null, $aligns);
    }
    for ($i = $from; $i <= $to; $i++) {
      $myrow                 = Sales_Order::get_header($i, ST_SALESQUOTE);
      $baccount              = Bank_Account::get_default($myrow['curr_code']);
      $params['bankaccount'] = $baccount['id'];
      $branch                = Sales_Branch::get($myrow["branch_id"]);
      if ($email == 1) {
        $rep           = new $report_type("", "", $_POST['PARAM_0'] == $_POST['PARAM_1'] ? SA_SALESTRANSVIEW : SA_SALESBULKREP, User::_page_size());
        $rep->currency = $cur;
        $rep->Font();
        $rep->filename = "SalesQuotation" . $i . ".pdf";
        $rep->Info($params, $cols, null, $aligns);
      }
      $rep->title = _("SALES QUOTATION");
      $rep->Header2($myrow, $branch, $myrow, $baccount, ST_SALESQUOTE);
      $result   = Sales_Order::get_details($i, ST_SALESQUOTE);
      $SubTotal = 0;
      $TaxTotal = 0;
      while ($myrow2 = DB::_fetch($result)) {
        $Net = Num::_round(((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]), User::_price_dec());
        $SubTotal += $Net;
        # __ADVANCEDEDIT__ BEGIN #
        $TaxType = Tax_ItemType::get_for_item($myrow2['stk_code']);
        $TaxTotal += Tax::for_item($myrow2['stk_code'], $Net, $TaxType);
        # __ADVANCEDEDIT__ END #
        $DisplayPrice = Num::_format($myrow2["unit_price"], $dec);
        $DisplayQty   = Num::_format($myrow2["quantity"], Item::qty_dec($myrow2['stk_code']));
        $DisplayNet   = Num::_format($Net, $dec);
        if ($myrow2["discount_percent"] == 0) {
          $DisplayDiscount = "";
        } else {
          $DisplayDiscount = Num::_format($myrow2["discount_percent"] * 100, User::_percent_dec()) . "%";
        }
        $rep->TextCol(0, 1, $myrow2['stk_code'], -2);
        $oldrow = $rep->row;
        $rep->TextColLines(1, 2, $myrow2['description'], -2);
        $newrow   = $rep->row;
        $rep->row = $oldrow;
        $rep->TextCol(2, 3, $DisplayQty, -2);
        $rep->TextCol(3, 4, $myrow2['units'], -2);
        $rep->TextCol(4, 5, $DisplayPrice, -2);
        $rep->TextCol(5, 6, $DisplayDiscount, -2);
        $rep->TextCol(6, 7, $TaxType[1], -2);
        $rep->TextCol(7, 8, $DisplayNet, -2);
        $rep->row = $newrow;
        $rep->NewLine();
        if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight)) {
          $rep->Header2($myrow, $branch, $myrow, $baccount, ST_SALESQUOTE);
        }
      }
      if ($myrow['comments'] != "") {
        $rep->NewLine();
        $rep->TextColLines(1, 5, $myrow['comments'], -2);
      }
      if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight)) {
        $rep->Header2($myrow, $branch, $myrow, $baccount, ST_SALESQUOTE);
      }
      $display_freight = Num::_format($myrow["freight_cost"], $dec);
      $TaxTotal += $myrow["freight_cost"] * .1;
      $DisplayTaxTot     = Num::_format($TaxTotal, $dec);
      $display_sub_total = Num::_format($SubTotal, $dec);
      $rep->row          = $rep->bottomMargin + (15 * $rep->lineHeight);
      $linetype          = true;
      $doctype           = ST_SALESQUOTE;
      extract($rep->getHeaderArray($doctype, false, $linetype));
      $rep->TextCol(4, 7, $doc_sub_total, -2);
      $rep->TextCol(7, 8, $display_sub_total, -2);
      $rep->NewLine();
      $rep->TextCol(4, 7, $doc_shipping . ' (ex.GST)', -2);
      $rep->TextCol(7, 8, $display_freight, -2);
      $rep->NewLine();
      # __ADVANCEDEDIT__ BEGIN # added tax to invoice
      $rep->TextCol(4, 7, 'Total GST (10%)', -2);
      $rep->TextCol(7, 8, $DisplayTaxTot, -2);
      $rep->NewLine();
      # __ADVANCEDEDIT__ END #
      $display_total = Num::_format($myrow["freight_cost"] + $SubTotal + $TaxTotal, $dec);
      $rep->Font('bold');
      #		if ($myrow['tax_included'] == 0)
      #			$rep->TextCol(4, 7, $doc_TOTAL_ORDER, - 2);
      #		else
      $rep->TextCol(4, 7, $doc_TOTAL_ORDER2, -2);
      $rep->TextCol(7, 8, $display_total, -2);
      $words = Item_Price::toWords($myrow["freight_cost"] + $SubTotal, ST_SALESQUOTE);
      if ($words != "") {
        $rep->NewLine(1);
        $rep->TextCol(1, 7, $myrow['curr_code'] . ": " . $words, -2);
      }
      $rep->Font();
      if ($email == 1) {
        if ($myrow['contact_email'] == '') {
          $myrow['contact_email'] = $branch['email'];
          if ($myrow['contact_email'] == '') {
            $myrow['contact_email'] = $myrow['master_email'];
          }
          $myrow['DebtorName'] = $myrow['name'];
        }
        //$myrow['reference'] = $i;
        $rep->End($email, $doc_invoice_no . " " . $i, $myrow);
      }
    }
    if ($email == 0) {
      $rep->End();
    }
  }
