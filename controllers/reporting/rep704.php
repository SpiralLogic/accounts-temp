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

  print_GL_transactions();

  function print_GL_transactions() {

    $dim       = DB_Company::_get_pref('use_dimension');
    $dimension = $dimension2 = 0;
    $from      = $_POST['PARAM_0'];
    $to        = $_POST['PARAM_1'];
    $fromacc   = $_POST['PARAM_2'];
    $toacc     = $_POST['PARAM_3'];
    if ($dim == 2) {
      $dimension   = $_POST['PARAM_4'];
      $dimension2  = $_POST['PARAM_5'];
      $comments    = $_POST['PARAM_6'];
      $destination = $_POST['PARAM_7'];
    } else {
      if ($dim == 1) {
        $dimension   = $_POST['PARAM_4'];
        $comments    = $_POST['PARAM_5'];
        $destination = $_POST['PARAM_6'];
      } else {
        $comments    = $_POST['PARAM_4'];
        $destination = $_POST['PARAM_5'];
      }
    }
    if ($destination) {

      $report_type = '\\ADV\\App\\Reports\\Excel';
    } else {

      $report_type = '\\ADV\\App\\Reports\\PDF';
    }
    /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
    $rep = new $report_type(_('GL Account Transactions'), "GLAccountTransactions", SA_GLREP, User::_page_size());
    $dec = User::_price_dec();
    //$cols = array(0, 80, 100, 150, 210, 280, 340, 400, 450, 510, 570);
    $cols = array(0, 65, 105, 125, 175, 230, 290, 345, 405, 465, 525);
    //------------0--1---2---3----4----5----6----7----8----9----10-------
    //-----------------------dim1-dim2-----------------------------------
    //-----------------------dim1----------------------------------------

    $aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'left', 'right', 'right', 'right');
    if ($dim == 2) {
      $headers = array(
        _('Type'),
        _('Ref'),
        _('#'),
        _('Date'),
        _('Dimension') . " 1",
        _('Dimension') . " 2",
        _('Person/Item'),
        _('Debit'),
        _('Credit'),
        _('Balance')
      );
    } elseif ($dim == 1) {
      $headers = array(
        _('Type'),
        _('Ref'),
        _('#'),
        _('Date'),
        _('Dimension'),
        "",
        _('Person/Item'),
        _('Debit'),
        _('Credit'),
        _('Balance')
      );
    } else {
      $headers = array(
        _('Type'),
        _('Ref'),
        _('#'),
        _('Date'),
        "",
        "",
        _('Person/Item'),
        _('Debit'),
        _('Credit'),
        _('Balance')
      );
    }
    if ($dim == 2) {
      $params = array(
        0 => $comments,
        1 => array(
          'text' => _('Period'),
          'from' => $from,
          'to'   => $to
        ),
        2 => array(
          'text' => _('Accounts'),
          'from' => $fromacc,
          'to'   => $toacc
        ),
        3 => array(
          'text' => _('Dimension') . " 1",
          'from' => Dimensions::get_string($dimension),
          'to'   => ''
        ),
        4 => array(
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
            'text' => _('Accounts'),
            'from' => $fromacc,
            'to'   => $toacc
          ),
          3 => array(
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
          ),
          2 => array(
            'text' => _('Accounts'),
            'from' => $fromacc,
            'to'   => $toacc
          )
        );
      }
    }
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();
    $accounts = GL_Account::getAll($fromacc, $toacc);
    while ($account = DB::_fetch($accounts)) {
      if (GL_Account::is_balancesheet($account["account_code"])) {
        $begin = "";
      } else {
        $begin = Dates::_beginFiscalYear();
        if (Dates::_isGreaterThan($begin, $from)) {
          $begin = $from;
        }
        $begin = Dates::_addDays($begin, -1);
      }
      $prev_balance = GL_Trans::get_balance_from_to($begin, $from, $account["account_code"], $dimension, $dimension2);
      $trans        = GL_Trans::get($from, $to, -1, $account['account_code'], $dimension, $dimension2, null, 0);
      $rows         = DB::_numRows($trans);
      if ($prev_balance == 0.0 && $rows == 0) {
        continue;
      }
      $rep->Font('bold');
      $rep->TextCol(0, 4, $account['account_code'] . " " . $account['account_name'], -2);
      $rep->TextCol(4, 6, _('Opening Balance'));
      if ($prev_balance > 0.0) {
        $rep->AmountCol(7, 8, abs($prev_balance), $dec);
      } else {
        $rep->AmountCol(8, 9, abs($prev_balance), $dec);
      }
      $rep->Font();
      $total = $prev_balance;
      $rep->NewLine(2);
      if ($rows > 0) {
        while ($myrow = DB::_fetch($trans)) {
          $total += $myrow['amount'];
          $rep->TextCol(0, 1, SysTypes::$names[$myrow["type"]], -2);
          $reference = Ref::get($myrow["type"], $myrow["type_no"]);
          $rep->TextCol(1, 2, $reference);
          $rep->TextCol(2, 3, $myrow['type_no'], -2);
          $rep->DateCol(3, 4, $myrow["tran_date"], true);
          if ($dim >= 1) {
            $rep->TextCol(4, 5, Dimensions::get_string($myrow['dimension_id']));
          }
          if ($dim > 1) {
            $rep->TextCol(5, 6, Dimensions::get_string($myrow['dimension2_id']));
          }
          $txt  = Bank::payment_person_name($myrow["person_type_id"], $myrow["person_id"], false);
          $memo = $myrow['memo_'];
          if ($txt != "") {
            if ($memo != "") {
              $txt = $txt . "/" . $memo;
            }
          } else {
            $txt = $memo;
          }
          $rep->TextCol(6, 7, $txt, -2);
          if ($myrow['amount'] > 0.0) {
            $rep->AmountCol(7, 8, abs($myrow['amount']), $dec);
          } else {
            $rep->AmountCol(8, 9, abs($myrow['amount']), $dec);
          }
          $rep->TextCol(9, 10, Num::_format($total, $dec));
          $rep->NewLine();
          if ($rep->row < $rep->bottomMargin + $rep->lineHeight) {
            $rep->Line($rep->row - 2);
            $rep->Header();
          }
        }
        $rep->NewLine();
      }
      $rep->Font('bold');
      $rep->TextCol(4, 6, _("Ending Balance"));
      if ($total > 0.0) {
        $rep->AmountCol(7, 8, abs($total), $dec);
      } else {
        $rep->AmountCol(8, 9, abs($total), $dec);
      }
      $rep->Font();
      $rep->Line($rep->row - $rep->lineHeight + 4);
      $rep->NewLine(2, 1);
    }
    $rep->End();
  }


