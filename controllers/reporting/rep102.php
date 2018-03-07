<?php
    use ADV\App\Dates;
    use ADV\Core\Config;
    use ADV\App\SysTypes;
    use ADV\App\User;
    use ADV\App\Debtor\Debtor;
    use ADV\Core\DB\DB;

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
    print_aged_customer_analysis();
    /**
     * @param $debtor_id
     * @param $to
     *
     * @return null|PDOStatement
     */
    function get_invoices($debtor_id, $to) {
        $todate    = Dates::_dateToSql($to);
        $past_due1 = DB_Company::_get_pref('past_due_days');
        $past_due2 = 2 * $past_due1;
        // Revomed allocated from sql
        $value = "(debtor_trans.ov_amount + debtor_trans.ov_gst + " . "debtor_trans.ov_freight + debtor_trans.ov_freight_tax + " . "debtor_trans.ov_discount)";
        $due   = "IF (debtor_trans.type=" . ST_SALESINVOICE . ",debtor_trans.due_date,debtor_trans.tran_date)";
        $sql   = "SELECT debtor_trans.type, debtor_trans.reference,
        debtor_trans.tran_date,
        $value as Balance,
        IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= 0,$value,0) AS Due,
        IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past_due1,$value,0) AS Overdue1,
        IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past_due2,$value,0) AS Overdue2

        FROM debtors,
            payment_terms,
            debtor_trans

        WHERE debtor_trans.type <> " . ST_CUSTDELIVERY . "
            AND debtors.payment_terms = payment_terms.terms_indicator
            AND debtors.debtor_id = debtor_trans.debtor_id
            AND debtor_trans.debtor_id = $debtor_id
            AND debtor_trans.tran_date <= '$todate'
            AND ABS(debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight + debtor_trans.ov_freight_tax + debtor_trans.ov_discount) > 0.004
            ORDER BY debtor_trans.tran_date";
        return DB::_query($sql, "The customer details could not be retrieved");
    }

    function print_aged_customer_analysis() {
        $to          = $_POST['PARAM_0'];
        $fromcust    = $_POST['PARAM_1'];
        $currency    = $_POST['PARAM_2'];
        $summaryOnly = $_POST['PARAM_3'];
        $no_zeros    = $_POST['PARAM_4'];
        $graphics    = $_POST['PARAM_5'];
        $comments    = $_POST['PARAM_6'];
        $destination = $_POST['PARAM_7'];
        if ($destination) {
            $report_type = '\\ADV\\App\\Reports\\Excel';
        } else {
            $report_type = '\\ADV\\App\\Reports\\PDF';
        }
        if ($graphics) {
            $pg = new Reports_Graph();
        }
        if ($fromcust == ALL_NUMERIC) {
            $from = _('All');
        } else {
            $from = Debtor::get_name($fromcust);
        }
        $dec = User::_price_dec();
        if ($summaryOnly == 1) {
            $summary = _('Summary Only');
        } else {
            $summary = _('Detailed Report');
        }
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
        $past_due1     = DB_Company::_get_pref('past_due_days');
        $past_due2     = 2 * $past_due1;
        $txt_now_due   = "1-" . $past_due1 . " " . _('Days');
        $txt_past_due1 = $past_due1 + 1 . "-" . $past_due2 . " " . _('Days');
        $txt_past_due2 = _('Over') . " " . $past_due2 . " " . _('Days');
        $cols          = [0, 100, 130, 190, 250, 320, 385, 450, 515];
        $headers       = [
            _('Customer'),
            '',
            '',
            _('Current'),
            $txt_now_due,
            $txt_past_due1,
            $txt_past_due2,
            _('Total Balance')
        ];
        $aligns        = ['left', 'left', 'left', 'right', 'right', 'right', 'right', 'right'];
        $params        = [
            0 => $comments,
            1 => [
                'text' => _('End Date'),
                'from' => $to,
                'to'   => ''
            ],
            2 => [
                'text' => _('Customer'),
                'from' => $from,
                'to'   => ''
            ],
            3 => [
                'text' => _('Currency'),
                'from' => $currency,
                'to'   => ''
            ],
            4 => [
                'text' => _('Type'),
                'from' => $summary,
                'to'   => ''
            ],
            5 => [
                'text' => _('Suppress Zeros'),
                'from' => $nozeros,
                'to'   => ''
            ]
        ];
        if ($convert) {
            $headers[2] = _('Currency');
        }
        /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
        $rep = new $report_type(_('Aged Customer Analysis'), "AgedCustomerAnalysis", SA_CUSTPAYMREP, User::_page_size());
        $rep->Font();
        $rep->Info($params, $cols, $headers, $aligns);
        $rep->Header();
        $total = [0, 0, 0, 0, 0];
        $sql   = "SELECT debtor_id, name, curr_code FROM debtors";
        if ($fromcust != ALL_NUMERIC) {
            $sql .= " WHERE debtor_id=" . DB::_escape($fromcust);
        }
        $sql .= " ORDER BY name";
        $result = DB::_query($sql, "The customers could not be retrieved");
        while ($myrow = DB::_fetch($result)) {
            if (!$convert && $currency != $myrow['curr_code']) {
                continue;
            }
            if ($convert) {
                $rate = Bank_Currency::exchange_rate_from_home($myrow['curr_code'], $to);
            } else {
                $rate = 1.0;
            }
            $custrec = Debtor::get_details($myrow['debtor_id'], $to);
            if (!$custrec) {
                continue;
            }
            foreach ($custrec as $i => $value) {
                $custrec[$i] *= $rate;
            }
            $str = [
                $custrec["Balance"] - $custrec["Due"],
                $custrec["Due"] - $custrec["Overdue1"],
                $custrec["Overdue1"] - $custrec["Overdue2"],
                $custrec["Overdue2"],
                $custrec["Balance"]
            ];
            if ($no_zeros && array_sum($str) == 0) {
                continue;
            }
            $rep->fontSize += 2;
            $rep->TextCol(0, 2, $myrow['name']);
            if ($convert) {
                $rep->TextCol(2, 3, $myrow['curr_code']);
            }
            $rep->fontSize -= 2;
            $total[0] += ($custrec["Balance"] - $custrec["Due"]);
            $total[1] += ($custrec["Due"] - $custrec["Overdue1"]);
            $total[2] += ($custrec["Overdue1"] - $custrec["Overdue2"]);
            $total[3] += $custrec["Overdue2"];
            $total[4] += $custrec["Balance"];
            for ($i = 0; $i < count($str); $i++) {
                $rep->AmountCol($i + 3, $i + 4, $str[$i], $dec);
            }
            $rep->NewLine(1, 2);
            if (!$summaryOnly) {
                $res = get_invoices($myrow['debtor_id'], $to);
                if (DB::_numRows($res) == 0) {
                    continue;
                }
                $rep->Line($rep->row + 4);
                while ($trans = DB::_fetch($res)) {
                    $rep->NewLine(1, 2);
                    $rep->TextCol(0, 1, SysTypes::$names[$trans['type']], -2);
                    $rep->TextCol(1, 2, $trans['reference'], -2);
                    $rep->DateCol(2, 3, $trans['tran_date'], true, -2);
                    if ($trans['type'] == ST_CUSTCREDIT || $trans['type'] == ST_CUSTPAYMENT || $trans['type'] == ST_CUSTREFUND || $trans['type'] == ST_BANKDEPOSIT
                    ) {
                        $trans['Balance'] *= -1;
                        $trans['Due'] *= -1;
                        $trans['Overdue1'] *= -1;
                        $trans['Overdue2'] *= -1;
                    }
                    foreach ($trans as $i => $value) {
                        $trans[$i] *= $rate;
                    }
                    $str = [
                        $trans["Balance"] - $trans["Due"],
                        $trans["Due"] - $trans["Overdue1"],
                        $trans["Overdue1"] - $trans["Overdue2"],
                        $trans["Overdue2"],
                        $trans["Balance"]
                    ];
                    for ($i = 0; $i < count($str); $i++) {
                        $rep->AmountCol($i + 3, $i + 4, $str[$i], $dec);
                    }
                }
                $rep->Line($rep->row - 8);
                $rep->NewLine(2);
            }
        }
        if ($summaryOnly) {
            $rep->Line($rep->row + 4);
            $rep->NewLine();
        }
        $rep->fontSize += 2;
        $rep->TextCol(0, 3, _('Grand Total'));
        $rep->fontSize -= 2;
        for ($i = 0; $i < count($total); $i++) {
            $rep->AmountCol($i + 3, $i + 4, $total[$i], $dec);
            if ($graphics && $i < count($total) - 1) {
                $pg->y[$i] = abs($total[$i]);
            }
        }
        $rep->Line($rep->row - 8);
        if ($graphics) {
            $pg->x              = [_('Current'), $txt_now_due, $txt_past_due1, $txt_past_due2];
            $pg->title          = $rep->title;
            $pg->axis_x         = _("Days");
            $pg->axis_y         = _("Amount");
            $pg->graphic_1      = $to;
            $pg->type           = $graphics;
            $pg->skin           = Config::_get('graphs_skin');
            $pg->built_in       = false;
            $pg->fontfile       = ROOT_DOC . "reporting/fonts/Vera.ttf";
            $pg->latin_notation = (User::_prefs()->dec_sep != ".");
            $filename           = PATH_COMPANY . "images/test.png";
            $pg->display($filename, true);
            $w = $pg->width / 1.5;
            $h = $pg->height / 1.5;
            $x = ($rep->pageWidth - $w) / 2;
            $rep->NewLine(2);
            if ($rep->row - $h < $rep->bottomMargin) {
                $rep->Header();
            }
            $rep->AddImage($filename, $x, $rep->row - $h, $w, $h);
        }
        $rep->NewLine();
        $rep->End();
    }

