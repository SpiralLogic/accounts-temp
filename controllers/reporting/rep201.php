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

  print_supplier_balances();
  /**
   * @param $creditor_id
   * @param $to
   * @param $convert
   *
   * @return \ADV\Core\DB\Query\Result|Array
   */
  function get_open_balance($creditor_id, $to, $convert) {
    $to  = Dates::_dateToSql($to);
    $sql = "SELECT SUM(IF(creditor_trans.type = " . ST_SUPPINVOICE . ", (creditor_trans.ov_amount + creditor_trans.ov_gst +
     creditor_trans.ov_discount)";
    if ($convert) {
      $sql .= " * rate";
    }
    $sql
      .= ", 0)) AS charges,
     SUM(IF(creditor_trans.type <> " . ST_SUPPINVOICE . ", (creditor_trans.ov_amount + creditor_trans.ov_gst +
     creditor_trans.ov_discount)";
    if ($convert) {
      $sql .= "* rate";
    }
    $sql
      .= ", 0)) AS credits,
        SUM(creditor_trans.alloc";
    if ($convert) {
      $sql .= " * rate";
    }
    $sql
      .= ") AS Allocated,
        SUM((creditor_trans.ov_amount + creditor_trans.ov_gst +
     creditor_trans.ov_discount - creditor_trans.alloc)";
    if ($convert) {
      $sql .= " * rate";
    }
    $sql
      .= ") AS OutStanding
        FROM creditor_trans
     WHERE creditor_trans.tran_date < '$to'
        AND creditor_trans.creditor_id = '$creditor_id' GROUP BY creditor_id";
    $result = DB::_query($sql, "No transactions were returned");

    return DB::_fetch($result);
  }

  /**
   * @param $creditor_id
   * @param $from
   * @param $to
   *
   * @return null|PDOStatement
   */
  function get_transactions($creditor_id, $from, $to) {
    $from = Dates::_dateToSql($from);
    $to   = Dates::_dateToSql($to);
    $sql
                = "SELECT creditor_trans.*,
                (creditor_trans.ov_amount + creditor_trans.ov_gst + creditor_trans.ov_discount)
                AS TotalAmount, creditor_trans.alloc AS Allocated,
                ((creditor_trans.type = " . ST_SUPPINVOICE . ")
                    AND creditor_trans.due_date < '$to') AS OverDue
             FROM creditor_trans
             WHERE creditor_trans.tran_date >= '$from' AND creditor_trans.tran_date <= '$to'
             AND creditor_trans.creditor_id = '$creditor_id'
                 ORDER BY creditor_trans.tran_date";
    $trans_rows = DB::_query($sql, "No transactions were returned");

    return $trans_rows;
  }

  function print_supplier_balances() {

    $from        = $_POST['PARAM_0'];
    $to          = $_POST['PARAM_1'];
    $fromsupp    = $_POST['PARAM_2'];
    $currency    = $_POST['PARAM_3'];
    $no_zeros    = $_POST['PARAM_4'];
    $comments    = $_POST['PARAM_5'];
    $destination = $_POST['PARAM_6'];
    if ($destination) {

      $report_type = '\\ADV\\App\\Reports\\Excel';
    } else {

      $report_type = '\\ADV\\App\\Reports\\PDF';
    }
    if ($fromsupp == ALL_NUMERIC) {
      $supp = _('All');
    } else {
      $supp = Creditor::get_name($fromsupp);
    }
    $dec = User::_price_dec();
    if ($currency == ALL_TEXT) {
      $convert  = true;
      $currency = _('Balances in Home currency');
    } else {
      $convert = false;
    }
    if ($no_zeros) {
      $nozeros = _('Yes');
    } else {
      $nozeros = _('No');
    }
    $cols    = array(0, 70, 110, 170, 220, 250, 315, 385, 450, 515);
    $headers = array(
      _('Trans Type'),
      _('#'),
      _('Invoice #'),
      _('Date'),
      _('Due Date'),
      _('Charges'),
      _('Credits'),
      _('Allocated'),
      _('Outstanding')
    );
    $aligns  = array('left', 'left', 'left', 'left', 'left', 'right', 'right', 'right', 'right');
    $params  = array(
      0 => $comments,
      1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
      2 => array('text' => _('Supplier'), 'from' => $supp, 'to' => ''),
      3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
      4 => array('text' => _('Suppress Zeros'), 'from' => $nozeros, 'to' => '')
    );
    /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
    $rep = new $report_type(_('Supplier Balances'), "SupplierBalances", SA_SUPPLIERANALYTIC, User::_page_size());
    $rep->Font();
    $rep->fontSize -= 2;
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();
    $total      = [];
    $grandtotal = array(0, 0, 0, 0);
    $sql        = "SELECT creditor_id, name, curr_code FROM suppliers";
    if ($fromsupp != ALL_NUMERIC) {
      $sql .= " WHERE creditor_id=" . DB::_escape($fromsupp);
    }
    $sql .= " ORDER BY name";
    $result = DB::_query($sql, "The customers could not be retrieved");
    while ($myrow = DB::_fetch($result)) {
      if (!$convert && $currency != $myrow['curr_code']) {
        continue;
      }
      $bal     = get_open_balance($myrow['creditor_id'], $from, $convert);
      $init[0] = $init[1] = 0.0;
      $init[0] = Num::_round(abs($bal['charges']), $dec);
      $init[1] = Num::_round(Abs($bal['credits']), $dec);
      $init[2] = Num::_round($bal['Allocated'], $dec);
      $init[3] = Num::_round($bal['OutStanding'], $dec);
      ;
      $total = array(0, 0, 0, 0);
      for ($i = 0; $i < 4; $i++) {
        $total[$i] += $init[$i];
        $grandtotal[$i] += $init[$i];
      }
      $res = get_transactions($myrow['creditor_id'], $from, $to);
      if ($no_zeros && DB::_numRows($res) == 0) {
        continue;
      }
      $rep->TextCol(0, 2, $myrow['name']);
      if ($convert) {
        $rep->TextCol(3, 4, $myrow['curr_code']);
      }
      $rep->fontSize -= 2;
      $rep->TextCol(4, 5, _("Open Balance"));
      $rep->AmountCol(5, 6, $init[0], $dec);
      $rep->AmountCol(6, 7, $init[1], $dec);
      $rep->AmountCol(7, 8, $init[2], $dec);
      $rep->AmountCol(8, 9, $init[3], $dec);
      $rep->NewLine(1, 2);
      if (DB::_numRows($res) == 0) {
        continue;
      }
      $rep->Line($rep->row + 4);
      while ($trans = DB::_fetch($res)) {
        if ($no_zeros && $trans['TotalAmount'] == 0 && $trans['Allocated'] == 0) {
          continue;
        }
        $rep->NewLine(1, 2);
        $rep->TextCol(0, 1, SysTypes::$names[$trans['type']]);
        $rep->TextCol(1, 2, $trans['reference']);
        $rep->TextCol(2, 3, $trans['supplier_reference']);
        $rep->DateCol(3, 4, $trans['tran_date'], true);
        if ($trans['type'] == ST_SUPPINVOICE) {
          $rep->DateCol(4, 5, $trans['due_date'], true);
        }
        $item[0] = $item[1] = 0.0;
        if ($convert) {
          $rate = $trans['rate'];
        } else {
          $rate = 1.0;
        }
        if ($trans['TotalAmount'] > 0.0) {
          $item[0] = Num::_round(abs($trans['TotalAmount']) * $rate, $dec);
          $rep->AmountCol(5, 6, $item[0], $dec);
        } else {
          $item[1] = Num::_round(abs($trans['TotalAmount']) * $rate, $dec);
          $rep->AmountCol(6, 7, $item[1], $dec);
        }
        $item[2] = Num::_round($trans['Allocated'] * $rate, $dec);
        $rep->AmountCol(7, 8, $item[2], $dec);
        /*
                     if ($trans['type'] == 20)
                       $item[3] = ($trans['TotalAmount'] - $trans['Allocated']) * $rate;
                     else
                       $item[3] = ($trans['TotalAmount'] + $trans['Allocated']) * $rate;
                     */
        if ($trans['type'] == ST_SUPPINVOICE || $trans['type'] == ST_BANKDEPOSIT) {
          $item[3] = $item[0] + $item[1] - $item[2];
        } else {
          $item[3] = $item[0] - $item[1] + $item[2];
        }
        $rep->AmountCol(8, 9, $item[3], $dec);
        for ($i = 0; $i < 4; $i++) {
          $total[$i] += $item[$i];
          $grandtotal[$i] += $item[$i];
        }
      }
      $rep->Line($rep->row - 8);
      $rep->NewLine(2);
      $rep->TextCol(0, 3, _('Total'));
      for ($i = 0; $i < 4; $i++) {
        $rep->AmountCol($i + 5, $i + 6, $total[$i], $dec);
        $total[$i] = 0.0;
      }
      $rep->Line($rep->row - 4);
      $rep->NewLine(2);
    }
    $rep->fontSize += 2;
    $rep->TextCol(0, 3, _('Grand Total'));
    $rep->fontSize -= 2;
    for ($i = 0; $i < 4; $i++) {
      $rep->AmountCol($i + 5, $i + 6, $grandtotal[$i], $dec);
    }
    $rep->Line($rep->row - 4);
    $rep->NewLine();
    $rep->End();
  }

