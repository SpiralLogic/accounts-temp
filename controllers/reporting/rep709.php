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

  print_tax_report();
  /**
   * @param $from
   * @param $to
   *
   * @return null|PDOStatement
   */
  function getTaxTransactions($from, $to) {
    $fromdate = Dates::_dateToSql($from);
    $todate   = Dates::_dateToSql($to);
    $sql
              = "SELECT taxrec.*, taxrec.amount*ex_rate AS amount,
     taxrec.net_amount*ex_rate AS net_amount,
                IF(ISNULL(supp.name), debt.name, supp.name) as name,
                branch.br_name
        FROM trans_tax_details taxrec
        LEFT JOIN creditor_trans strans
            ON taxrec.trans_no=strans.trans_no AND taxrec.trans_type=strans.type
        LEFT JOIN suppliers as supp ON strans.creditor_id=supp.creditor_id
        LEFT JOIN debtor_trans dtrans
            ON taxrec.trans_no=dtrans.trans_no AND taxrec.trans_type=dtrans.type
        LEFT JOIN debtors as debt ON dtrans.debtor_id=debt.debtor_id
        LEFT JOIN branches as branch ON dtrans.branch_id=branch.branch_id
        WHERE (taxrec.amount <> 0 OR taxrec.net_amount <> 0)
            AND taxrec.trans_type <> " . ST_CUSTDELIVERY . "
            AND taxrec.tran_date >= '$fromdate'
            AND taxrec.tran_date <= '$todate'
        ORDER BY taxrec.tran_date";
    //Event::error($sql);
    return DB::_query($sql, "No transactions were returned");
  }

  /**
   * @return null|PDOStatement
   */
  function getTaxTypes() {
    $sql = "SELECT * FROM tax_types ORDER BY id";

    return DB::_query($sql, "No transactions were returned");
  }

  /**
   * @param $id
   *
   * @return \ADV\Core\DB\Query\Result|Array
   */
  function getTaxInfo($id) {
    $sql    = "SELECT * FROM tax_types WHERE id=$id";
    $result = DB::_query($sql, "No transactions were returned");

    return DB::_fetch($result);
  }

  function print_tax_report() {
    global $trans_dir, $Hooks;
    $from        = $_POST['PARAM_0'];
    $to          = $_POST['PARAM_1'];
    $summaryOnly = $_POST['PARAM_2'];
    $comments    = $_POST['PARAM_3'];
    $destination = $_POST['PARAM_4'];
    if ($destination) {

      $report_type = '\\ADV\\App\\Reports\\Excel';
    } else {

      $report_type = '\\ADV\\App\\Reports\\PDF';
    }
    $dec = User::_price_dec();
    /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
    $rep = new $report_type(_('Tax Report'), "TaxReport", SA_TAXREP, User::_page_size());
    if ($summaryOnly == 1) {
      $summary = _('Summary Only');
    } else {
      $summary = _('Detailed Report');
    }
    $res   = getTaxTypes();
    $taxes = [];
    while ($tax = DB::_fetch($res)) {
      $taxes[$tax['id']] = array('in' => 0, 'out' => 0, 'taxin' => 0, 'taxout' => 0);
    }
    $params  = array(
      0 => $comments,
      1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
      2 => array('text' => _('Type'), 'from' => $summary, 'to' => '')
    );
    $cols    = array(0, 100, 130, 180, 290, 370, 420, 470, 520);
    $headers = array(
      _('Trans Type'),
      _('Ref'),
      _('Date'),
      _('Name'),
      _('Branch Name'),
      _('Net'),
      _('Rate'),
      _('Tax')
    );
    $aligns  = array('left', 'left', 'left', 'left', 'left', 'right', 'right', 'right');
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    if (!$summaryOnly) {
      $rep->Header();
    }
    $totalnet     = 0.0;
    $totaltax     = 0.0;
    $transactions = getTaxTransactions($from, $to);
    while ($trans = DB::_fetch($transactions)) {
      if (in_array($trans['trans_type'], array(ST_CUSTCREDIT, ST_SUPPINVOICE))) {
        $trans['net_amount'] *= -1;
        $trans['amount'] *= -1;
      }
      if (!$summaryOnly) {
        $rep->TextCol(0, 1, SysTypes::$names[$trans['trans_type']]);
        if ($trans['memo'] == '') {
          $trans['memo'] = Ref::get($trans['trans_type'], $trans['trans_no']);
        }
        $rep->TextCol(1, 2, $trans['memo']);
        $rep->DateCol(2, 3, $trans['tran_date'], true);
        $rep->TextCol(3, 4, $trans['name']);
        $rep->TextCol(4, 5, $trans['br_name']);
        $rep->AmountCol(5, 6, $trans['net_amount'], $dec);
        $rep->AmountCol(6, 7, $trans['rate'], $dec);
        $rep->AmountCol(7, 8, $trans['amount'], $dec);
        $rep->NewLine();
        if ($rep->row < $rep->bottomMargin + $rep->lineHeight) {
          $rep->Line($rep->row - 2);
          $rep->Header();
        }
      }
      if ($trans['trans_type'] == ST_JOURNAL && $trans['amount'] < 0) {
        $taxes[$trans['tax_type_id']]['taxout'] -= $trans['amount'];
        $taxes[$trans['tax_type_id']]['out'] -= $trans['net_amount'];
      } elseif (in_array($trans['trans_type'], array(ST_BANKDEPOSIT, ST_SALESINVOICE, ST_CUSTCREDIT))) {
        $taxes[$trans['tax_type_id']]['taxout'] += $trans['amount'];
        $taxes[$trans['tax_type_id']]['out'] += $trans['net_amount'];
      } else {
        $taxes[$trans['tax_type_id']]['taxin'] += $trans['amount'];
        $taxes[$trans['tax_type_id']]['in'] += $trans['net_amount'];
      }
      $totalnet += $trans['net_amount'];
      $totaltax += $trans['amount'];
    }
    // Summary
    $cols2    = array(0, 100, 180, 260, 340, 420, 500);
    $headers2 = array(_('Tax Rate'), _('Outputs'), _('Output Tax'), _('Inputs'), _('Input Tax'), _('Net Tax'));
    $aligns2  = array('left', 'right', 'right', 'right', 'right', 'right', 'right');
    $rep->Info($params, $cols2, $headers2, $aligns2);
    //for ($i = 0; $i < count($cols2); $i++)
    //	$rep->cols[$i] = $rep->leftMargin + $cols2[$i];
    //$rep->numcols = count($headers2);
    //$rep->headers = $headers2;
    //$rep->aligns = $aligns2;
    $rep->Header();
    $taxtotal = 0;
    foreach ($taxes as $id => $sum) {
      $tx = getTaxInfo($id);
      $rep->TextCol(0, 1, $tx['name'] . " " . Num::_format($tx['rate'], $dec) . "%");
      $rep->AmountCol(1, 2, $sum['out'], $dec);
      $rep->AmountCol(2, 3, $sum['taxout'], $dec);
      $rep->AmountCol(3, 4, $sum['in'], $dec);
      $rep->AmountCol(4, 5, $sum['taxin'], $dec);
      $rep->AmountCol(5, 6, $sum['taxout'] + $sum['taxin'], $dec);
      $taxtotal += $sum['taxout'] + $sum['taxin'];
      $rep->NewLine();
    }
    $rep->Font('bold');
    $rep->NewLine();
    $rep->Line($rep->row + $rep->lineHeight);
    $rep->TextCol(3, 5, _("Total payable or refund"));
    $rep->AmountCol(5, 6, $taxtotal, $dec);
    $rep->Line($rep->row - 5);
    $rep->Font();
    $rep->NewLine();
    if (method_exists($Hooks, 'TaxFunction')) {
      $Hooks->TaxFunction();
    }
    $rep->End();
  }

