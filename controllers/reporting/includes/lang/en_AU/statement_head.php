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
  // New version (without vertical lines)

  $this->row = $this->pageHeight - $this->topMargin;
  $upper     = $this->row - 2 * $this->lineHeight;
  $lower     = $this->bottomMargin + 8 * $this->lineHeight;
  $iline1    = $upper - 7.5 * $this->lineHeight;
  $iline2    = $iline1 - 8 * $this->lineHeight;
  $iline3    = $iline2 - 1.5 * $this->lineHeight;
  $iline4    = $iline3 - 1.5 * $this->lineHeight;
  $iline5    = $iline4 - 3 * $this->lineHeight;
  $iline6    = $iline5 - 1.5 * $this->lineHeight;
  $iline7    = $lower;
  $right     = $this->pageWidth - $this->rightMargin;
  $width     = ($right - $this->leftMargin) / 5;
  $icol      = $this->pageWidth / 2;
  $ccol      = $this->cols[0] + 4;
  $c2col     = $ccol + 60;
  $ccol2     = $icol / 2;
  $mcol      = $icol + 8;
  $mcol2     = $this->pageWidth - $ccol2;
  $cols      = count($this->cols);
  $this->SetDrawColor(205, 205, 205);
  $this->Line($iline1, 3);
  $this->SetDrawColor(128, 128, 128);
  $this->Line($iline1);
  $this->rectangle($this->leftMargin, $iline2, $right - $this->leftMargin, $iline2 - $iline3, "F", null, array(222, 231, 236));
  $this->Line($iline2);
  $this->Line($iline3);
  $this->Line($iline4);
  $this->rectangle($this->leftMargin, $iline5, $right - $this->leftMargin, $iline5 - $iline6, "F", null, array(222, 231, 236));
  $this->Line($iline5);
  $this->Line($iline6);
  $this->Line($iline7);
  $this->LineTo($this->leftMargin, $iline2, $this->leftMargin, $iline4);
  $col = $this->leftMargin;
  for ($i = 0; $i < 5; $i++) {
    $this->LineTo($col += $width, $iline2, $col, $iline4);
  }
  $this->LineTo($right, $iline2, $right, $iline4);
  $this->LineTo($this->leftMargin, $iline5, $this->leftMargin, $iline7);
  $this->LineTo($this->cols[$cols - 2] + 4, $iline5, $this->cols[$cols - 2] + 4, $iline7);
  $this->LineTo($right, $iline5, $right, $iline7);
  $this->NewLine();
  if ($this->company['coy_logo'] != '') {
    $logo = PATH_COMPANY . "images/" . $this->company['coy_logo'];
    $this->AddImage($logo, $ccol, $this->row, 0, 40);
  } else {
    $this->fontSize += 4;
    $this->Font('bold');
    $this->Text($ccol, $this->company['coy_name'], $icol);
    $this->Font();
    $this->fontSize -= 4;
  }
  $this->fontSize += 10;
  $this->Font('bold');
  $this->TextWrap($mcol, $this->row, $this->pageWidth - $this->rightMargin - $mcol - 20, $this->title, 'right');
  $this->Font();
  $this->fontSize -= 10;
  $this->NewLine();
  $this->SetTextColor(0, 0, 0);
  $adrline = $this->row;
  $this->TextWrapLines($ccol, $icol, $this->company['postal_address']);
  $this->NewLine();
  $this->Font('italic');
  $customer_branch_details = Sales_Branch::get_main($myrow['debtor_id']);
  if ($this->company['phone'] != "") {
    $this->Text($ccol, _("Phone"), $c2col);
    $this->Text($c2col, $this->company['phone'], $mcol);
    $this->NewLine();
  }
  if ($this->company['fax'] != "") {
    $this->Text($ccol, _("Fax"), $c2col);
    $this->Text($c2col, $this->company['fax'], $mcol);
    $this->NewLine();
  }
  if ($this->company['email'] != "") {
    $this->Text($ccol, _("Email"), $c2col);
    $url = "mailto:" . $this->company['email'];
    $this->SetTextColor(0, 0, 255);
    $this->Text($c2col, $this->company['email'], $mcol);
    $this->SetTextColor(0, 0, 0);
    $this->addLink($url, $c2col, $this->row, $mcol, $this->row + $this->lineHeight);
    $this->NewLine();
  }
  if ($this->company['gst_no'] != "") {
    $this->Text($ccol, $doc_Our_TAX_no, $c2col);
    $this->Text($c2col, $this->company['gst_no'], $mcol);
    $this->NewLine();
  }
  if ($this->company['suburb'] != "") {
    $this->Text($ccol, $doc_Suburb, $c2col);
    $this->Text($c2col, $this->company['suburb'], $mcol);
    $this->NewLine();
  }
  $this->Font();
  $this->row = $adrline;
  $this->NewLine(3);
  $this->Text($mcol + 100, $txt_date);
  $this->Text($mcol + 180, Dates::_sqlToDate($myrow['tran_date']));
  $this->NewLine();
  $this->Text($mcol + 100, $doc_invoice_no);
  if (isset($myrow['trans_no']) && isset($myrow['reference'])) { // INV/CRE/STA
    if (Config::_get('print_useinvoicenumber') == 1) {
      $this->Text($mcol + 180, $myrow['trans_no']);
    } else {
      $this->Text($mcol + 180, $myrow['reference']);
    }
  }
  if ($this->pageNumber > 1 && !strstr($this->filename, "Bulk")) {
    $this->Text($this->endLine - 35, _("Page") . ' ' . $this->pageNumber);
  }
  $this->row = $iline1 - $this->lineHeight;
  $this->Font('bold');
  $this->Text($ccol, $doc_Charge_To . ':', $icol);
  $this->Font('');
  $this->row = $this->row - $this->lineHeight - 5;
  $temp      = $this->row;
  $name      = $myrow['DebtorName'];
  $addr      = (trim($branch['br_address']) != '') ? $branch['br_address'] : $addr = $myrow['address'];
  $this->Text($ccol, $name, $icol);
  $this->NewLine();
  $this->TextWrapLines($ccol, $icol - $ccol, $addr);
  if ($sales_order != null) {
    $this->row = $temp;
    if (isset($sales_order['delivery_address'])) {
      $this->TextWrapLines($mcol, $this->rightMargin - $mcol, $sales_order['delivery_address']);
    }
  }
  $this->row = $iline2 - $this->lineHeight - 1;
  $col       = $this->leftMargin;
  $this->TextWrap($col, $this->row, $width, $doc_Customers_Ref, 'C');
  $col += $width;
  $this->TextWrap($col, $this->row, $width, $doc_Our_Ref, 'C');
  $col += $width;
  $this->TextWrap($col, $this->row, $width, $doc_Your_VAT_no, 'C');
  $col += $width;
  $this->TextWrap($col, $this->row, $width, $doc_Our_Order_No, 'C');
  $col += $width;
  $this->TextWrap($col, $this->row, $width, $doc_Due_Date, 'C');
  $this->row = $iline3 - $this->lineHeight - 1;
  $col       = $this->leftMargin;
  if (isset($sales_order["customer_ref"])) {
    $this->TextWrap($col, $this->row, $width, $sales_order["customer_ref"], 'C');
  } elseif (isset($myrow["debtor_ref"])) {
    $this->TextWrap($col, $this->row, $width, $myrow["debtor_ref"], 'C');
  }
  $col += $width;
  $report_contact = (!empty($myrow['contact_name'])) ? $myrow['contact_name'] : $branch['contact_name'];
  $col += $width;
  $report_phone = $customer->accounts->phone;
  $this->TextWrap($col, $this->row, $width, $report_phone, 'C');
  $col += $width;
  $this->TextWrap($col, $this->row, $width, $report_phone = $customer->accounts->fax, 'C');
  $col += $width;
  $this->NewLine();
  $this->NewLine();
  $this->TextWrap($ccol, $this->row, $right - $ccol, "Email: " . $customer_branch_details['email']);
  $this->NewLine();
  $id     = $myrow['payment_terms'];
  $sql    = "SELECT terms FROM payment_terms WHERE terms_indicator='$id'";
  $result = DB::_query($sql, "could not get paymentterms");
  $row    = DB::_fetch($result);
  $str    = $row["terms"];
  $this->Font('italic');
  $this->TextWrap($ccol, $this->row, $right - $ccol, $doc_Payment_Terms . ": " . $str);
  $this->Font();
  $this->row = $iline5 - $this->lineHeight - 1;
  $this->Font('bold');
  $count              = count($this->headers);
  $this->cols[$count] = $right - 3;
  for ($i = 0; $i < $count; $i++) {
    $this->TextCol($i, $i + 1, $this->headers[$i], -2);
  }
  $this->Font();
  $this->Font('italic');
  $this->row = $iline7 - $this->lineHeight - 6;
  $this->row -= $this->lineHeight;
  $this->Font();
  $this->Font('bold');
  $this->TextWrap($ccol, $this->row, $right - $ccol, $statement_note, 'C');
  $this->row -= $this->lineHeight;
  $this->row -= $this->lineHeight;
  $this->Font();
  $this->Font('italic');
  if (isset($bankaccount['bank_name'])) {
    $txt = $payment_terms_note;
    $this->TextWrap($ccol, $this->row, $right - $ccol, $txt, 'C');
    $this->row -= $this->lineHeight;
    $txt = $doc_Bank . ": " . $bankaccount['bank_name'] . " " . $doc_Bank_Account . ": " . $bankaccount['bank_account_number'];
    $this->TextWrap($ccol, $this->row, $right - $ccol, $txt, 'C');
    $this->row -= $this->lineHeight;
  }
  if ($doc_Extra != "") {
    $this->TextWrap($ccol, $this->row, $right - $ccol, $doc_Extra, 'C');
    $this->row -= $this->lineHeight;
  }
  if ($this->params['comments'] != '') {
    $this->TextWrap($ccol, $this->row, $right - $ccol, $this->params['comments'], 'C');
    $this->row -= $this->lineHeight;
  }
  $this->TextWrapLines($ccol, $right - $ccol, $this->company['legal_text'], 'C');
  $this->Font();
  $temp = $iline6 - $this->lineHeight - 2;
