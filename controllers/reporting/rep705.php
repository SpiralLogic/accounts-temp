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
  print_annual_expense_breakdown();
  /**
   * @param $yr
   * @param $mo
   * @param $account
   * @param $dimension
   * @param $dimension2
   *
   * @return \ADV\Core\DB\Query\Result|Array
   */
  function getPeriods($yr, $mo, $account, $dimension, $dimension2) {
    $date13 = date('Y-m-d', mktime(0, 0, 0, $mo + 1, 1, $yr));
    $date12 = date('Y-m-d', mktime(0, 0, 0, $mo, 1, $yr));
    $date11 = date('Y-m-d', mktime(0, 0, 0, $mo - 1, 1, $yr));
    $date10 = date('Y-m-d', mktime(0, 0, 0, $mo - 2, 1, $yr));
    $date09 = date('Y-m-d', mktime(0, 0, 0, $mo - 3, 1, $yr));
    $date08 = date('Y-m-d', mktime(0, 0, 0, $mo - 4, 1, $yr));
    $date07 = date('Y-m-d', mktime(0, 0, 0, $mo - 5, 1, $yr));
    $date06 = date('Y-m-d', mktime(0, 0, 0, $mo - 6, 1, $yr));
    $date05 = date('Y-m-d', mktime(0, 0, 0, $mo - 7, 1, $yr));
    $date04 = date('Y-m-d', mktime(0, 0, 0, $mo - 8, 1, $yr));
    $date03 = date('Y-m-d', mktime(0, 0, 0, $mo - 9, 1, $yr));
    $date02 = date('Y-m-d', mktime(0, 0, 0, $mo - 10, 1, $yr));
    $date01 = date('Y-m-d', mktime(0, 0, 0, $mo - 11, 1, $yr));
    $sql
            = "SELECT SUM(CASE WHEN tran_date >= '$date01' AND tran_date < '$date02' THEN amount / 1000 ELSE 0 END) AS per01,
                 SUM(CASE WHEN tran_date >= '$date02' AND tran_date < '$date03' THEN amount / 1000 ELSE 0 END) AS per02,
                 SUM(CASE WHEN tran_date >= '$date03' AND tran_date < '$date04' THEN amount / 1000 ELSE 0 END) AS per03,
                 SUM(CASE WHEN tran_date >= '$date04' AND tran_date < '$date05' THEN amount / 1000 ELSE 0 END) AS per04,
                 SUM(CASE WHEN tran_date >= '$date05' AND tran_date < '$date06' THEN amount / 1000 ELSE 0 END) AS per05,
                 SUM(CASE WHEN tran_date >= '$date06' AND tran_date < '$date07' THEN amount / 1000 ELSE 0 END) AS per06,
                 SUM(CASE WHEN tran_date >= '$date07' AND tran_date < '$date08' THEN amount / 1000 ELSE 0 END) AS per07,
                 SUM(CASE WHEN tran_date >= '$date08' AND tran_date < '$date09' THEN amount / 1000 ELSE 0 END) AS per08,
                 SUM(CASE WHEN tran_date >= '$date09' AND tran_date < '$date10' THEN amount / 1000 ELSE 0 END) AS per09,
                 SUM(CASE WHEN tran_date >= '$date10' AND tran_date < '$date11' THEN amount / 1000 ELSE 0 END) AS per10,
                 SUM(CASE WHEN tran_date >= '$date11' AND tran_date < '$date12' THEN amount / 1000 ELSE 0 END) AS per11,
                 SUM(CASE WHEN tran_date >= '$date12' AND tran_date < '$date13' THEN amount / 1000 ELSE 0 END) AS per12
             FROM gl_trans
                WHERE account='$account'";
    if ($dimension != 0) {
      $sql .= " AND dimension_id = " . ($dimension < 0 ? 0 : DB::_escape($dimension));
    }
    if ($dimension2 != 0) {
      $sql .= " AND dimension2_id = " . ($dimension2 < 0 ? 0 : DB::_escape($dimension2));
    }
    $result = DB::_query($sql, "Transactions for account $account could not be calculated");
    return DB::_fetch($result);
  }

  /**
   * @param $type
   * @param $typename
   * @param $yr
   * @param $mo
   * @param $convert
   * @param $dec
   * @param $rep
   * @param $dimension
   * @param $dimension2
   *
   * @return array
   */
  function display_type($type, $typename, $yr, $mo, $convert, &$dec, &$rep, $dimension = null, $dimension2 = null) {
    $ctotal     = array(1 => 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
    $total      = array(1 => 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
    $totals_arr = [];
    $printtitle = 0; //Flag for printing type name
    //Get Accounts directly under this group/type
    $result = GL_Account::getAll(null, null, $type);
    while ($account = DB::_fetch($result)) {
      $bal = getPeriods($yr, $mo, $account["account_code"], $dimension, $dimension2);
      if (!$bal['per01'] && !$bal['per02'] && !$bal['per03'] && !$bal['per04'] && !$bal['per05'] && !$bal['per06'] && !$bal['per07'] && !$bal['per08'] && !$bal['per09'] && !$bal['per10'] && !$bal['per11'] && !$bal['per12']
      ) {
        continue;
      }
      //Print Type Title if it has atleast one non-zero account
      if (!$printtitle) {
        $printtitle = 1;
        $rep->row -= 4;
        $rep->TextCol(0, 5, $typename);
        $rep->row -= 4;
        $rep->Line($rep->row);
        $rep->NewLine();
      }
      $balance = array(
        1 => $bal['per01'],
        $bal['per02'],
        $bal['per03'],
        $bal['per04'],
        $bal['per05'],
        $bal['per06'],
        $bal['per07'],
        $bal['per08'],
        $bal['per09'],
        $bal['per10'],
        $bal['per11'],
        $bal['per12']
      );
      $rep->TextCol(0, 1, $account['account_code']);
      $rep->TextCol(1, 2, $account['account_name']);
      for ($i = 1; $i <= 12; $i++) {
        $rep->AmountCol($i + 1, $i + 2, $balance[$i] * $convert, $dec);
        $ctotal[$i] += $balance[$i];
      }
      $rep->NewLine();
    }
    //Get Account groups/types under this group/type
    $result = GL_Type::getAll(false, false, $type);
    while ($accounttype = DB::_fetch($result)) {
      //Print Type Title if has sub types and not previously printed
      if (!$printtitle) {
        $printtitle = 1;
        $rep->row -= 4;
        $rep->TextCol(0, 5, $typename);
        $rep->row -= 4;
        $rep->Line($rep->row);
        $rep->NewLine();
      }
      $totals_arr = display_type($accounttype["id"], $accounttype["name"], $yr, $mo, $convert, $dec, $rep, $dimension, $dimension2);
      for ($i = 1; $i <= 12; $i++) {
        $total[$i] += $totals_arr[$i];
      }
    }
    //Display Type Summary if total is != 0 OR head is printed (Needed in case of unused hierarchical COA)
    if ($printtitle) {
      $rep->row += 6;
      $rep->Line($rep->row);
      $rep->NewLine();
      $rep->TextCol(0, 2, _('Total') . " " . $typename);
      for ($i = 1; $i <= 12; $i++) {
        $rep->AmountCol($i + 1, $i + 2, ($total[$i] + $ctotal[$i]) * $convert, $dec);
      }
      $rep->NewLine();
    }
    for ($i = 1; $i <= 12; $i++) {
      $totals_arr[$i] = $total[$i] + $ctotal[$i];
    }
    return $totals_arr;
  }

  function print_annual_expense_breakdown() {
    $dim       = DB_Company::_get_pref('use_dimension');
    $dimension = $dimension2 = 0;
    if ($dim == 2) {
      $year        = $_POST['PARAM_0'];
      $dimension   = $_POST['PARAM_1'];
      $dimension2  = $_POST['PARAM_2'];
      $comments    = $_POST['PARAM_3'];
      $destination = $_POST['PARAM_4'];
    } else {
      if ($dim == 1) {
        $year        = $_POST['PARAM_0'];
        $dimension   = $_POST['PARAM_1'];
        $comments    = $_POST['PARAM_2'];
        $destination = $_POST['PARAM_3'];
      } else {
        $year        = $_POST['PARAM_0'];
        $comments    = $_POST['PARAM_1'];
        $destination = $_POST['PARAM_2'];
      }
    }
    if ($destination) {
      $report_type = '\\ADV\\App\\Reports\\Excel';
    } else {
      $report_type = '\\ADV\\App\\Reports\\PDF';
    }
    $dec = 1;
    //$pdec = User::_percent_dec();
    $cols = array(0, 40, 150, 180, 210, 240, 270, 300, 330, 360, 390, 420, 450, 480, 510);
    //------------0--1---2----3----4----5----6----7----8----10---11---12---13---14---15-
    //$yr = date('Y');
    //$mo = date('m'):
    // from now
    $sql     = "SELECT begin, end, YEAR(end) AS yr, MONTH(end) AS mo FROM fiscal_year WHERE id=" . DB::_escape($year);
    $result  = DB::_query($sql, "could not get fiscal year");
    $row     = DB::_fetch($result);
    $year    = Dates::_sqlToDate($row['begin']) . " - " . Dates::_sqlToDate($row['end']);
    $yr      = $row['yr'];
    $mo      = $row['mo'];
    $da      = 1;
    $per12   = strftime('%b', mktime(0, 0, 0, $mo, $da, $yr));
    $per11   = strftime('%b', mktime(0, 0, 0, $mo - 1, $da, $yr));
    $per10   = strftime('%b', mktime(0, 0, 0, $mo - 2, $da, $yr));
    $per09   = strftime('%b', mktime(0, 0, 0, $mo - 3, $da, $yr));
    $per08   = strftime('%b', mktime(0, 0, 0, $mo - 4, $da, $yr));
    $per07   = strftime('%b', mktime(0, 0, 0, $mo - 5, $da, $yr));
    $per06   = strftime('%b', mktime(0, 0, 0, $mo - 6, $da, $yr));
    $per05   = strftime('%b', mktime(0, 0, 0, $mo - 7, $da, $yr));
    $per04   = strftime('%b', mktime(0, 0, 0, $mo - 8, $da, $yr));
    $per03   = strftime('%b', mktime(0, 0, 0, $mo - 9, $da, $yr));
    $per02   = strftime('%b', mktime(0, 0, 0, $mo - 10, $da, $yr));
    $per01   = strftime('%b', mktime(0, 0, 0, $mo - 11, $da, $yr));
    $headers = array(
      _('Account'),
      _('Account Name'),
      $per01,
      $per02,
      $per03,
      $per04,
      $per05,
      $per06,
      $per07,
      $per08,
      $per09,
      $per10,
      $per11,
      $per12
    );
    $aligns  = array(
      'left',
      'left',
      'right',
      'right',
      'right',
      'right',
      'right',
      'right',
      'right',
      'right',
      'right',
      'right',
      'right',
      'right'
    );
    if ($dim == 2) {
      $params = array(
        0 => $comments,
        1 => array(
          'text' => _("Year"),
          'from' => $year,
          'to'   => ''
        ),
        2 => array(
          'text' => _("Dimension") . " 1",
          'from' => Dimensions::get_string($dimension),
          'to'   => ''
        ),
        3 => array(
          'text' => _("Dimension") . " 2",
          'from' => Dimensions::get_string($dimension2),
          'to'   => ''
        ),
        4 => array(
          'text' => _('Info'),
          'from' => _('Amounts in thousands'),
          'to'   => ''
        )
      );
    } else {
      if ($dim == 1) {
        $params = array(
          0 => $comments,
          1 => array(
            'text' => _("Year"),
            'from' => $year,
            'to'   => ''
          ),
          2 => array(
            'text' => _('Dimension'),
            'from' => Dimensions::get_string($dimension),
            'to'   => ''
          ),
          3 => array(
            'text' => _('Info'),
            'from' => _('Amounts in thousands'),
            'to'   => ''
          )
        );
      } else {
        $params = array(
          0 => $comments,
          1 => array(
            'text' => _("Year"),
            'from' => $year,
            'to'   => ''
          ),
          2 => array(
            'text' => _('Info'),
            'from' => _('Amounts in thousands'),
            'to'   => ''
          )
        );
      }
    }
    /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
    $rep = new $report_type(_('Annual Expense Breakdown'), "AnnualBreakDown", SA_GLANALYTIC, User::_page_size());
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();
    $sales       = Array(1 => 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
    $classresult = GL_Class::getAll(false, 0);
    while ($class = DB::_fetch($classresult)) {
      $ctotal  = Array(1 => 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
      $convert = SysTypes::get_class_type_convert($class["ctype"]);
      //Print Class Name
      $rep->Font('bold');
      $rep->TextCol(0, 5, $class["class_name"]);
      $rep->Font();
      $rep->NewLine();
      //Get Account groups/types under this group/type with no parents
      $typeresult = GL_Type::getAll(false, $class['cid'], -1);
      while ($accounttype = DB::_fetch($typeresult)) {
        $classtotal = display_type($accounttype["id"], $accounttype["name"], $yr, $mo, $convert, $dec, $rep, $dimension, $dimension2);
        for ($i = 1; $i <= 12; $i++) {
          $ctotal[$i] += $classtotal[$i];
        }
      }
      //Print Class Summary
      $rep->row += 6;
      $rep->Line($rep->row);
      $rep->NewLine();
      $rep->Font('bold');
      $rep->TextCol(0, 2, _('Total') . " " . $class["class_name"]);
      for ($i = 1; $i <= 12; $i++) {
        $rep->AmountCol($i + 1, $i + 2, $ctotal[$i] * $convert, $dec);
        $sales[$i] += $ctotal[$i];
      }
      $rep->Font();
      $rep->NewLine(2);
    }
    $rep->Font('bold');
    $rep->TextCol(0, 2, _("Calculated Return"));
    for ($i = 1; $i <= 12; $i++) {
      $rep->AmountCol($i + 1, $i + 2, $sales[$i] * -1, $dec);
    }
    $rep->Font();
    $rep->NewLine();
    $rep->Line($rep->row);
    $rep->NewLine(2);
    $rep->End();
  }

