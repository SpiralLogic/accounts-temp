<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  use ADV\App\Dates;
  use ADV\Core\Num;
  use ADV\Core\DB\DB;
  use ADV\App\Forms;
  use ADV\Core\Table;
  use ADV\App\Display;

  /**
   *
   */
  class Bank_UI
  {
    /**
     * @static
     *
     * @param      $account
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     * @param bool $special_option
     *
     * @return string
     */
    public static function reconcile($account, $name, $selected_id = null, $submit_on_change = false, $special_option = false) {
      $sql
        = "SELECT reconciled FROM bank_trans
 WHERE bank_act=" . DB::_escape($account) . " AND reconciled IS NOT null AND amount!=0
 GROUP BY reconciled";
      return Forms::selectBox(
        $name,
        $selected_id,
        $sql,
        'id',
        'reconciled',
        array(
             'spec_option'   => $special_option,
             'format'        => 'Forms::dateFormat',
             'spec_id'       => '',
             'select_submit' => $submit_on_change,
             'order'         => 'reconciled DESC'
        )
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $account
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     * @param bool $special_option
     */
    public static function reconcile_cells($label, $account, $name, $selected_id = null, $submit_on_change = false, $special_option = false) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Bank_UI::reconcile($account, $name, $selected_id, $submit_on_change, $special_option);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param        $bank_acc
     * @param string $parms
     */
    public static function balance_row($bank_acc, $parms = '') {
      $to  = Dates::_addDays(Dates::_today(), 1);
      $bal = Bank_Account::getBalances($bank_acc, null, $to);
      Table::label(
        _("Bank Balance:"),
        "<a target='_blank' " . ($bal < 0 ? 'class="redfg openWindow"' : '') . "href='/gl/inquiry/bank.php?bank_account=" . $bank_acc . "'" . " >&nbsp;" .
          Num::_priceFormat(
            $bal
          ) . "</a>",
        $parms
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     * @param bool $raw
     *
     * @return string
     */
    public static function cash_accounts_row($label, $name, $selected_id = null, $submit_on_change = false, $raw = false) {
      $sql
        = "SELECT bank_accounts.id, bank_account_name, bank_curr_code, inactive
 FROM bank_accounts
 WHERE bank_accounts.account_type=3";
      if ($label != null) {
        if (!$raw) {
          echo "<tr><td class='label'>$label</td>\n";
        }
      }
      if (!$raw) {
        echo "<td>";
      }
      $select = Forms::selectBox(
        $name,
        $selected_id,
        $sql,
        'id',
        'bank_account_name',
        array(
             'format'        => 'Forms::addCurrFormat',
             'select_submit' => $submit_on_change,
             'async'         => true
        )
      );
      if ($raw) {
        return $select;
      }
      echo $select;
      echo "</td></tr>\n";
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $trans_no
     * @param string $label
     * @param bool   $icon
     * @param string $class
     * @param string $id
     * @param bool   $raw
     *
     * @return null|string
     */
    public static function viewTrans($type, $trans_no, $label = "", $icon = false, $class = '', $id = '', $raw = false) {
      if ($label == "") {
        $label = $trans_no;
      }
      switch ($type) {
        case ST_BANKTRANSFER:
          $viewer = "bank_transfer.php";
          break;
        case ST_BANKPAYMENT:
          $viewer = "gl_payment.php";
          break;
        case ST_BANKDEPOSIT:
          $viewer = "gl_deposit.php";
          break;
        default:
          return null;
      }
      if ($raw) {
        return "banking/view/$viewer?trans_no=$trans_no";
      }
      return Display::viewer_link($label, "banking/view/$viewer?trans_no=$trans_no", $class, $id, $icon);
    }
  }

