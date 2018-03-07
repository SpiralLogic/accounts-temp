<?php
  use ADV\Core\DB\DB;
  use ADV\Core\Event;
  use ADV\App\Dates;
  use ADV\App\User;

  /**
   * Created by JetBrains PhpStorm.
   * User: Complex
   * Date: 4/08/12
   * Time: 5:25 PM
   * To change this template use File | Settings | File Templates.
   */
  class Bank_Undeposited
  {
    /** @var \ADV\Core\DB\DB */
    static $DB;
    /** @var \ADV\App\Dates */
    static $Dates;
    /**
     * @param $group_id
     *
     * @internal param $groupid
     */
    public static function ungroup($group_id) {
      $sql = "UPDATE bank_trans SET undeposited=0, reconciled=null WHERE undeposited =" . static::$DB->_escape($group_id);
      static::$DB->_query($sql, "Couldn't ungroup group deposit");
      $sql = "DELETE FROM bank_trans  WHERE id=" . $group_id;
      static::$DB->_query($sql, "Couldn't update removed group deposit data");
    }
    /**
     * @param $deposit_id
     *
     * @internal param $deposit
     */
    public static function undeposit($deposit_id) {
      $sql = "UPDATE bank_trans SET undeposited=0, reconciled=null WHERE id=" . $deposit_id;
      static::$DB->_query($sql, "Can't change undeposited status");
    }
    /**
     * @param $account
     * @param $date
     *
     * @return mixed
     */
    public static function createGroup($account, $date) {
      $sql
                  = "INSERT INTO bank_trans (type, bank_act, amount, ref, trans_date, person_type_id, person_id, undeposited)
          VALUES (" . ST_GROUPDEPOSIT . ", " . static::$DB->_quote($account) . ", 0," . static::$DB->_quote('Group Deposit') . "," . //
        static::$DB->_quote($date) . ", 6," . //
        static::$DB->_quote(User::_i()->user) . ",0)";
      $query      = static::$DB->_query($sql, "Undeposited Cannot be Added");
      $deposit_id = static::$DB->_insertId($query);
      return $deposit_id;
    }
    /**
     * @param $trans_id
     * @param $account
     * @param $togroup
     *
     * @return bool
     */
    public static function addToGroup($trans_id, $account, $togroup) {
      if ($trans_id == $togroup) {
        Event::error('These are both the same transaction!');
        exit;
      }
      $result1 = static::$DB->_select('type', 'amount', 'trans_date', 'undeposited')->from('bank_trans')->where('id=', $trans_id)->where('bank_act=', $account)->fetch()->one();
      $result2 = static::$DB->_select('type', 'amount', 'trans_date', 'undeposited')->from('bank_trans')->where('id=', $togroup)->where('bank_act=', $account)->fetch()->one();
      $type1   = $result1['type'];
      $amount1 = $result1['amount'];
      $date1   = $result1['trans_date'];
      $group1  = $result1['undeposited'];
      $type2   = $result2['type'];
      $amount2 = $result2['amount'];
      $group2  = $result2['undeposited'];
      if ($group1 > 1 && $group2 > 1) {
        Event::error('Transactions are already grouped!');
        return false;
      }
      if ($type1 == ST_GROUPDEPOSIT && $type2 == ST_GROUPDEPOSIT) {
        $group = $trans_id;
        $sql   = "UPDATE bank_trans SET undeposited=" . $group . " WHERE undeposited=" . static::$DB->_quote($togroup) . " AND bank_act = " . static::$DB->_quote($account);
        $sql .= "; DELETE FROM bank_trans WHERE id=" . $togroup . " AND bank_act=" . static::$DB->_quote($account) . " AND type=" . ST_GROUPDEPOSIT;
      } elseif ($type1 == ST_GROUPDEPOSIT) {
        $group = $trans_id;
        $sql   = "UPDATE bank_trans SET undeposited=" . $trans_id . " WHERE id=" . static::$DB->_quote($togroup) . " AND bank_act = " . static::$DB->_quote($account);
      } elseif ($type2 == ST_GROUPDEPOSIT) {
        $group = $togroup;
        $sql   = "UPDATE bank_trans SET undeposited=" . $togroup . " WHERE id=" . static::$DB->_quote($trans_id) . " AND bank_act = " . static::$DB->_quote($account);
      } else {
        $group = static::createGroup($account, $date1);
        $sql   = "UPDATE bank_trans SET undeposited=" . $group . " WHERE id=" . static::$DB->_quote($trans_id) . " AND bank_act = " . static::$DB->_quote($account);
        $sql .= "; UPDATE bank_trans SET undeposited=" . $group . " WHERE id=" . static::$DB->_quote($togroup) . " AND bank_act = " . static::$DB->_quote($account);
      }
      $amount = $amount1 + $amount2;
      $sql .= "; UPDATE bank_trans SET amount=$amount WHERE id = " . static::$DB->_quote($group) . " AND type= " . ST_GROUPDEPOSIT . " AND bank_act = " . static::$DB->_quote(
        $account
      );
      return static::$DB->_query($sql, "Can't change undeposited status");
    }
    /**
     * @static
     *
     * @param $trans_id
     * @param $account
     * @param $fromgroup
     *
     * @return bool
     */
    public static function removeFromGroup($trans_id, $account, $fromgroup) {
      $trans   = static::$DB->_select('amount', 'undeposited')->from('bank_trans')->where('id=', $trans_id)->where('bank_act=', $account)->fetch()->one();
      $amount  = $trans['amount'];
      $current = $trans['undeposited'];
      if ($current != $fromgroup) {
        Event::error('Transaction is not in this group!');
        return false;
      }
      $sql = "UPDATE bank_trans SET undeposited=0 WHERE id=" . static::$DB->_quote($trans_id) . " AND bank_act = " . static::$DB->_quote($account);
      $sql .= "; UPDATE bank_trans SET amount=amount - $amount WHERE id = " . static::$DB->_quote($fromgroup);
      return static::$DB->_query($sql, "Can't change undeposited status");
    }
  }

  Bank_Undeposited::$DB    = DB::i();
  Bank_Undeposited::$Dates = Dates::i();
