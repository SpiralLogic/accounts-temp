<?php
  use ADV\App\Bank\Bank;
  use ADV\App\Dates;
  use ADV\App\User;
  use ADV\Core\DB\DB;

  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Bank_Trans
  {
    public static $types
      = array(
        BT_TRANSFER => "Transfer", //
        "Cheque", //
        "Credit", //
        "Cash"
      );
    // add a bank transaction
    // $amount is in $currency
    // $date_ is display date (non-sql)
    /** @var \ADV\Core\DB\DB */
    static $DB;
    /** @var Dates */
    static $Dates;
    /**
     * @static
     *
     * @param        $type
     * @param        $trans_no
     * @param        $bank_act
     * @param        $ref
     * @param        $date_
     * @param        $amount
     * @param        $person_type_id
     * @param        $person_id
     * @param string $currency
     * @param string $err_msg
     * @param int    $rate
     */
    public static function add($type, $trans_no, $bank_act, $ref, $date_, $amount, $person_type_id, $person_id, $currency = "", $err_msg = "", $rate = 0) {
      $sqlDate = static::$Dates->dateToSql($date_);
      // convert $amount to the bank's currency
      if ($currency != "") {
        $bank_account_currency = Bank_Currency::for_company($bank_act);
        if ($rate == 0) {
          $to_bank_currency = Bank::get_exchange_rate_from_to($currency, $bank_account_currency, $date_);
        } else {
          $to_bank_currency = 1 / $rate;
        }
        $amount_bank = ($amount / $to_bank_currency);
      } else {
        $amount_bank = $amount;
      }
      // Also store the rate to the home
      //$BankToHomeCurrencyRate = Bank_Currency::exchange_rate_to_home($bank_account_currency, $date_);
      $sql = "INSERT INTO bank_trans (type, trans_no, bank_act, ref,         trans_date, amount, person_type_id, person_id, undeposited) ";
      $sql .= "VALUES ($type, $trans_no, '$bank_act', " . static::$DB->_escape($ref) . ", '$sqlDate',
        " . static::$DB->_escape($amount_bank) . ", " . static::$DB->_escape($person_type_id) . ", " . static::$DB->_escape($person_id) . ",0)";
      if ($err_msg == "") {
        $err_msg = "The bank transaction could not be inserted";
      }
      static::$DB->_query($sql, $err_msg);
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     *
     * @return bool
     */
    public static function exists($type, $type_no) {
      $sql    = "SELECT trans_no FROM bank_trans WHERE type=" . static::$DB->_escape($type) . " AND trans_no=" . static::$DB->_escape($type_no);
      $result = static::$DB->_query($sql, "Cannot retreive a bank transaction");
      return (static::$DB->_numRows($result) > 0);
    }
    /**
     * @static
     *
     * @param      $type
     * @param null $trans_no
     * @param null $person_type_id
     * @param null $person_id
     *
     * @return null|PDOStatement
     */
    public static function get($type, $trans_no = null, $person_type_id = null, $person_id = null) {
      $sql = "SELECT *, bank_account_name, account_code, bank_curr_code         FROM bank_trans, bank_accounts         WHERE bank_accounts.id=bank_trans.bank_act ";
      if ($type != null) {
        $sql .= " AND type=" . static::$DB->_escape($type);
      }
      if ($trans_no != null) {
        $sql .= " AND bank_trans.trans_no = " . static::$DB->_escape($trans_no);
      }
      if ($person_type_id != null) {
        $sql .= " AND bank_trans.person_type_id = " . static::$DB->_escape($person_type_id);
      }
      if ($person_id != null) {
        $sql .= " AND bank_trans.person_id = " . static::$DB->_escape($person_id);
      }
      $sql .= " ORDER BY trans_date, bank_trans.id";
      return static::$DB->_query($sql, "query for bank transaction");
    }
    /**
     * @static
     *
     * @param      $bank_account
     * @param      $from
     * @param null $to
     *
     * @return array|bool
     */
    public static function getPeriod($bank_account, $from, $to = null) {
      $fiscalperiod = GL_Trans::getCurrentOpenFiscalPeriod();
      $beginfiscal  = $fiscalperiod['start'];
      $sql
                    = "SELECT bt.type, bt.trans_no, bt.ref, bt.trans_date,bt.id, IF( bt.trans_no IS null, SUM( g.amount ), bt.amount ) AS amount
       , bt.person_id, bt.person_type_id , bt.reconciled FROM bank_trans bt LEFT OUTER JOIN bank_trans g ON g.undeposited = bt.id
       WHERE bt.bank_act = " . static::$DB->_quote($bank_account) . "
       AND bt.trans_date <= '" . ($to ? : static::$Dates->today()) . "'
       AND bt.undeposited<2
       AND ((bt.reconciled IS null and bt.trans_date>='" . $beginfiscal . "') ";
      if ($to) {
        $sql .= " OR bt.reconciled='" . $to . "'";
      }
      $sql .= ") AND bt.amount!=0 GROUP BY bt.id ORDER BY IF(bt.trans_date>'" . $from . "' AND bt.trans_date<='" . $to . "',1,0) , bt.reconciled DESC ,bt.trans_date , amount ";
      static::$DB->_query($sql);
      return static::$DB->_fetchAll();
    }
    /**
     * @static
     *
     * @param      $type
     * @param      $type_no
     * @param bool $nested
     */
    public static function void($type, $type_no, $nested = false) {
      if (!$nested) {
        static::$DB->_begin();
      }
      $sql
        = "UPDATE bank_trans SET amount=0
        WHERE type=" . static::$DB->_escape($type) . " AND trans_no=" . static::$DB->_escape($type_no);
      static::$DB->_query($sql, "could not void bank transactions for type=$type and trans_no=$type_no");
      GL_Trans::void($type, $type_no, true);
      // in case it's a customer trans - probably better to check first
      Sales_Allocation::void($type, $type_no);
      Debtor_Trans::void($type, $type_no);
      // in case it's a supplier trans - probably better to check first
      Purch_Allocation::void($type, $type_no);
      Creditor_Trans::void($type, $type_no);
      GL_Trans::void_tax_details($type, $type_no);
      if (!$nested) {
        static::$DB->_commit();
      }
    }
    /**
     * @static
     *
     * @param $trans_no
     * @param $type
     * @param $from
     * @param $to
     *
     * @return bool
     */
    public static function changeBankAccount($trans_no, $type, $from, $to) {
      $fromgl = Bank_Account::get_gl($from);
      $togl   = Bank_Account::get_gl($to);
      if (!$togl || !$fromgl) {
        return false;
      }
      static::$DB->_begin();
      static::$DB->_update('bank_trans')->value('bank_act', $to)->where('type=', $type)->andWhere('trans_no=', $trans_no)->andwhere('bank_act=', $from)->exec();
      static::$DB->_update('gl_trans')->value('account', $togl)->where('type=', $type)->andWhere('type_no=', $trans_no)->andWhere('account=', $fromgl)->exec();
      $sql = "UPDATE comments SET memo_ = CONCAT(memo_," . DB::_quote(
        ' <br>Bank changed from ' . $from . ' to ' . $to . ' by ' . User::_i()->username
      ) . ") WHERE id=" . DB::_quote($trans_no) . " AND type=" . DB::_quote($type);
      DB::_query($sql);
      static::$DB->_commit();
      return true;
    }
    /**
     * @static
     *
     * @param      $bank_trans_id
     * @param      $newdate
     * @param      &$status
     *
     * @return bool
     */
    public static function changeDate($bank_trans_id, $newdate, &$status = null) {
      $date   = static::$Dates->isDate($newdate);
      $status = new \ADV\Core\Status();
      if (!$date) {
        return $status->set(\ADV\Core\Status::ERROR, 'chnage date', 'Incorrect date format');
      }
      $sqldate = static::$Dates->dateToSql($date);
      $row     = static::$DB->_select('*')->from('bank_trans')->where('id=', $bank_trans_id)->fetch()->one();
      static::$DB->_begin();
      try {
        switch ($row['type']) {
          case ST_CUSTPAYMENT:
          case ST_BANKDEPOSIT:
            static::$DB->_update('debtor_trans')->value('tran_date', $sqldate)->where('trans_no=', $row['trans_no'])->andWhere('type=', $row['type'])->exec();
            static::$DB->_update('bank_trans')->value('trans_date', $sqldate)->where('id=', $row['id'])->exec();
            static::$DB->_update('gl_trans')->value('tran_date', $sqldate)->where('type_no=', $row['trans_no'])->andWhere('type=', $row['type'])->exec();
            break;
          case ST_SUPPAYMENT:
          case ST_BANKPAYMENT:
            static::$DB->_update('creditor_trans')->value('tran_date', $sqldate)->where('trans_no=', $row['trans_no'])->andWhere('type=', $row['type'])->exec();
            static::$DB->_update('bank_trans')->value('trans_date', $sqldate)->where('id=', $row['id'])->exec();
            static::$DB->_update('gl_trans')->value('tran_date', $sqldate)->where('type_no=', $row['trans_no'])->andWhere('type=', $row['type'])->exec();
            break;
          case ST_GROUPDEPOSIT:
            static::$DB->_update('bank_trans')->value('trans_date', $sqldate)->where('id=', $row['id'])->exec();
            break;
          case ST_BANKTRANSFER:
            static::$DB->_update('bank_trans')->value('trans_date', $sqldate)->where('trans_no=', $row['trans_no'])->andWhere('type=', $row['type'])->exec();
            static::$DB->_update('gl_trans')->value('tran_date', $sqldate)->where('type_no=', $row['trans_no'])->andWhere('type=', $row['type'])->exec();
            break;
          default:
            static::$DB->_cancel();
            $status->set(\ADV\Core\Status::ERROR, 'chnage date', $bank_trans_id . 'Cannot change date for this transaction');
        }
        $sql = "UPDATE comments SET memo_ = CONCAT(memo_,'" . ' <br>Date changed from ' . $row['trans_date'] . ' to ' . $sqldate . ' by ' . User::_i(
        )->username . "') WHERE id=" . DB::_quote($row["trans_no"]) . " AND type=" . DB::_quote($row['type']);
        DB::_query($sql);
        static::$DB->_commit();
        return true;
      } catch (\ADV\Core\DB\DBException $e) {
        static::$DB->_cancel();
        $status->set(\ADV\Core\Status::ERROR, 'change date', 'Database error: ' . $e->getMessage());
      }
      return false;
    }
    /**
     * @param $bank_account
     * @param $groupid
     *
     * @return null|PDOStatement
     */
    public static function getGroupDeposit($bank_account, $groupid) {
      $sql
        = "SELECT bank_trans.ref,bank_trans.person_type_id,bank_trans.trans_no,bank_trans.person_id,bank_trans.amount,
     			comments.memo_ FROM bank_trans LEFT JOIN comments ON (bank_trans.type=comments.type AND bank_trans.trans_no=comments.id)
     			WHERE bank_trans.bank_act=" . static::$DB->_quote(
        $bank_account
      ) . " AND bank_trans.type != " . ST_GROUPDEPOSIT . " AND bank_trans.undeposited>0 AND (undeposited = " . static::$DB->_quote($groupid) . ")";
      return static::$DB->_query($sql, 'Couldn\'t get deposit references');
    }
    /**
     * @param $trans_no
     * @param $type
     *
     * @return PDOStatement
     */
    public static function getInfo($trans_no, $type) {
      $sql
        = "SELECT memo_ from gl_trans WHERE type = " . static::$DB->_quote($type) . " AND type_no = " . static::$DB->_quote($trans_no);
      return static::$DB->_query($sql, 'Couldn\'t get deposit references');
    }
  }

  Bank_Trans::$DB    = DB::i();
  Bank_Trans::$Dates = Dates::i();
