<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  namespace ADV\App\GL {
    use ADV\App\Pager\Pageable;
    use DB_Company;
    use ADV\Core\DB\DB;
    use ADV\Core\Config;
    use ADV\App\Validation;

    /**

     */
    class Account extends \ADV\App\DB\Base implements Pageable {
      protected $_table = 'chart_master';
      protected $_classname = 'GL Account';
      protected $_id_column = 'account_code';
      public $account_code;
      public $account_code2;
      public $account_name;
      public $account_type;
      public $inactive = 0;
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      protected function canProcess() {
        if (strlen($this->account_code) == 0) {
          return $this->status(false, 'The account code must be entered.', 'account_code');
        }
        if (strlen($this->account_name) == 0) {

          return $this->status(false, 'The account name cannot be empty.', 'account_name');
        }
        if (!Config::_get('accounts.allowcharacters') && !is_numeric($this->account_code)) {

          return $this->status(false, 'The account code must be numeric.', 'account_code');
        }
        if (Config::_get('accounts.allowcharacters') == 2) {
          $this->account_code = strtoupper($this->account_code);
        }
        if (strlen($this->account_code2) > 11) {
          return $this->status(false, 'Account Code2 must be not be longer than 11 characters!', 'account_code2');
        }
        if (strlen($this->account_name) > 60) {
          return $this->status(false, 'Account Name must be not be longer than 60 characters!', 'account_name');
        }
        if (!Validation::is_num($this->account_type, 0)) {
          return $this->status(false, 'Must have account type', 'account_type');
        }

        return true;
      }
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      public function delete() {
        $result = $this->DB->select("COUNT(*) as count")->from('gl_trans')->where('account=', $this->account_code)->fetch()->one('count');
        if ($result > 0) {
          return $this->status(false, "Cannot delete this account because transactions have been created using this account.");
        }
        $prefs    = DB_Company::get_prefs();
        $accounts = [
          'debtors_act',
          'pyt_discount_act',
          'creditors_act',
          'bank_charge_act',
          'exchange_diff_act',
          'profit_loss_year_act',
          'retained_earnings_act',
          'freight_act',
          'default_sales_act',
          'default_sales_discount_act',
          'default_prompt_payment_act',
          'default_inventory_act',
          'default_cogs_act',
          'default_adj_act',
          'default_inv_sales_act',
          'default_assembly_act'
        ];
        foreach ($accounts as $account) {
          if ($prefs[$account] == $this->account_code) {
            return $this->status(false, "Cannot delete this account because it is used as one of the company default GL accounts.");
          }
        }
        $result = $this->DB->select("COUNT(*) as count")->from('bank_accounts')->where('account_code=', $this->account_code)->fetch()->one('count');
        if ($result > 0) {
          return $this->status(false, "Cannot delete this account because it is used by a bank account.");
        }
        $result = $this->DB->select("COUNT(*) as count")->from('stock_master') //
          ->where('inventory_account=', $this->account_code)//
          ->orWhere('inventory_account=', $this->account_code)//
          ->orWhere('cogs_account=', $this->account_code)//
          ->orWhere('adjustment_account=', $this->account_code)//
          ->orWhere('sales_account=', $this->account_code)//
          ->fetch()->one(
          'count'
        );
        if ($result > 0) {
          return $this->status(false, "Cannot delete this account because it is used by one or more Items.");
        }
        $result = $this->DB->select("COUNT(*) as count")->from('tax_types') //
          ->where('sales_gl_code=', $this->account_code)//
          ->orWhere('purchasing_gl_code=', $this->account_code)//
          ->fetch()->one('count');
        if ($result > 0) {
          return $this->status(false, "Cannot delete this account because it is used by one or more Taxes.");
        }
        $result = $this->DB->select("COUNT(*) as count")->from('branches') //
          ->where('sales_account=', $this->account_code)//
          ->orWhere('sales_discount_account=', $this->account_code)//
          ->orWhere('receivables_account=', $this->account_code)//
          ->orWhere('payment_discount_account=', $this->account_code)//
          ->fetch()->one('count');
        if ($result > 0) {
          return $this->status(false, "Cannot delete this account because it is used by one or more Customer Branches.");
        }
        $result = $this->DB->select("COUNT(*) as count")->from('suppliers') //
          ->where('purchase_account=', $this->account_code)//
          ->orWhere('payment_discount_account=', $this->account_code)//
          ->orWhere('payable_account=', $this->account_code)//
          ->fetch()->one('count');
        if ($result > 0) {
          return $this->status(false, "Cannot delete this account because it is used by one or more suppliers.");
        }
        $result = $this->DB->select("COUNT(*) as count")->from('quick_entry_lines') //
          ->where('dest_id=', $this->account_code)//
          ->andWhere("UPPER(LEFT(action, 1)) <> 'T'")//
          ->fetch()->one('count');
        if ($result > 0) {
          return $this->status(false, "Cannot delete this account because it is used by one or more Quick Entry Lines.");
        }
        return parent::delete();
      }
      /**
       * @param bool $inactive
       *
       * @return array
       */
      public static function getAll($inactive = false) {
        $q = DB::_select('t.name as type', 'c.account_name', 'c.account_code', 'c.account_code2', 'c.inactive', 't.id as type_id')->from('chart_master c', 'chart_types t')->where(
          'c.account_type=t.id'
        );
        if (!$inactive) {
          $q->andWhere('c.inactive=', 0);
        }

        return $q->fetch()->all();
      }
      /**
       * @return array
       */
      public function getPagerColumns() {
        return  [
                  'Type',
                  'Account Name',
                  'Account Code',
                  'Account Code 2',
                  'Inactive'=>['type'=>'inactive'],
                  ['type'=>'skip'],
                ];
      }
    }
  }
  namespace {

    use ADV\Core\DB\DB;
    use ADV\Core\Event;
    use ADV\App\Dates;

    /**

     */
    class GL_Account {
      /**
       * @static
       *
       * @param $account_code
       * @param $account_name
       * @param $account_type
       * @param $account_code2
       *
       * @return null|PDOStatement
       */
      public static function add($account_code, $account_name, $account_type, $account_code2) {
        $sql
          = "INSERT INTO chart_master (account_code, account_code2, account_name, account_type)
        VALUES (" . DB::_escape($account_code) . ", " . DB::_escape($account_code2) . ", " . DB::_escape($account_name) . ", " . DB::_escape($account_type) . ")";

        return DB::_query($sql);
      }
      /**
       * @static
       *
       * @param $account_code
       * @param $account_name
       * @param $account_type
       * @param $account_code2
       *
       * @return null|PDOStatement
       */
      public static function update($account_code, $account_name, $account_type, $account_code2) {
        $sql = "UPDATE chart_master SET account_name=" . DB::_escape($account_name) . ",account_type=" . DB::_escape($account_type) . ", account_code2=" . DB::_escape(
          $account_code2
        ) . " WHERE account_code = " . DB::_escape($account_code);

        return DB::_query($sql);
      }
      /**
       * @static
       *
       * @param $code
       */
      public static function delete($code) {
        $sql = "DELETE FROM chart_master WHERE account_code=" . DB::_escape($code);
        DB::_query($sql, "could not delete gl account");
      }
      /**
       * @static
       *
       * @param null $from
       * @param null $to
       * @param null $type
       *
       * @return null|PDOStatement
       */
      public static function getAll($from = null, $to = null, $type = null) {
        $sql
          = "SELECT chart_master.*,chart_types.name AS AccountTypeName
                FROM chart_master,chart_types
                WHERE chart_master.account_type=chart_types.id";
        if ($from != null) {
          $sql .= " AND chart_master.account_code >= " . DB::_escape($from);
        }
        if ($to != null) {
          $sql .= " AND chart_master.account_code <= " . DB::_escape($to);
        }
        if ($type != null) {
          $sql .= " AND account_type=" . DB::_escape($type);
        }
        $sql .= " ORDER BY account_code";

        return DB::_query($sql, "could not get gl accounts");
      }
      /**
       * @static
       *
       * @param $code
       *
       * @return \ADV\Core\DB\Query\Result|Array
       */
      public static function get($code) {
        $sql    = "SELECT * FROM chart_master WHERE account_code=" . DB::_escape($code);
        $result = DB::_query($sql, "could not get gl account");

        return DB::_fetch($result);
      }
      /**
       * @static
       *
       * @param      $reconcile_id
       * @param      $reconcile_value
       * @param      $reconcile_date
       * @param      $end_balance
       * @param      $bank_account
       * @param null $state_id
       */
      public static function update_reconciled_values($reconcile_id, $reconcile_value, $reconcile_date, $end_balance, $bank_account, $state_id = null) {
        $sql = "UPDATE bank_trans SET reconciled=$reconcile_value WHERE id=" . DB::_quote($reconcile_id);
        if ($state_id > -1) {
          $sql .= "; UPDATE temprec SET reconciled_to_id=" . ($reconcile_value != 'null' ? DB::_quote($reconcile_id) : 'null') . " WHERE id=" . DB::_quote($state_id);
        }
        DB::_query($sql, "Can't change reconciliation status");
        // save last reconcilation status (date, end balance)
        $sql2 = "UPDATE bank_accounts SET last_reconciled_date='" . Dates::_dateToSql($reconcile_date) . "', ending_reconcile_balance=$end_balance WHERE id=" . DB::_quote(
          $bank_account
        );
        DB::_query($sql2, "Error updating reconciliation information");
      }
      /**
       * @static
       *
       * @param $date
       * @param $bank_account
       *
       * @return null|PDOStatement
       */
      public static function get_max_reconciled($date, $bank_account) {
        $date = Dates::_dateToSql($date);
        if ($date == 0) {
          $date = '0000-00-00';
        }
        $sql
          = "SELECT MAX(reconciled) as last_date,
        SUM(IF(reconciled<='$date', amount, 0)) as end_balance,
        SUM(IF(reconciled<'$date', amount, 0)) as beg_balance,
        SUM(amount) as total
        FROM bank_trans trans
        WHERE undeposited=0 AND bank_act=" . DB::_escape($bank_account) . " AND trans.reconciled IS NOT null";

        return DB::_query($sql, "Cannot retrieve reconciliation data");
      }
      /**
       * @static
       *
       * @param $bank_account
       * @param $bank_date
       *
       * @return \ADV\Core\DB\Query\Result|Array
       */
      public static function get_ending_reconciled($bank_account, $bank_date) {
        $sql
                = "SELECT ending_reconcile_balance
        FROM bank_accounts WHERE id=" . DB::_escape($bank_account) . " AND last_reconciled_date=" . DB::_escape($bank_date);
        $result = DB::_query($sql, "Cannot retrieve last reconciliation");

        return DB::_fetch($result);
      }
      /**
       * @static
       *
       * @param $bank_account
       * @param $date
       *
       * @return string
       */
      public static function get_sql_for_reconcile($bank_account, $date) {
        $sql
          = "
      SELECT bt.type, bt.trans_no, bt.ref, bt.trans_date, IF( bt.trans_no IS null,
      SUM( g.amount ), bt.amount ) AS amount, bt.person_id, bt.person_type_id, bt.reconciled, bt.id
            FROM bank_trans bt
            LEFT OUTER JOIN bank_trans g ON g.undeposited = bt.id
            WHERE   bt.bank_act = " . DB::_quote($bank_account) . " AND bt.trans_date <= '" . Dates::_dateToSql($date) . "' AND bt.undeposited<2
            AND (bt.reconciled IS null OR bt.reconciled='" . Dates::_dateToSql($date) . "') AND bt.amount!=0 GROUP BY bt.id ORDER BY bt.trans_date ASC";

        return $sql;
      }
      /**
       * @static
       *
       * @param $bank_account
       * @param $date
       *
       * @return null|\PDOStatement
       */
      public static function reset_sql_for_reconcile($bank_account, $date) {
        $sql = "UPDATE	reconciled FROM bank_trans WHERE bank_trans.bank_act = " . DB::_escape($bank_account) . " AND undeposited = 0 AND reconciled = '" . Dates::_dateToSql(
          $date
        ) . "'";

        return DB::_query($sql);
      }
      /**
       * @static
       *
       * @param $code
       *
       * @return bool
       */
      public static function is_balancesheet($code) {
        $sql    = "SELECT chart_class.ctype FROM chart_class, " . "chart_types, chart_master
        WHERE chart_master.account_type=chart_types.id AND
        chart_types.class_id=chart_class.cid
        AND chart_master.account_code=" . DB::_escape($code);
        $result = DB::_query($sql, "could not retreive the account class for $code");
        $row    = DB::_fetchRow($result);

        return $row[0] > 0 && $row[0] < CL_INCOME;
      }
      /**
       * @static
       *
       * @param $code
       *
       * @return mixed
       */
      public static function get_name($code) {
        $sql    = "SELECT account_name from chart_master WHERE account_code=" . DB::_escape($code);
        $result = DB::_query($sql, "could not retreive the account name for $code");
        if (DB::_numRows($result) == 1) {
          $row = DB::_fetchRow($result);

          return $row[0];
        }
        Event::error("could not retreive the account name for $code", $sql);

        return false;
      }
      /**
       * @param $bank_account
       * @param $reconcile_date
       *
       * @return mixed
       */
      public static function get_reconcile_start($bank_account, $reconcile_date) {
        $sql
                = "SELECT reconciled as start_date FROM bank_trans
                                   WHERE bank_act=" . DB::_escape($bank_account) . " AND reconciled IS NOT null AND amount!=0 AND reconciled <" . DB::_quote(
          $reconcile_date
        ) . " ORDER BY reconciled DESC LIMIT 1";
        $result = DB::_query($sql);
        $row    = DB::_fetch($result);

        return $row['start_date'];
      }
    }
  }
