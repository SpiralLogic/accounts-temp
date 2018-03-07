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
  print_receipts();
  /**
   * @param $type
   * @param $trans_no
   *
   * @return \ADV\Core\DB\Query\Result|Array|bool
   */
  function get_receipt($type, $trans_no) {
    $sql
            = "SELECT debtor_trans.*,
                (debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
                debtor_trans.ov_freight_tax + debtor_trans.ov_discount) AS Total,
                 debtors.name AS DebtorName, debtors.debtor_ref,
                 debtors.curr_code, debtors.payment_terms, debtors.tax_id AS tax_id,
                 debtors.email, debtors.address
             FROM debtor_trans, debtors
                WHERE debtor_trans.debtor_id = debtors.debtor_id
                AND debtor_trans.type = " . DB::_escape($type) . "
                AND debtor_trans.trans_no = " . DB::_escape($trans_no);
    $result = DB::_query($sql, "The remittance cannot be retrieved");
    if (DB::_numRows($result) == 0) {
      return false;
    }
    return DB::_fetch($result);
  }

  /**
   * @param $debtor_id
   * @param $type
   * @param $trans_no
   *
   * @return null|PDOStatement
   */
  function get_allocations_for_receipt($debtor_id, $type, $trans_no) {
    $sql = Sales_Allocation::get_sql(
      "amt, trans.reference, trans.alloc",
      "trans.trans_no = alloc.trans_no_to
        AND trans.type = alloc.trans_type_to
        AND alloc.trans_no_from=$trans_no
        AND alloc.trans_type_from=$type
        AND trans.debtor_id=" . DB::_escape($debtor_id),
      "debtor_allocations as alloc"
    );
    $sql .= " ORDER BY trans_no";
    return DB::_query($sql, "Cannot retreive alloc to transactions");
  }

  function print_receipts() {
    $report_type = '\\ADV\\App\\Reports\\PDF';
    $from        = $_POST['PARAM_0'];
    $to          = $_POST['PARAM_1'];
    $currency    = $_POST['PARAM_2'];
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
    $cols = array(4, 85, 150, 225, 275, 360, 450, 515);
    // $headers in doctext.inc
    $aligns = array('left', 'left', 'left', 'left', 'right', 'right', 'right');
    $params = array('comments' => $comments);
    $cur    = DB_Company::_get_pref('curr_default');
    /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
    $rep           = new $report_type(_('RECEIPT'), "ReceiptBulk", $_POST['PARAM_0'] == $_POST['PARAM_1'] ? SA_SALESTRANSVIEW : SA_SALESBULKREP, User::_page_size());
    $rep->currency = $cur;
    $rep->Font();
    $rep->Info($params, $cols, null, $aligns);
    for ($i = $fno[0]; $i <= $tno[0]; $i++) {
      if ($fno[0] == $tno[0] && isset($fno[1])) {
        $types = array($fno[1]);
      } else {
        $types = array(ST_BANKDEPOSIT, ST_CUSTPAYMENT, ST_CUSTREFUND, ST_CUSTDELIVERY);
      }
      foreach ($types as $j) {
        $myrow = get_receipt($j, $i);
        if (!$myrow) {
          continue;
        }
        $baccount              = Bank_Account::get_default($myrow['curr_code']);
        $params['bankaccount'] = $baccount['id'];
        $rep->title            = _('RECEIPT');
        $rep->Header2($myrow, null, $myrow, $baccount, ST_CUSTPAYMENT);
        $result   = get_allocations_for_receipt($myrow['debtor_id'], $myrow['type'], $myrow['trans_no']);
        $linetype = true;
        $doctype  = ST_CUSTPAYMENT;
        extract($rep->getHeaderArray($doctype, false, $linetype));
        $total_allocated = 0;
        $rep->TextCol(0, 4, $doc_Towards, -2);
        $rep->NewLine(2);
        while ($myrow2 = DB::_fetch($result)) {
          $rep->TextCol(0, 1, SysTypes::$names[$myrow2['type']], -2);
          $rep->TextCol(1, 2, $myrow2['reference'], -2);
          $rep->TextCol(2, 3, Dates::_sqlToDate($myrow2['tran_date']), -2);
          $rep->TextCol(3, 4, Dates::_sqlToDate($myrow2['due_date']), -2);
          $rep->AmountCol(4, 5, $myrow2['Total'], $dec, -2);
          $rep->AmountCol(5, 6, $myrow2['Total'] - $myrow2['alloc'], $dec, -2);
          $rep->AmountCol(6, 7, $myrow2['amt'], $dec, -2);
          $total_allocated += $myrow2['amt'];
          $rep->NewLine(1);
          if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight)) {
            $rep->Header2($myrow, null, $myrow, $baccount, ST_CUSTPAYMENT);
          }
        }
        $rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
        $rep->TextCol(3, 6, $txt_total_allocated, -2);
        $rep->AmountCol(6, 7, $total_allocated, $dec, -2);
        $rep->NewLine();
        $rep->TextCol(3, 6, $txt_left_to_allocate, -2);
        $rep->AmountCol(6, 7, $myrow['Total'] - $total_allocated, $dec, -2);
        $rep->NewLine();
        $rep->Font('bold');
        $rep->TextCol(3, 6, $txt_total_payment, -2);
        $rep->AmountCol(6, 7, $myrow['Total'], $dec, -2);
        $words = Item_Price::toWords($myrow['Total'], ST_CUSTPAYMENT);
        if ($words != "") {
          $rep->NewLine(1);
          $rep->TextCol(0, 7, $myrow['curr_code'] . ": " . $words, -2);
        }
        $rep->Font();
        $rep->NewLine();
        $rep->TextCol(6, 7, $txt_received, -2);
        $rep->NewLine();
        $rep->TextCol(0, 2, $txt_by_Cheque, -2);
        $rep->TextCol(2, 4, "______________________________", -2);
        $rep->TextCol(4, 5, $txt_dated, -2);
        $rep->TextCol(5, 6, "__________________", -2);
        $rep->NewLine(1);
        $rep->TextCol(0, 2, $doc_Drawn, -2);
        $rep->TextCol(2, 4, "______________________________", -2);
        $rep->TextCol(4, 5, $doc_Drawn_Branch, -2);
        $rep->TextCol(5, 6, "__________________", -2);
        $rep->TextCol(6, 7, "__________________");
      }
    }
    $rep->End();
  }

