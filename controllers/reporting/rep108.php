<?php
  use ADV\Core\DB\DB;
  use ADV\App\Dates;
  use ADV\Core\Num;
  use ADV\App\Debtor\Debtor;
  use ADV\App\User;
  use ADV\App\SysTypes;
  use ADV\Core\Input\Input;

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
  print_statements();
  /**
   * @param $debtorno
   * @param $month
   * @param $inc_all
   *
   * @return null|PDOStatement
   */
  function get_transactions($debtorno, $month, $inc_all) {
    $dateend   = date('Y-m-d', mktime(0, 0, 0, date('n') - $month, 0));
    $datestart = date('Y-m-d', mktime(0, 0, 0, date('n') - $month - 1, 1));
    $sql       = "SELECT d.*," . "((d.ov_amount + d.ov_gst + d.ov_freight + d.ov_freight_tax + d.ov_discount) * IF(d.type = " . ST_SALESINVOICE . " OR d.type = " . ST_BANKPAYMENT . ",1,-1) ) AS TotalAmount,";
    $sql .= "IF(d.type <> 10 AND d.type <> 1,-SUM(d.alloc), if(a.amt is null,0,SUM(a.amt))*if(b.tran_date > '$dateend',0,1)) AS Allocated, ";
    $sql
      .= "( d.due_date < '$datestart' AND (b.tran_date < '$datestart' or b.tran_date is null)) AS OverDue
                            FROM debtor_trans d
                            LEFT  outer JOIN debtor_allocations a ON d.trans_no = a.trans_no_to AND d.type=a.trans_type_to
                            LEFT JOIN debtor_trans b ON b.trans_no = a.trans_no_from AND b.type=a.trans_type_from
                            WHERE  d.debtor_id =  " . DB::_escape($debtorno) . "
                               AND d.type <> " . ST_CUSTDELIVERY . "
                               AND ((d.ov_amount + d.ov_gst + d.ov_freight + d.ov_freight_tax + d.ov_discount != 0
                                AND d.due_date <= '$dateend')  )
                               GROUP BY d.debtor_id ";
    $sql .= ", d.trans_no,	d.type";
    $sql .= " ORDER BY  d.tran_date,	d.type,	d.branch_id";
    return DB::_query($sql, "No transactions were returned");
  }

  /**
   * @param $no
   *
   * @return mixed
   */
  function getTransactionPO($no) {
    $sql    = "SELECT customer_ref FROM sales_orders WHERE order_no=" . DB::_escape($no);
    $result = DB::_query($sql, "Could not retrieve any branches");
    $myrow  = DB::_fetchAssoc($result);
    return $myrow['customer_ref'];
  }

  function print_statements() {
    $trans_type_string   = [
      ST_SALESINVOICE => 'Invoice',
      ST_CUSTCREDIT   => 'Credit',
      ST_CUSTPAYMENT  => 'Payment',
      ST_BANKDEPOSIT  => 'Payment',
      ST_BANKPAYMENT  => 'Refund'
    ];
    $report_type         = '\\ADV\\App\\Reports\\PDF';
    $txt_statement       = "Statement";
    $txt_opening_balance = 'Opening Balance';
    $doc_as_of           = "as of";
    $customer            = Input::_postGet('PARAM_0', Input::NUMERIC, 0);
    $email               = Input::_postGet('PARAM_1', Input::NUMERIC);
    $email               = $email ? : Input::_postGet('Email', Input::STRING, 0);
    $month               = Input::_postGet('PARAM_2', Input::NUMERIC, 0);
    $inc_all             = Input::_postGet('PARAM_3', Input::NUMERIC, 0);
    $inc_payments        = Input::_postGet('PARAM_4', Input::NUMERIC, 1);
    $inc_negatives       = Input::_postGet('PARAM_5', Input::NUMERIC, 0);
    $comments            = Input::_postGet('PARAM_6', Input::STRING, '');
    $curency             = Input::_postGet('PARAM_7', Input::STRING, '');
    $doctype             = ST_STATEMENT;
    $txt_outstanding     = $txt_over = $txt_days = $txt_current = $txt_total_balance = null;
    $dec                 = User::_price_dec();
    $cols                = array(
      5, //Transaction
      65, //Invoice
      105, //PO#
      175, //Date
      215, //Due
      295, //Debits
      345, //Credits
      390, //Outstanding
      465, //Balance
      510,
      460 //line
    );
    $aligns              = array('left', 'left', 'left', 'center', 'center', 'left', 'left', 'left', 'left');
    $params              = array('comments' => $comments);
    $cur                 = DB_Company::_get_pref('curr_default');
    $past_due1           = DB_Company::_get_pref('past_due_days');
    $past_due2           = 2 * $past_due1;
    if ($email == 0) {
      /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
      $rep           = new $report_type(_('STATEMENT'), "StatementBulk", SA_CUSTSTATREP, User::_page_size());
      $rep->currency = $cur;
      $rep->Font();
      $rep->Info($params, $cols, null, $aligns);
    }
    $sql
      = 'SELECT DISTINCT db.*,c.name AS DebtorName,c.tax_id,a.email,c.curr_code, c.payment_terms,
CONCAT(a.br_address,CHARACTER(13),a.city," ",a.state," ",a.postcode) as address FROM debtor_balances db, branches a,
        debtors c WHERE db.debtor_id = a.debtor_id AND c.debtor_id=db.debtor_id AND a.branch_ref LIKE ' . DB::_quote(Debtor_Account::ACCOUNTS);
    if ($customer > 0) {
      $sql .= " AND c.debtor_id = " . DB::_escape($customer);
    } else {
      $sql .= " ORDER by name";
    }
    $result = DB::_query($sql, "The customers could not be retrieved");
    while ($myrow = DB::_fetch($result)) {
      $date = $myrow['tran_date'] = date('Y-m-1', strtotime("now - $month months"));
      if ($month == -1) {
        $date = $myrow['tran_date'] = date('Y-m-1', strtotime("now +1 month"));
      }
      $myrow['order_'] = "";
      $customer_record = Debtor::get_details($myrow['debtor_id'], mktime(0, 0, 0, date('n') - $month, 0), true);
      if (round($customer_record["Balance"], 2) == 0) {
        continue;
      }
      if (!$inc_negatives && $customer_record["Balance"] < 0) {
        continue;
      }
      $baccount              = Bank_Account::get_default($myrow['curr_code']);
      $params['bankaccount'] = $baccount['id'];
      $trans_rows            = get_transactions($myrow['debtor_id'], $month, $inc_all);
      if ((DB::_numRows($trans_rows) == 0)) {
        continue;
      }
      $transactions = [];
      $branch       = $openingbalance = $balance = 0;
      while ($transaction = DB::_fetchAssoc($trans_rows)) {
        $balance += $transaction['TotalAmount'] - $transaction['Allocated'];
        if (!$branch) {
          $branch = $transaction['branch_id'];
        }
        if ($transaction['OverDue'] && !$inc_all) {
          $openingbalance += abs($transaction["TotalAmount"] - $transaction["Allocated"]) * (in_array($transaction['type'], [ST_SALESINVOICE, ST_BANKPAYMENT]) ? 1 : -1);
          continue;
        }
        $transactions[] = $transaction;
      }
      if (!$inc_negatives && $balance <= 0) {
        continue;
      }
      if ($email == 1) {
        $rep           = new $report_type("", "", SA_CUSTSTATREP, User::_page_size());
        $rep->currency = $cur;
        $rep->Font();
        $rep->title    = _('STATEMENT');
        $rep->filename = "Statement" . $myrow['debtor_id'] . ".pdf";
        $rep->Info($params, $cols, null, $aligns);
      }
      $rep->Header2($myrow, Sales_Branch::get($branch), null, $baccount, ST_STATEMENT);
      $rep->NewLine();
      extract($rep->getHeaderArray($doctype));
      $balance       = 0;
      $rep->currency = $cur;
      $rep->Font();
      $rep->Info($params, $cols, null, $aligns);
      if ($openingbalance && !$inc_all) {
        $rep->TextCol(0, 8, $txt_opening_balance);
        $rep->TextCol(8, 9, Num::_format($openingbalance, $dec));
        $rep->NewLine(2);
        $balance         = $openingbalance;
        $display_balance = Num::_format($balance, $dec);
      }
      foreach ($transactions as $i => $trans) {
        if (!$inc_payments && in_array($trans['type'], [ST_CUSTPAYMENT, ST_BANKDEPOSIT])) {
          //  continue;
        }
        $display_total       = Num::_format(abs($trans["TotalAmount"]), $dec);
        $outstanding         = abs($trans["TotalAmount"] - $trans["Allocated"]);
        $display_outstanding = Num::_format($outstanding, $dec);
        if (!$inc_payments && $display_outstanding == 0) {
          continue;
        }
        if (!$inc_all || !$inc_payments) {
          $balance += (in_array($trans['type'], [ST_SALESINVOICE, ST_BANKPAYMENT])) ? $outstanding : -$outstanding;
        } else {
          $balance += $trans["TotalAmount"];
        }
        $display_balance = Num::_format($balance, $dec);
        $rep->TextCol(0, 1, $trans_type_string[$trans['type']], -2);
        $ledgerside = (in_array($trans['type'], [ST_SALESINVOICE, ST_BANKPAYMENT]));
        if ($ledgerside) {
          $rep->Font('bold');
        }
        $rep->TextCol(1, 2, $trans['reference'], -2);
        if ($ledgerside) {
          $rep->TextCol(2, 3, getTransactionPO($trans['order_']), -2);
        }
        $rep->Font();
        $rep->TextCol(3, 4, Dates::_sqlToDate($trans['tran_date']), -2);
        if ($ledgerside) {
          $rep->TextCol(4, 5, Dates::_sqlToDate($trans['due_date']), -2);
        }
        if ($ledgerside && isset($display_total)) {
          $rep->TextCol(5, 6, $display_total, -2);
        } elseif (isset($display_total)) {
          $rep->TextCol(6, 7, $display_total, -2);
        }
        if (isset($display_outstanding)) {
          $rep->TextCol(7, 8, $display_outstanding, -2);
        }
        $rep->TextCol(8, 9, $display_balance, -2);
        $rep->NewLine();
        $gaptoleave = ((count($transactions) - $i) > 5) ? 10 : 15;
        if ($rep->row < $rep->bottomMargin + ($gaptoleave * $rep->lineHeight)) {
          $rep->Header2($myrow, null, null, $baccount, ST_STATEMENT);
        }
      }
      $rep->Font('bold');
      $txt_current   = "Current";
      $txt_now_due   = "31-60  Days";
      $txt_past_due1 = "61-90  Days";
      $txt_past_due2 = "90+ Days";
      $str           = array($txt_past_due2, $txt_past_due1, $txt_now_due, $txt_current, $txt_total_balance);
      $str2          = array(
        Num::_format($customer_record["Overdue2"], $dec),
        Num::_format(($customer_record["Overdue1"] - $customer_record["Overdue2"]), $dec),
        Num::_format(($customer_record["Due"] - $customer_record["Overdue1"]), $dec),
        Num::_format(($balance - $customer_record["Due"]), $dec),
        $display_balance
      );
      $col           = array(
        70,
        150,
        250,
        350,
        450,
        610
      );
      $rep->row      = $rep->bottomMargin + (13 * $rep->lineHeight - 6);
      if ($customer_record["Balance"] > 0 && $customer_record["Due"] - $customer_record["Overdue1"] < $customer_record["Balance"]) {
        $rep->SetTextColor(255, 0, 0);
        $rep->fontSize += 4;
        $rep->Font('bold');
        $rep->TextWrapLines(0, $rep->pageWidth - 50, 'YOUR ACCOUNT IS OVERDUE, IMMEDIATE PAYMENT REQUIRED!', 'C');
        $rep->fontSize -= 4;
        $rep->SetTextColor(0, 0, 0);
      }
      $rep->NewLine();
      for ($i = 0; $i < 5; $i++) {
        $rep->TextWrap($col[$i], $rep->row, $col[$i + 1] - $col[$i], $str[$i], 'center');
      }
      $rep->Font();
      $rep->NewLine();
      for ($i = 0; $i < 5; $i++) {
        $rep->TextWrap($col[$i], $rep->row, $col[$i + 1] - $col[$i], $str2[$i], 'center');
      }
      if ($email == 1) {
        $rep->End($email, $txt_statement . " " . $doc_as_of . " " . Dates::_sqlToDate($date), $myrow, ST_STATEMENT);
      }
    }
    if ($email == 0) {
      $rep->End();
    }
  }

