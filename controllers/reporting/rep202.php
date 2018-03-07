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
  print_aged_supplier_analysis();
  /**
   * @param $creditor_id
   * @param $to
   *
   * @return null|PDOStatement
   */
  function get_invoices($creditor_id, $to) {
    $todate    = Dates::_dateToSql($to);
    $past_due1 = DB_Company::_get_pref('past_due_days');
    $past_due2 = 2 * $past_due1;
    // Revomed allocated from sql
    $value = "(creditor_trans.ov_amount + creditor_trans.ov_gst + creditor_trans.ov_discount)";
    $due   = "IF (creditor_trans.type=" . ST_SUPPINVOICE . " OR creditor_trans.type=" . ST_SUPPCREDIT . ",creditor_trans.due_date,creditor_trans.tran_date)";
    $sql
           = "SELECT creditor_trans.type,
        creditor_trans.reference,
        creditor_trans.tran_date,
        $value as Balance,
        IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= 0,$value,0) AS Due,
        IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past_due1,$value,0) AS Overdue1,
        IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past_due2,$value,0) AS Overdue2

        FROM suppliers,
            payment_terms,
            creditor_trans

         WHERE suppliers.payment_terms = payment_terms.terms_indicator
            AND suppliers.creditor_id = creditor_trans.creditor_id
            AND creditor_trans.creditor_id = $creditor_id
            AND creditor_trans.tran_date <= '$todate'
            AND ABS(creditor_trans.ov_amount + creditor_trans.ov_gst + creditor_trans.ov_discount) > 0.004
            ORDER BY creditor_trans.tran_date";
    return DB::_query($sql, "The supplier details could not be retrieved");
  }

  function print_aged_supplier_analysis() {
    $to          = $_POST['PARAM_0'];
    $fromsupp    = $_POST['PARAM_1'];
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
    if ($fromsupp == ALL_NUMERIC) {
      $from = _('All');
    } else {
      $from = Creditor::get_name($fromsupp);
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
    $cols          = array(0, 100, 130, 190, 250, 320, 385, 450, 515);
    $headers       = array(
      _('Supplier'),
      '',
      '',
      _('Current'),
      $txt_now_due,
      $txt_past_due1,
      $txt_past_due2,
      _('Total Balance')
    );
    $aligns        = array('left', 'left', 'left', 'right', 'right', 'right', 'right', 'right');
    $params        = array(
      0 => $comments,
      1 => array(
        'text' => _('End Date'),
        'from' => $to,
        'to'   => ''
      ),
      2 => array(
        'text' => _('Supplier'),
        'from' => $from,
        'to'   => ''
      ),
      3 => array(
        'text' => _('Currency'),
        'from' => $currency,
        'to'   => ''
      ),
      4 => array(
        'text' => _('Type'),
        'from' => $summary,
        'to'   => ''
      ),
      5 => array(
        'text' => _('Suppress Zeros'),
        'from' => $nozeros,
        'to'   => ''
      )
    );
    if ($convert) {
      $headers[2] = _('currency');
    }
    /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
    $rep = new $report_type(_('Aged Supplier Analysis'), "AgedSupplierAnalysis", SA_SUPPLIERANALYTIC, User::_page_size());
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();
    $total         = [];
    $total[0]      = $total[1] = $total[2] = $total[3] = $total[4] = 0.0;
    $past_due1     = DB_Company::_get_pref('past_due_days');
    $past_due2     = 2 * $past_due1;
    $txt_now_due   = "1-" . $past_due1 . " " . _('Days');
    $txt_past_due1 = $past_due1 + 1 . "-" . $past_due2 . " " . _('Days');
    $txt_past_due2 = _('Over') . " " . $past_due2 . " " . _('Days');
    $sql           = "SELECT creditor_id, name AS name, curr_code FROM suppliers";
    if ($fromsupp != ALL_NUMERIC) {
      $sql .= " WHERE creditor_id=" . DB::_escape($fromsupp);
    }
    $sql .= " ORDER BY name";
    $result = DB::_query($sql, "The suppliers could not be retrieved");
    while ($myrow = DB::_fetch($result)) {
      if (!$convert && $currency != $myrow['curr_code']) {
        continue;
      }
      if ($convert) {
        $rate = Bank_Currency::exchange_rate_from_home($myrow['curr_code'], $to);
      } else {
        $rate = 1.0;
      }
      $supprec = Creditor::get_to_trans($myrow['creditor_id'], $to);
      foreach ($supprec as $i => $value) {
        $supprec[$i] *= $rate;
      }
      $str = array(
        $supprec["Balance"] - $supprec["Due"],
        $supprec["Due"] - $supprec["Overdue1"],
        $supprec["Overdue1"] - $supprec["Overdue2"],
        $supprec["Overdue2"],
        $supprec["Balance"]
      );
      if ($no_zeros && array_sum($str) == 0) {
        continue;
      }
      $rep->fontSize += 2;
      $rep->TextCol(0, 2, $myrow['name']);
      if ($convert) {
        $rep->TextCol(2, 3, $myrow['curr_code']);
      }
      $rep->fontSize -= 2;
      $total[0] += ($supprec["Balance"] - $supprec["Due"]);
      $total[1] += ($supprec["Due"] - $supprec["Overdue1"]);
      $total[2] += ($supprec["Overdue1"] - $supprec["Overdue2"]);
      $total[3] += $supprec["Overdue2"];
      $total[4] += $supprec["Balance"];
      for ($i = 0; $i < count($str); $i++) {
        $rep->AmountCol($i + 3, $i + 4, $str[$i], $dec);
      }
      $rep->NewLine(1, 2);
      if (!$summaryOnly) {
        $res = get_invoices($myrow['creditor_id'], $to);
        if (DB::_numRows($res) == 0) {
          continue;
        }
        $rep->Line($rep->row + 4);
        while ($trans = DB::_fetch($res)) {
          $rep->NewLine(1, 2);
          $rep->TextCol(0, 1, SysTypes::$names[$trans['type']], -2);
          $rep->TextCol(1, 2, $trans['reference'], -2);
          $rep->TextCol(2, 3, Dates::_sqlToDate($trans['tran_date']), -2);
          foreach ($trans as $i => $value) {
            $trans[$i] *= $rate;
          }
          $str = array(
            $trans["Balance"] - $trans["Due"],
            $trans["Due"] - $trans["Overdue1"],
            $trans["Overdue1"] - $trans["Overdue2"],
            $trans["Overdue2"],
            $trans["Balance"]
          );
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
    $rep->NewLine();
    if ($graphics) {
      $pg->x              = array(_('Current'), $txt_now_due, $txt_past_due1, $txt_past_due2);
      $pg->title          = $rep->title;
      $pg->axis_x         = _("Days");
      $pg->axis_y         = _("Amount");
      $pg->graphic_1      = $to;
      $pg->type           = $graphics;
      $pg->skin           = Config::_get('graphs_skin');
      $pg->built_in       = false;
      $pg->fontfile       = ROOT_URL . "reporting/fonts/Vera.ttf";
      $pg->latin_notation = (User::_prefs()->dec_sep != ".");
      $filename           = PATH_COMPANY . "pdf_files/test.png";
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
    $rep->End();
  }

