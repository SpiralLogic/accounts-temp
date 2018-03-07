<?php
    use ADV\App\User;
    use ADV\Core\DB\DB;
    use ADV\Core\Num;
    use ADV\App\Debtor\Debtor;
    use ADV\App\Dates;

    /**********************************************************************
     * Copyright (C) Advanced Group PTY LTD
     * Released under the terms of the GNU General Public License, GPL,
     * as published by the Free Software Foundation, either version 3
     * of the License, or (at your option) any later version.
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
     * See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
     ***********************************************************************/
    // trial_inquiry_controls();
    print_customer_balances();
    /**
     * @param $debtorno
     * @param $to
     * @param $convert
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    function get_open_balance($debtorno, $to, $convert) {
        $to  = Dates::_dateToSql($to);
        $sql = "SELECT SUM(IF(debtor_trans.type = " . ST_SALESINVOICE . ", (debtor_trans.ov_amount + debtor_trans.ov_gst +
     debtor_trans.ov_freight + debtor_trans.ov_freight_tax + debtor_trans.ov_discount)";
        if ($convert) {
            $sql .= " * rate";
        }
        $sql .= ", 0)) AS charges,
     SUM(IF(debtor_trans.type <> " . ST_SALESINVOICE . ", (debtor_trans.ov_amount + debtor_trans.ov_gst +
     debtor_trans.ov_freight + debtor_trans.ov_freight_tax + debtor_trans.ov_discount)";
        if ($convert) {
            $sql .= " * rate";
        }
        $sql .= " * -1, 0)) AS credits
        FROM debtor_trans
     WHERE debtor_trans.tran_date < '$to'
        AND debtor_trans.debtor_id = " . DB::_escape($debtorno) . "
        AND debtor_trans.type <> " . ST_CUSTDELIVERY . " GROUP BY debtor_id";
        $result = DB::_query($sql, "No transactions were returned");
        return DB::_fetch($result);
    }

    /**
     * @param $debtorno
     * @param $from
     * @param $to
     *
     * @return null|PDOStatement
     */
    function get_transactions($debtorno, $from, $to) {
        $from = Dates::_dateToSql($from);
        $to   = Dates::_dateToSql($to);
        $sql  = "SELECT debtor_trans.*,
        (debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
        debtor_trans.ov_freight_tax + debtor_trans.ov_discount)
        AS TotalAmount, debtor_trans.alloc AS Allocated,
        ((debtor_trans.type = " . ST_SALESINVOICE . ")
        AND debtor_trans.due_date < '$to') AS OverDue
     FROM debtor_trans
     WHERE debtor_trans.tran_date >= '$from'
        AND debtor_trans.tran_date <= '$to'
        AND debtor_trans.debtor_id = " . DB::_escape($debtorno) . "
        AND debtor_trans.type <> " . ST_CUSTDELIVERY . "
     ORDER BY debtor_trans.tran_date";
        return DB::_query($sql, "No transactions were returned");
    }

    function print_customer_balances() {
        $from        = $_POST['PARAM_0'];
        $to          = $_POST['PARAM_1'];
        $fromcust    = $_POST['PARAM_2'];
        $currency    = $_POST['PARAM_3'];
        $no_zeros    = $_POST['PARAM_4'];
        $comments    = $_POST['PARAM_5'];
        $destination = $_POST['PARAM_6'];
        if ($destination) {
            $report_type = '\\ADV\\App\\Reports\\Excel';
        } else {
            $report_type = '\\ADV\\App\\Reports\\PDF';
        }
        if ($fromcust == ALL_NUMERIC) {
            $cust = _('All');
        } else {
            $cust = Debtor::get_name($fromcust);
        }
        $dec = User::_price_dec();
        if ($currency == ALL_TEXT) {
            $convert  = true;
            $currency = _('Balances in Home Currency');
        } else {
            $convert = false;
        }
        if ($no_zeros) {
            $nozeros = _('Yes');
        } else {
            $nozeros = _('No');
        }
        $cols    = [0, 100, 130, 190, 250, 385, 450, 515];
        $headers = [
            _(''),
            _(''),
            _(''),
            _(''),
            _('Charges'),
            _('Credits'),
            _('Outstanding')
        ];
        $aligns  = ['left', 'left', 'left', 'left', 'right', 'right', 'right'];
        $params  = [
            0 => $comments,
            1 => ['text' => _('Period'), 'from' => $from, 'to' => $to],
            2 => ['text' => _('Customer'), 'from' => $cust, 'to' => ''],
            3 => ['text' => _('Currency'), 'from' => $currency, 'to' => ''],
            4 => ['text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => '']
        ];
        /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
        $rep = new $report_type(_('Customer Balances'), "CustomerBalances", SA_CUSTPAYMREP, User::_page_size());
        $rep->Font();
        $rep->Info($params, $cols, $headers, $aligns);
        $rep->Header();
        $grandtotal = [0, 0, 0, 0];
        $sql        = "SELECT debtor_id , name, curr_code FROM debtors ";
        if ($fromcust != ALL_NUMERIC) {
            $sql .= "WHERE debtor_id=" . DB::_escape($fromcust);
        }
        $sql .= " ORDER BY name";
        $result    = DB::_query($sql, "The customers could not be retrieved");
        $num_lines = 0;
        while ($myrow = DB::_fetch($result)) {
            if (!$convert && $currency != $myrow['curr_code']) {
                continue;
            }
            /*   $res = get_transactions($myrow['debtor_id'], $from, $to);
               if ($no_zeros && DB::_numRows($res) == 0) {
                 continue;
               }
            */
            $bal         = get_open_balance($myrow['debtor_id'], $from, $convert);
            $charges     = Num::_round($bal['charges'], 2);
            $credits     = Num::_round($bal['credits'], 2);
            $outstanding = Num::_round($charges + $credits, 2);
            if ($outstanding <= 0) {
                continue;
            }
            $init = [$charges, $credits, $outstanding];
            $num_lines++;
            $rep->fontSize += 2;
            $rep->TextCol(0, 2, $myrow['name']);
            if ($convert && $currency != $myrow['curr_code']) {
                //$rep->TextCol(2, 3, $myrow['curr_code']);
            }
            $rep->fontSize -= 2;
            //$rep->TextCol(3, 4,	_("Open Balance"));
            //$rep->AmountCol(4, 5, $charges, $dec);
            //$rep->AmountCol(5, 6, $credits, $dec);
            //$rep->AmountCol(6, 7, $allocated, $dec);
            //$rep->AmountCol(7, 8, $outstanding, $dec);
            $total = [0, 0, 0, 0];
            for ($i = 0; $i < 32; $i++) {
                $total[$i] += $init[$i];
                $grandtotal[$i] += $init[$i];
            }
            /*   while ($trans = DB::_fetch($res)) {
                 if ($no_zeros && $trans['TotalAmount'] == 0 && $trans['Allocated'] == 0) {
                   continue;
                 }
                 //$rep->NewLine(1, 2);
                 //$rep->TextCol(0, 1, SysTypes::$names[$trans['type']]);
                 //$rep->TextCol(1, 2,	$trans['reference']);
                 //$rep->DateCol(2, 3,	$trans['tran_date'], true);
                 if ($trans['type'] == ST_SALESINVOICE) //	$rep->DateCol(3, 4,	$trans['due_date'], true);
                 {
                   $item[0] = $item[1] = 0.0;
                 }
                 if ($convert) {
                   $rate = $trans['rate'];
                 } else {
                   $rate = 1.0;
                 }
                 if ($trans['type'] == ST_CUSTCREDIT || $trans['type'] == ST_CUSTPAYMENT || $trans['type'] == ST_BANKDEPOSIT) {
                   $trans['TotalAmount'] *= -1;
                 }
                 if ($trans['TotalAmount'] > 0.0) {
                   $item[0] = Num::_round(abs($trans['TotalAmount']) * $rate, $dec);
                   //		$rep->AmountCol(4, 5, $item[0], $dec);
                 } else {
                   $item[1] = Num::_round(Abs($trans['TotalAmount']) * $rate, $dec);
                   //		$rep->AmountCol(5, 6, $item[1], $dec);
                 }
                 $item[2] = Num::_round($trans['Allocated'] * $rate, $dec);
                 //	$rep->AmountCol(6, 7, $item[2], $dec);
                 /*
                              if ($trans['type'] == 10)
                                $item[3] = ($trans['TotalAmount'] - $trans['Allocated']) * $rate;
                              else
                                $item[3] = ($trans['TotalAmount'] + $trans['Allocated']) * $rate;

                 if ($trans['type'] == ST_SALESINVOICE || $trans['type'] == ST_BANKPAYMENT) {
                   $item[3] = $item[0] + $item[1] - $item[2];
                 } else {
                   $item[3] = $item[0] - $item[1] + $item[2];
                 }
                 //	$rep->AmountCol(7, 8, $item[3], $dec);
                 for ($i = 0; $i < 4; $i++) {
                   $total[$i] += $item[$i];
                   $grandtotal[$i] += $item[$i];
                 }
               } */
            for ($i = 0; $i < 3; $i++) {
                $rep->AmountCol($i + 4, $i + 5, $total[$i], 2);
            }
            $rep->NewLine(1, 2);
        }
        $rep->fontSize += 2;
        $rep->TextCol(0, 3, _('Grand Total'));
        $rep->fontSize -= 2;
        for ($i = 0; $i < 3; $i++) {
            $rep->AmountCol($i + 4, $i + 5, $grandtotal[$i], $dec);
        }
        $rep->Line($rep->row - 4);
        $rep->NewLine();
        $rep->End();
    }

