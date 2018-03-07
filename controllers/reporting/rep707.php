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
  /**
   * @param $type
   * @param $typename
   * @param $from
   * @param $to
   * @param $begin
   * @param $end
   * @param $compare
   * @param $convert
   * @param $dec
   * @param $pdec
   * @param $rep
   * @param $dimension
   * @param $dimension2
   * @param $pg
   * @param $graphics
   *
   * @return array
   */
  function display_type($type, $typename, $from, $to, $begin, $end, $compare, $convert, &$dec, &$pdec, &$rep, $dimension, $dimension2, &$pg, $graphics) {
    $code_per_balance  = 0;
    $code_acc_balance  = 0;
    $per_balance_total = 0;
    $acc_balance_total = 0;
    unset($totals_arr);
    $totals_arr = [];
    $printtitle = 0; //Flag for printing type name
    //Get Accounts directly under this group/type
    $result = GL_Account::getAll(null, null, $type);
    while ($account = DB::_fetch($result)) {
      $per_balance = GL_Trans::get_from_to($from, $to, $account["account_code"], $dimension, $dimension2);
      if ($compare == 2) {
        $acc_balance = GL_Trans::get_budget_from_to($begin, $end, $account["account_code"], $dimension, $dimension2);
      } else {
        $acc_balance = GL_Trans::get_from_to($begin, $end, $account["account_code"], $dimension, $dimension2);
      }
      if (!$per_balance && !$acc_balance) {
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
      $rep->TextCol(0, 1, $account['account_code']);
      $rep->TextCol(1, 2, $account['account_name']);
      $rep->AmountCol(2, 3, $per_balance * $convert, $dec);
      $rep->AmountCol(3, 4, $acc_balance * $convert, $dec);
      $rep->AmountCol(4, 5, Achieve($per_balance, $acc_balance), $pdec);
      $rep->NewLine();
      if ($rep->row < $rep->bottomMargin + 3 * $rep->lineHeight) {
        $rep->Line($rep->row - 2);
        $rep->Header();
      }
      $code_per_balance += $per_balance;
      $code_acc_balance += $acc_balance;
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
      $totals_arr = display_type(
        $accounttype["id"],
        $accounttype["name"],
        $from,
        $to,
        $begin,
        $end,
        $compare,
        $convert,
        $dec,
        $pdec,
        $rep,
        $dimension,
        $dimension2,
        $pg,
        $graphics
      );
      $per_balance_total += $totals_arr[0];
      $acc_balance_total += $totals_arr[1];
    }
    //Display Type Summary if total is != 0 OR head is printed (Needed in case of unused hierarchical COA)
    if (($code_per_balance + $per_balance_total + $code_acc_balance + $acc_balance_total) != 0 || $printtitle) {
      $rep->row += 6;
      $rep->Line($rep->row);
      $rep->NewLine();
      $rep->TextCol(0, 2, _('Total') . " " . $typename);
      $rep->AmountCol(2, 3, ($code_per_balance + $per_balance_total) * $convert, $dec);
      $rep->AmountCol(3, 4, ($code_acc_balance + $acc_balance_total) * $convert, $dec);
      $rep->AmountCol(4, 5, Achieve(($code_per_balance + $per_balance_total), ($code_acc_balance + $acc_balance_total)), $pdec);
      if ($graphics) {
        $pg->x[] = $typename;
        $pg->y[] = abs($code_per_balance + $per_balance_total);
        $pg->z[] = abs($code_acc_balance + $acc_balance_total);
      }
      $rep->NewLine();
    }
    $totals_arr[0] = $code_per_balance + $per_balance_total;
    $totals_arr[1] = $code_acc_balance + $acc_balance_total;
    return $totals_arr;
  }

  print_profit_and_loss_statement();
  /**
   * @param $d1
   * @param $d2
   *
   * @return float|int
   */
  function Achieve($d1, $d2) {
    if ($d1 == 0 && $d2 == 0) {
      return 0;
    } elseif ($d2 == 0) {
      return 999;
    }
    $ret = ($d1 / $d2 * 100.0);
    if ($ret > 999) {
      $ret = 999;
    }
    return $ret;
  }

  function print_profit_and_loss_statement() {
    $dim       = DB_Company::_get_pref('use_dimension');
    $dimension = $dimension2 = 0;
    $from      = $_POST['PARAM_0'];
    $to        = $_POST['PARAM_1'];
    $compare   = $_POST['PARAM_2'];
    if ($dim == 2) {
      $dimension   = $_POST['PARAM_3'];
      $dimension2  = $_POST['PARAM_4'];
      $decimals    = $_POST['PARAM_5'];
      $graphics    = $_POST['PARAM_6'];
      $comments    = $_POST['PARAM_7'];
      $destination = $_POST['PARAM_8'];
    } else {
      if ($dim == 1) {
        $dimension   = $_POST['PARAM_3'];
        $decimals    = $_POST['PARAM_4'];
        $graphics    = $_POST['PARAM_5'];
        $comments    = $_POST['PARAM_6'];
        $destination = $_POST['PARAM_7'];
      } else {
        $decimals    = $_POST['PARAM_3'];
        $graphics    = $_POST['PARAM_4'];
        $comments    = $_POST['PARAM_5'];
        $destination = $_POST['PARAM_6'];
      }
    }
    if ($destination) {
      $report_type = '\\ADV\\App\\Reports\\Excel';
    } else {
      $report_type = '\\ADV\\App\\Reports\\PDF';
    }
    if ($graphics) {
      $pg = new Reports_Graph();
    }
    if (!$decimals) {
      $dec = 0;
    } else {
      $dec = User::_price_dec();
    }
    $pdec = User::_percent_dec();
    $cols = array(0, 50, 200, 350, 425, 500);
    //------------0--1---2----3----4----5--
    $headers = array(_('Account'), _('Account Name'), _('Period'), _('Accumulated'), _('Achieved %'));
    $aligns  = array('left', 'left', 'right', 'right', 'right');
    if ($dim == 2) {
      $params = array(
        0 => $comments,
        1 => array(
          'text' => _('Period'),
          'from' => $from,
          'to'   => $to
        ),
        2 => array(
          'text' => _('Dimension') . " 1",
          'from' => Dimensions::get_string($dimension),
          'to'   => ''
        ),
        3 => array(
          'text' => _('Dimension') . " 2",
          'from' => Dimensions::get_string($dimension2),
          'to'   => ''
        )
      );
    } else {
      if ($dim == 1) {
        $params = array(
          0 => $comments,
          1 => array(
            'text' => _('Period'),
            'from' => $from,
            'to'   => $to
          ),
          2 => array(
            'text' => _('Dimension'),
            'from' => Dimensions::get_string($dimension),
            'to'   => ''
          )
        );
      } else {
        $params = array(
          0 => $comments,
          1 => array(
            'text' => _('Period'),
            'from' => $from,
            'to'   => $to
          )
        );
      }
    }
    if ($compare == 0 || $compare == 2) {
      $end = $to;
      if ($compare == 2) {
        $begin      = $from;
        $headers[3] = _('Budget');
      } else {
        $begin = Dates::_beginFiscalYear();
      }
    } elseif ($compare == 1) {
      $begin      = Dates::_addMonths($from, -12);
      $end        = Dates::_addMonths($to, -12);
      $headers[3] = _('Period Y-1');
    }
    /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
    $rep = new $report_type(_('Profit and Loss Statement'), "ProfitAndLoss", SA_GLANALYTIC, User::_page_size());
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();
    $classper    = 0.0;
    $classacc    = 0.0;
    $salesper    = 0.0;
    $salesacc    = 0.0;
    $classresult = GL_Class::getAll(false, 0);
    while ($class = DB::_fetch($classresult)) {
      $class_per_total = 0;
      $class_acc_total = 0;
      $convert         = SysTypes::get_class_type_convert($class["ctype"]);
      //Print Class Name
      $rep->Font('bold');
      $rep->TextCol(0, 5, $class["class_name"]);
      $rep->Font();
      $rep->NewLine();
      //Get Account groups/types under this group/type with no parents
      $typeresult = GL_Type::getAll(false, $class['cid'], -1);
      while ($accounttype = DB::_fetch($typeresult)) {
        $classtotal = display_type(
          $accounttype["id"],
          $accounttype["name"],
          $from,
          $to,
          $begin,
          $end,
          $compare,
          $convert,
          $dec,
          $pdec,
          $rep,
          $dimension,
          $dimension2,
          $pg,
          $graphics
        );
        $class_per_total += $classtotal[0];
        $class_acc_total += $classtotal[1];
      }
      //Print Class Summary
      $rep->row += 6;
      $rep->Line($rep->row);
      $rep->NewLine();
      $rep->Font('bold');
      $rep->TextCol(0, 2, _('Total') . " " . $class["class_name"]);
      $rep->AmountCol(2, 3, $class_per_total * $convert, $dec);
      $rep->AmountCol(3, 4, $class_acc_total * $convert, $dec);
      $rep->AmountCol(4, 5, Achieve($class_per_total, $class_acc_total), $pdec);
      $rep->Font();
      $rep->NewLine(2);
      $salesper += $class_per_total;
      $salesacc += $class_acc_total;
    }
    $rep->Font('bold');
    $rep->TextCol(0, 2, _('Calculated Return'));
    $rep->AmountCol(2, 3, $salesper * -1, $dec); // always convert
    $rep->AmountCol(3, 4, $salesacc * -1, $dec);
    $rep->AmountCol(4, 5, Achieve($salesper, $salesacc), $pdec);
    if ($graphics) {
      $pg->x[] = _('Calculated Return');
      $pg->y[] = abs($salesper);
      $pg->z[] = abs($salesacc);
    }
    $rep->Font();
    $rep->NewLine();
    $rep->Line($rep->row);
    if ($graphics) {
      $pg->title          = $rep->title;
      $pg->axis_x         = _("Group");
      $pg->axis_y         = _("Amount");
      $pg->graphic_1      = $headers[2];
      $pg->graphic_2      = $headers[3];
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


