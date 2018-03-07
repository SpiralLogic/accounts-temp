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
  namespace ADV\App\Bank {
    use ADV\Core\DB\DB;

    /**

     */
    class Currency extends \ADV\App\DB\Base implements \ADV\App\Pager\Pageable
    {
      protected $_table = 'currencies';
      protected $_classname = 'Currency';
      protected $_id_column = 'id';
      public $id;
      public $currency;
      public $curr_abrev;
      public $curr_symbol;
      public $country;
      public $hundreds_name;
      public $inactive = 0;
      public $auto_update = 1;
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      protected function canProcess() {
        if (strlen($this->curr_abrev) == 0) {
          return $this->status(false, "The currency abbreviation must be entered.", 'curr_abrev');
        }
        if (strlen($this->currency) == 0) {
          return $this->status(false, "The currency name must be entered.");
        }
        if (strlen($this->curr_symbol) == 0) {
          return $this->status(false, "The currency symbol must be entered.");
        }
        if (strlen($this->hundreds_name) == 0) {
          return $this->status(false, "The hundredths name must be entered.");
        }
        if (strlen($this->currency) > 60) {
          return $this->status(false, 'Currency must be not be longer than 60 characters!', 'currency');
        }
        if (strlen($this->curr_symbol) > 10) {
          return $this->status(false, 'Curr Symbol must be not be longer than 10 characters!', 'curr_symbol');
        }
        if (strlen($this->country) > 100) {
          return $this->status(false, 'Country must be not be longer than 100 characters!', 'country');
        }
        if (strlen($this->hundreds_name) > 15) {
          return $this->status(false, 'Hundreds Name must be not be longer than 15 characters!', 'hundreds_name');
        }
        return true;
      }
      /**
       * @return \ADV\Core\Traits\Status|bool
       */
      public function delete() {
        $count = DB::_select('count(*) as count')->from('debtors')->where('curr_code=', $this->curr_abrev)->fetch()->one('count');
        if ($count) {
          return $this->status(false, "Cannot delete this currency, because customer accounts have been created referring to this currency.");
        }
        $count = DB::_select('count(*) as count')->from('suppliers')->where('curr_code=', $this->curr_abrev)->fetch()->one('count');
        if ($count) {
          return $this->status(false, "Cannot delete this currency, because supplier accounts have been created referring to this currency.");
        }
        $count = DB::_select('count(*) as count')->from('company')->where('curr_default=', $this->curr_abrev)->fetch()->one('count');
        if ($count) {
          return $this->status(false, "Cannot delete this currency, because the company preferences uses this currency.");
        }
        // see if there are any bank accounts that use this currency
        $count = DB::_select('count(*) as count')->from('bank_accounts')->where('bank_curr_code=', $this->curr_abrev)->fetch()->one('count');
        if ($count) {
          return $this->status(false, "Cannot delete this currency, because thre are bank accounts that use this currency.");
        }
        return parent::delete();
      }
      /**
       * @param bool $inactive
       *
       * @return array
       */
      public static function getAll($inactive = false) {
        $q = DB::_select()->from('currencies');
        if (!$inactive) {
          $q->andWhere('inactive=', 0);
        }
        return $q->fetch()->all();
      }
      /**
       * @return array
       */
      public function getPagerColumns() {
        return [
          ['type' => "skip"],
          'Currency',
          'Abbreviation',
          'Symbol',
          'Country',
          'Hundreds',
          'Inactive' => ['type' => 'inactive'],
          'Auto Update',
        ];
      }
    }
  }

  namespace {
    use ADV\Core\DB\DB;
    use ADV\Core\DB\DBSelectException;
    use ADV\Core\Num;
    use ADV\Core\Event;
    use ADV\App\Dates;
    use ADV\App\User;

    /**

     */
    class Bank_Currency
    {
      /**
       * @static
       *
       * @param $currency
       *
       * @return bool
       */
      public static function is_company($currency) {
        return (static::for_company() == $currency);
      }
      /**
       * @static
       * @return bool
       */
      public static function for_company() {
        try {
          $result = DB::_select('curr_default')->from('company')->fetch()->one();
          return $result['curr_default'];
        } catch (DBSelectException $e) {
          Event::error('Could not get company currency');
        }
        return false;
      }
      /**
       * @static
       *
       * @param $curr_code
       */
      public static function clear_default($curr_code) {
        $sql = "UPDATE bank_accounts SET dflt_curr_act=0 WHERE bank_curr_code=" . DB::_escape($curr_code);
        DB::_query($sql, "could not update default currency account");
      }
      /**
       * @static
       *
       * @param $id
       *
       * @return mixed
       */
      public static function for_bank_account($id) {
        $sql    = "SELECT bank_curr_code FROM bank_accounts WHERE id='$id'";
        $result = DB::_query($sql, "retreive bank account currency");
        $myrow  = DB::_fetchRow($result);
        return $myrow[0];
      }
      /**
       * @static
       *
       * @param $debtor_id
       *
       * @return mixed
       */
      public static function for_debtor($debtor_id) {
        $sql    = "SELECT curr_code FROM debtors WHERE debtor_id = '$debtor_id'";
        $result = DB::_query($sql, "Retreive currency of customer $debtor_id");
        $myrow  = DB::_fetchRow($result);
        return $myrow[0];
      }
      /**
       * @static
       *
       * @param $creditor_id
       *
       * @return mixed
       */
      public static function for_creditor($creditor_id) {
        $sql    = "SELECT curr_code FROM suppliers WHERE creditor_id = '$creditor_id'";
        $result = DB::_query($sql, "Retreive currency of supplier $creditor_id");
        $myrow  = DB::_fetchRow($result);
        return $myrow[0];
      }
      /**
       * @static
       *
       * @param $type
       * @param $person_id
       *
       * @return bool
       */
      public static function for_payment_person($type, $person_id) {
        switch ($type) {
          case PT_MISC :
          case PT_QUICKENTRY :
          case PT_WORKORDER :
            return Bank_Currency::for_company();
          case PT_CUSTOMER :
            return Bank_Currency::for_debtor($person_id);
          case PT_SUPPLIER :
            return Bank_Currency::for_creditor($person_id);
          default :
            return Bank_Currency::for_company();
        }
      }
      /**
       * @static
       *
       * @param $currency_code
       * @param $date_
       *
       * @return float
       */
      public static function exchange_rate_from_home($currency_code, $date_) {
        if ($currency_code == static::for_company() || $currency_code == null) {
          return 1.0000;
        }
        $date = Dates::_dateToSql($date_);
        $sql
                = "SELECT rate_buy, max(date_) as date_ FROM exchange_rates WHERE curr_code = '$currency_code'
                        AND date_ <= '$date' GROUP BY rate_buy ORDER BY date_ Desc LIMIT 1";
        $result = DB::_query($sql, "could not query exchange rates");
        if (DB::_numRows($result) == 0) {
          // no stored exchange rate, just return 1
          Event::error(sprintf(_("Cannot retrieve exchange rate for currency %s as of %s. Please add exchange rate manually on Exchange Rates page."), $currency_code, $date_));
          return 1.000;
        }
        $myrow = DB::_fetchRow($result);
        return $myrow[0];
      }
      /**
       * @static
       *
       * @param $currency_code
       * @param $date_
       *
       * @return float
       */
      public static function exchange_rate_to_home($currency_code, $date_) {
        return 1 / static::exchange_rate_from_home($currency_code, $date_);
      }
      /**
       * @static
       *
       * @param $amount
       * @param $currency_code
       * @param $date_
       *
       * @return float
       */
      public static function to_home($amount, $currency_code, $date_) {
        $ex_rate = static::exchange_rate_to_home($currency_code, $date_);
        return Num::_round($amount / $ex_rate, User::_price_dec());
      }
    }
  }
