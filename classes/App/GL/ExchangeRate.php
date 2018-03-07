<?php
  use ADV\Core\DB\DB;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class GL_ExchangeRate {
    /**
     * @static
     *
     * @param $rate_id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get($rate_id) {
      $sql    = "SELECT * FROM exchange_rates WHERE id=" . DB::_escape($rate_id);
      $result = DB::_query($sql, "could not get exchange rate for $rate_id");
      return DB::_fetch($result);
    }
    // Retrieves buy exchange rate for given currency/date, zero if no result
    /**
     * @static
     *
     * @param $curr_code
     * @param $date_
     *
     * @return int
     */
    public static function get_date($curr_code, $date_) {
      $date   = Dates::_dateToSql($date_);
      $sql    = "SELECT rate_buy FROM exchange_rates WHERE curr_code=" . DB::_escape($curr_code) . " AND date_='$date'";
      $result = DB::_query($sql, "could not get exchange rate for $curr_code - $date_");
      if (DB::_numRows($result) == 0) {
        return 0;
      }
      $row = DB::_fetch($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param $curr_code
     * @param $date_
     * @param $buy_rate
     * @param $sell_rate
     */
    public static function update($curr_code, $date_, $buy_rate, $sell_rate) {
      if (Bank_Currency::is_company($curr_code)) {
        Event::error("Exchange rates cannot be set for company currency", "", true);
      }
      $date = Dates::_dateToSql($date_);
      $sql  = "UPDATE exchange_rates SET rate_buy=$buy_rate, rate_sell=" . DB::_escape($sell_rate) . " WHERE curr_code=" . DB::_escape($curr_code) . " AND date_='$date'";
      DB::_query($sql, "could not add exchange rate for $curr_code");
    }
    /**
     * @static
     *
     * @param $curr_code
     * @param $date_
     * @param $buy_rate
     * @param $sell_rate
     */
    public static function add($curr_code, $date_, $buy_rate, $sell_rate) {
      if (Bank_Currency::is_company($curr_code)) {
        Event::error("Exchange rates cannot be set for company currency", "", true);
      }
      $date = Dates::_dateToSql($date_);
      $sql
            = "INSERT INTO exchange_rates (curr_code, date_, rate_buy, rate_sell)
        VALUES (" . DB::_escape($curr_code) . ", '$date', " . DB::_escape($buy_rate) . ", " . DB::_escape($sell_rate) . ")";
      DB::_query($sql, "could not add exchange rate for $curr_code");
    }
    /**
     * @static
     *
     * @param $rate_id
     */
    public static function delete($rate_id) {
      $sql = "DELETE FROM exchange_rates WHERE id=" . DB::_escape($rate_id);
      DB::_query($sql, "could not delete exchange rate $rate_id");
    }
    //	Retrieve exchange rate as of date $date from external source (usually inet)
    //
    /**
     * @static
     *
     * @param $curr_b
     * @param $date
     *
     * @return float|int|mixed|string
     */
    public static function retrieve($curr_b, $date) {
      global $Hooks;
      if (method_exists($Hooks, 'retrieve_exrate')) {
        return $Hooks->retrieve_exrate($curr_b, $date);
      } else {
        return static::get_external($curr_b, 'ECB', $date);
      }
    }
    /**
     * @static
     *
     * @param        $curr_b
     * @param string $provider
     * @param        $date
     *
     * @return float|int|mixed|string
     */
    public static function get_external($curr_b, $provider = 'ECB', $date) {
      $curr_a = DB_Company::_get_pref('curr_default');
      if ($provider == 'ECB') {
        $filename = "/stats/eurofxref/eurofxref-daily.xml";
        $site     = "www.ecb.int";
      } elseif ($provider == 'YAHOO') {
        $filename = "/q?s={$curr_a}{$curr_b}=X";
        $site     = "finance.yahoo.com";
      } elseif ($provider == 'GOOGLE') {
        $filename = "/finance/converter?a=1&from={$curr_a}&to={$curr_b}";
        $site     = "finance.google.com";
      }
      $contents = '';
      if (function_exists('curl_init')) { // first check with curl as we can set short timeout;
        $retry = 1;
        do {
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, 'http://' . $site . $filename);
          curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie.txt");
          curl_setopt($ch, CURLOPT_HEADER, 0);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
          curl_setopt($ch, CURLOPT_TIMEOUT, 3);
          $contents = curl_exec($ch);
          curl_close($ch);
          // due to resolver bug in some curl versions (e.g. 7.15.5)
          // try again for constant IP.
          $site = "195.128.2.97";
        } while (($contents == '') && $retry--);
      } else {
        $handle = @fopen("http://" . $site . $filename, 'rb');
        if ($handle) {
          do {
            $data = @fread($handle, 4096);
            if (strlen($data) == 0) {
              break;
            }
            $contents .= $data; // with this syntax only text will be translated, whole text with htmlspecialchars($data)
          } while (true);
          @fclose($handle);
        } // end handle
      }
      if (!$contents) {
        Event::warning(_("Cannot retrieve currency rate from $provider page. Please set the rate manually."));
      }
      if ($provider == 'ECB') {
        $contents  = str_replace("<Cube currency='USD'", " <Cube currency='EUR' rate='1'/> <Cube currency='USD'", $contents);
        $from_mask = "|<Cube\s*currency=\'" . $curr_a . "\'\s*rate=\'([\d.,]*)\'\s*/>|i";
        preg_match($from_mask, $contents, $out);
        $val_a   = isset($out[1]) ? $out[1] : 0;
        $val_a   = str_replace(',', '', $val_a);
        $to_mask = "|<Cube\s*currency=\'" . $curr_b . "\'\s*rate=\'([\d.,]*)\'\s*/>|i";
        preg_match($to_mask, $contents, $out);
        $val_b = isset($out[1]) ? $out[1] : 0;
        $val_b = str_replace(',', '', $val_b);
        if ($val_b) {
          $val = $val_a / $val_b;
        } else {
          $val = 0;
        }
      } elseif ($provider == 'YAHOO') {
        $val = '';
        if (preg_match('/Last\sTrade:(.*?)Trade\sTime/s', $contents, $matches)) {
          $val = strip_tags($matches[1]);
          $val = str_replace(',', '', $val);
          if ($val != 0) {
            $val = 1 / $val;
          }
        }
      } elseif ($provider == 'GOOGLE') {
        $val    = '';
        $regexp = "%([\d|.]+)\s+{$curr_a}\s+=\s+<span\sclass=(.*)>([\d|.]+)\s+{$curr_b}\s*</span>%s";
        if (preg_match($regexp, $contents, $matches)) {
          $val = $matches[3];
          $val = str_replace(',', '', $val);
          if ($val != 0) {
            $val = 1 / $val;
          }
        }
      }
      return $val;
    } /* end function get_extern_rate */
    // Displays currency exchange rate for given date.
    // When there is no exrate for today,
    // gets it form ECB and stores in local database.
    //
    /**
     * @static
     *
     * @param      $from_currency
     * @param      $to_currency
     * @param      $date_
     * @param bool $edit_rate
     */
    public static function display($from_currency, $to_currency, $date_, $edit_rate = false) {
      if ($from_currency != $to_currency) {
        $comp_currency = Bank_Currency::for_company();
        if ($from_currency == $comp_currency) {
          $currency = $to_currency;
        } else {
          $currency = $from_currency;
        }
        $rate = 0;
        if ($date_ == Dates::_today()) {
          $rate = GL_ExchangeRate::get_date($currency, $date_);
          if (!$rate) {
            $row = GL_Currency::get($currency);
            if ($row['auto_update']) {
              $rate = GL_ExchangeRate::retrieve($currency, $date_);
              if ($rate) {
                GL_ExchangeRate::add($currency, $date_, $rate, $rate);
              }
            }
          }
        }
        if (!$rate) {
          $rate = Bank_Currency::exchange_rate_from_home($currency, $date_);
        }
        if ($from_currency != $comp_currency) {
          $rate = 1 / ($rate / Bank_Currency::exchange_rate_from_home($to_currency, $date_));
        }
        $rate = Num::_format($rate, User::_exrate_dec());
        if ($edit_rate) {
          Forms::textCells(_("Exchange Rate:"), '_ex_rate', $rate, 8, 8, null, "class='label'", " $from_currency = 1 $to_currency");
        } else {
          Cell::labelled(_("Exchange Rate:"), "<span style='vertical-align:top;' id='_ex_rate'>$rate</span> $from_currency = 1 $to_currency", '');
        }
        Ajax::_addUpdate('_ex_rate', '_ex_rate', $rate);
      }
    }
  }
