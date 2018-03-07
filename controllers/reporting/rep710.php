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

  print_audit_trail();
  /**
   * @param $from
   * @param $to
   * @param $type
   * @param $user
   *
   * @return null|PDOStatement
   */
  function get_transactions($from, $to, $type, $user) {
    $fromdate = Dates::_dateToSql($from) . " 00:00:00";
    $todate   = Dates::_dateToSql($to) . " 23:59.59";
    $sql
              = "SELECT a.*,
        SUM(IF(ISNULL(g.amount), null, IF(g.amount > 0, g.amount, 0))) AS amount,
        u.user_id,
        UNIX_TIMESTAMP(a.stamp) as unix_stamp
        FROM audit_trail AS a JOIN users AS u
        LEFT JOIN gl_trans AS g ON (g.type_no=a.trans_no
            AND g.type=a.type)
        WHERE a.user = u.id ";
    if ($type != -1) {
      $sql .= "AND a.type=$type ";
    }
    if ($user != -1) {
      $sql .= "AND a.user='$user' ";
    }
    $sql
      .= "AND a.stamp >= '$fromdate'
            AND a.stamp <= '$todate'
        GROUP BY a.trans_no,a.gl_seq,a.stamp
        ORDER BY a.stamp,a.gl_seq";
    return DB::_query($sql, "No transactions were returned");
  }

  function print_audit_trail() {

    $from        = $_POST['PARAM_0'];
    $to          = $_POST['PARAM_1'];
    $systype     = $_POST['PARAM_2'];
    $user        = $_POST['PARAM_3'];
    $comments    = $_POST['PARAM_4'];
    $destination = $_POST['PARAM_5'];
    if ($destination) {
      $report_type = '\\ADV\\App\\Reports\\Excel';
    } else {
      $report_type = '\\ADV\\App\\Reports\\PDF';
    }
    $dec     = User::_price_dec();
    $cols    = array(0, 60, 120, 180, 240, 340, 400, 460, 520);
    $headers = array(
      _('Date'),
      _('Time'),
      _('User'),
      _('Trans Date'),
      _('Type'),
      _('#'),
      _('Action'),
      _('Amount')
    );
    $aligns  = array('left', 'left', 'left', 'left', 'left', 'left', 'left', 'right');
    $usr     = Users::get($user);
    $user_id = $usr['user_id'];
    $params  = array(
      0 => $comments,
      1 => array(
        'text' => _('Period'),
        'from' => $from,
        'to'   => $to
      ),
      2 => array(
        'text' => _('Type'),
        'from' => ($systype != -1 ? SysTypes::$names[$systype] : _('All')),
        'to'   => ''
      ),
      3 => array(
        'text' => _('User'),
        'from' => ($user != -1 ? $user_id : _('All')),
        'to'   => ''
      )
    );
    /** @var \ADV\App\Reports\PDF|\ADV\App\Reports\Excel $rep */
    $rep = new $report_type(_('Audit Trail'), "AuditTrail", SA_GLANALYTIC, User::_page_size());
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();
    $trans = get_transactions($from, $to, $systype, $user);
    while ($myrow = DB::_fetch($trans)) {
      $rep->TextCol(0, 1, Dates::_sqlToDate(date("Y-m-d", $myrow['unix_stamp'])));
      if (User::_date_format() == 0) {
        $rep->TextCol(1, 2, date("h:i:s a", $myrow['unix_stamp']));
      } else {
        $rep->TextCol(1, 2, date("H:i:s", $myrow['unix_stamp']));
      }
      $rep->TextCol(2, 3, $myrow['user_id']);
      $rep->TextCol(3, 4, Dates::_sqlToDate($myrow['gl_date']));
      $rep->TextCol(4, 5, SysTypes::$names[$myrow['type']]);
      $rep->TextCol(5, 6, $myrow['trans_no']);
      if ($myrow['gl_seq'] == null) {
        $action = _('Changed');
      } else {
        $action = _('Closed');
      }
      $rep->TextCol(6, 7, $action);
      if ($myrow['amount'] != null) {
        $rep->AmountCol(7, 8, $myrow['amount'], $dec);
      }
      $rep->NewLine(1, 2);
    }
    $rep->Line($rep->row + 4);
    $rep->End();
  }

