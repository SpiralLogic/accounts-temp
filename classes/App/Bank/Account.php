<?php
  use ADV\Core\DB\DB;
  use ADV\App\Forms;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Bank_Account
  {
    static $types
      = array(
        BT_TRANSFER => "Savings Account", //
        "Chequing Account", //
        " Credit Account", //
        " Cash Account"
      );
    /**
     * @static
     *
     * @param $account_code
     * @param $account_type
     * @param $bank_account_name
     * @param $bank_name
     * @param $bank_account_number
     * @param $bank_address
     * @param $bank_curr_code
     * @param $dflt_curr_act
     */
    public static function add($account_code, $account_type, $bank_account_name, $bank_name, $bank_account_number, $bank_address, $bank_curr_code, $dflt_curr_act) {
      if ($dflt_curr_act) // only one default account for any currency
      {
        Bank_Currency::clear_default($bank_curr_code);
      }
      $sql
        = "INSERT INTO bank_accounts (account_code, account_type,
        bank_account_name, bank_name, bank_account_number, bank_address,
        bank_curr_code, dflt_curr_act)
        VALUES (" . DB::_escape($account_code) . ", " . DB::_escape($account_type) . ", " . DB::_escape($bank_account_name) . ", " . DB::_escape($bank_name) . ", " . DB::_escape(
        $bank_account_number
      ) . "," . DB::_escape($bank_address) . ", " . DB::_escape($bank_curr_code) . ", " . DB::_escape($dflt_curr_act) . ")";
      DB::_query($sql, "could not add a bank account for $account_code");
    }
    /**
     * @static
     *
     * @param $id
     * @param $account_code
     * @param $account_type
     * @param $bank_account_name
     * @param $bank_name
     * @param $bank_account_number
     * @param $bank_address
     * @param $bank_curr_code
     * @param $dflt_curr_act
     */
    public static function update($id, $account_code, $account_type, $bank_account_name, $bank_name, $bank_account_number, $bank_address, $bank_curr_code, $dflt_curr_act) {
      if ($dflt_curr_act) // only one default account for any currency
      {
        Bank_Currency::clear_default($bank_curr_code);
      }
      $sql = "UPDATE bank_accounts	SET account_type = " . DB::_escape($account_type) . ",
        account_code=" . DB::_escape($account_code) . ",
        bank_account_name=" . DB::_escape($bank_account_name) . ", bank_name=" . DB::_escape($bank_name) . ",
        bank_account_number=" . DB::_escape($bank_account_number) . ", bank_curr_code=" . DB::_escape($bank_curr_code) . ",
        bank_address=" . DB::_escape($bank_address) . ",
        dflt_curr_act=" . DB::_escape($dflt_curr_act) . " WHERE id = " . DB::_escape($id);
      DB::_query($sql, "could not update bank account for $account_code");
    }
    /**
     * @static
     *
     * @param $id
     */
    public static function delete($id) {
      $sql = "DELETE FROM bank_accounts WHERE id=" . DB::_escape($id);
      DB::_query($sql, "could not delete bank account for $id");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get($id) {
      $sql    = "SELECT * FROM bank_accounts WHERE id=" . DB::_escape($id);
      $result = DB::_query($sql, "could not retreive bank account for $id");
      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    public static function get_gl($id) {
      $sql          = "SELECT account_code FROM bank_accounts WHERE id=" . DB::_escape($id);
      $result       = DB::_query($sql, "could not retreive bank account for $id");
      $bank_account = DB::_fetch($result);
      return isset($bank_account['account_code']) ? $bank_account['account_code'] : false;
    }
    /**
     * @return array
     */
    public static function getAll() {
      $bank_accounts = [];
      $result        = DB::_query("SELECT bank_accounts.id, bank_account_name name FROM bank_accounts WHERE !inactive")->fetchAll();
      foreach ($result as $acc) {
        $bank_accounts[$acc['id']] = $acc['name'];
      }
      return $bank_accounts;
    }
    /**
     * @static
     *
     * @param $curr
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get_default($curr = null) {
      /* default bank account is selected as first found account from:
        . default account in $curr if any
        . first defined account in $curr if any
        . default account in home currency
        . first defined account in home currency
      */
      $home_curr = DB_Company::_get_pref('curr_default');
      if (!$curr) {
        $curr = $home_curr;
      }
      $sql    = "SELECT b.*, b.bank_curr_code='$home_curr' as fall_back FROM " . "bank_accounts b" . " WHERE b.bank_curr_code=" . DB::_escape($curr) . " OR b.bank_curr_code='$home_curr'
        ORDER BY fall_back, dflt_curr_act desc";
      $result = DB::_query($sql, "could not retreive default bank account");
      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $cust_id
     *
     * @return mixed
     */
    public static function get_customer_default($cust_id) {
      $sql    = "SELECT curr_code FROM debtors WHERE debtor_id=" . DB::_escape($cust_id);
      $result = DB::_query($sql, "could not retreive default customer currency code");
      $row    = DB::_fetchRow($result);
      $ba     = static::get_default($row[0]);
      return $ba['id'];
    }
    /**
     * @param null $id
     *
     * @return mixed
     */
    public static function hasStatements($id = null) {
      $id     = $id ? : static::get_default()['id'];
      $result = DB::_select('count(*) as count')->from('temprec')->where('bank_account_id=', $id)->fetch()->one();
      return $result['count'];
    }
    /**
     * @static
     *
     * @param $account_code
     *
     * @return bool
     */
    public static function is($account_code) {
      $sql    = "SELECT id FROM bank_accounts WHERE account_code='$account_code'";
      $result = DB::_query($sql, "checking account is bank account");
      if (DB::_numRows($result) > 0) {
        $acct = DB::_fetch($result);
        return $acct['id'];
      } else {
        return false;
      }
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     *
     * @return string
     */
    public static function  select($name, $selected_id = null, $submit_on_change = false) {
      $sql = "SELECT bank_accounts.id, bank_account_name, bank_curr_code, inactive FROM bank_accounts";
      return Forms::selectBox(
        $name, $selected_id, $sql, 'id', 'bank_account_name', array(
                                                                   'format'        => 'Forms::addCurrFormat',
                                                                   'select_submit' => $submit_on_change,
                                                                   'async'         => false
                                                              )
      );
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     */
    public static function  cells($label, $name, $selected_id = null, $submit_on_change = false) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Bank_Account::select($name, $selected_id, $submit_on_change);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     */
    public static function  row($label, $name, $selected_id = null, $submit_on_change = false) {
      echo "<tr><td class='label'>$label</td>";
      Bank_Account::cells(null, $name, $selected_id, $submit_on_change);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     *
     * @return string
     */
    public static function  type($name, $selected_id = null) {
      $bank_account_types = Bank_Account::$types;
      return Forms::arraySelect($name, $selected_id, $bank_account_types);
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    public static function  type_cells($label, $name, $selected_id = null) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Bank_Account::type($name, $selected_id);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    public static function  type_row($label, $name, $selected_id = null) {
      echo "<tr><td class='label'>$label</td>";
      Bank_Account::type_cells(null, $name, $selected_id);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param $bank_account
     * @param $begin_date
     * @param $end_date
     *
     * @return array
     */
    public static function getBalances($bank_account, $begin_date, $end_date) {
      $begin_date = static::checkBeginDate($begin_date);
      $sql        = "(select (rb - amount) as amount from temprec where date>" . $begin_date . " and date<=" . DB::_quote($end_date) . " and bank_account_id=" . DB::_quote($bank_account) . " order by date, id desc limit 0,
          1)";
      $result     = DB::_query($sql);
      //var_dump($sql);
      $begin_balance = DB::_fetch($result)['amount'];
      $sql           = "(select rb as amount from temprec where date>" . $begin_date . " and date<=" . DB::_quote($end_date) . " and bank_account_id=" . DB::_quote(
        $bank_account
      ) . " order by date desc, id asc limit 0,1)";
      $result        = DB::_query($sql);
      $end_balance   = DB::_fetch($result)['amount'];
      return array($begin_balance, $end_balance);
    }
    /**
     * @static
     *
     * @param $begin_date
     *
     * @return string
     */
    protected static function checkBeginDate($begin_date) {
      return $begin_date ? DB::_quote($begin_date) : "(SELECT max(reconciled) from bank_trans)";
    }
    /**
     * @param $bank_account
     * @param $begin_date
     * @param $end_date
     *
     * @return mixed
     */
    public static function getStatement($bank_account, $begin_date, $end_date) {
      $begin_date = static::checkBeginDate($begin_date);
      $sql        = "SELECT date as state_date, amount as state_amount,memo,id as state_id, reconciled_to_id FROM temprec WHERE date > " . $begin_date . " AND date <=" . DB::_quote(
        $end_date
      ) . " and bank_account_id=" . DB::_quote($bank_account) . " ORDER BY reconciled_to_id DESC, date ,amount";
      DB::_query($sql);
      $statement_trans = DB::_fetchAll() ? : [];
      return $statement_trans;
    }
  }
