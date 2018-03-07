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
  namespace ADV\App;

  use ADV\Core\Ajax;
  use ADV\Core\Cache;
  use ADV\Core\DB\DB;

  /** **/
  class Validation
  {
    const CUSTOMERS         = "debtors";
    const CURRENCIES        = "currencies";
    const SALES_TYPES       = "sales_types";
    const ITEM_TAX_TYPES    = "item_tax_types";
    const TAX_TYPES         = "tax_types";
    const TAX_GROUP         = "tax_groups";
    const MOVEMENT_TYPES    = "movement_types";
    const BRANCHES          = "branches WHERE debtor_id=";
    const BRANCHES_ACTIVE   = "branches WHERE !inactive";
    const SALESPERSONS      = "salesman";
    const SALES_AREA        = "areas";
    const SHIPPERS          = "shippers";
    const OPEN_WORKORDERS   = "workorders WHERE closed=0";
    const WORKORDERS        = "workorders";
    const OPEN_DIMENSIONS   = "dimensions WHERE closed=0";
    const DIMENSIONS        = "dimensions";
    const SUPPLIERS         = "suppliers";
    const STOCK_ITEMS       = "stock_master";
    const BOM_ITEMS         = "stock_master WHERE mb_flag=";
    const MANUFACTURE_ITEMS = "stock_master WHERE mb_flag=";
    const PURCHASE_ITEMS    = "stock_master WHERE mb_flag=";
    const COST_ITEMS        = "stock_master WHERE mb_flag!=";
    const STOCK_CATEGORIES  = "stock_category";
    const WORKCENTRES       = "workcentres";
    const LOCATIONS         = "locations";
    const BANK_ACCOUNTS     = "bank_accounts";
    const CASH_ACCOUNTS     = "bank_accounts";
    const GL_ACCOUNTS       = "chart_master";
    const GL_ACCOUNT_GROUPS = "chart_types";
    const QUICK_ENTRIES     = "quick_entries";
    const TAGS              = "FROM tags WHERE type=";
    const EMPTY_RESULT      = "";
    /**
     * @static
     *
     * @param        $validate
     * @param string $msg
     * @param null   $extra
     * @param null   $default
     *
     * @return int|null
     * @throws \Exception
     */
    public static function check($validate, $msg = '', $extra = null, $default = null) {
      if ($extra === false) {
        return 0;
      }
      $cachekey = 'validation.' . md5($validate . $extra);
      if (Cache::_get($cachekey)) {
        return 1;
      }
      if ($extra !== null) {
        if (empty($extra)) {
          return $default;
        }
        if (is_string($extra)) {
          $extra = DB::_escape($extra);
        }
      } else {
        $extra = '';
      }
      $result = DB::_query('SELECT COUNT(*) FROM ' . $validate . ' ' . $extra, 'Could not do check empty query');
      $myrow  = DB::_fetchRow($result);
      if (!($myrow[0] > 0)) {
        throw new \Exception($msg);
      } else {
        Cache::_set($cachekey, true);
        return $myrow[0];
      }
    }
    //
    //	Integer input check
    //	Return 1 if number has proper form and is within <min, max> range
    //
    /**
     * @static
     *
     * @param      $postname
     * @param null $min
     * @param null $max
     *
     * @return bool|int
     */
    public static function is_int($postname, $min = null, $max = null) {
      if (!isset($_POST) || !isset($_POST[$postname])) {
        return 0;
      }
      $options = [];
      if ($min !== null) {
        $options['min_range'] = $min;
      }
      if ($max !== null) {
        $options['max_range'] = $max;
      }
      $result = filter_var($_POST[$postname], FILTER_VALIDATE_INT, $options);
      return ($result === false || $result === null) ? false : 1;
    }
    //
    //	Numeric input check.
    //	Return 1 if number has proper form and is within <min, max> range
    //	Empty/not defined fields are defaulted to $dflt value.
    //
    /**
     * @static
     *
     * @param      $postname
     * @param null $min
     * @param null $max
     * @param int  $default
     *
     * @return int
     */
    public static function post_num($postname, $min = null, $max = null, $default = 0) {
      if (!isset($_POST) || !isset($_POST[$postname])) {
        $_POST[$postname] = $default;
      }
      return Validation::is_num($_POST[$postname], $min, $max, $default);
    }
    /**
     * @static
     *
     * @param      $value
     * @param null $min
     * @param null $max
     * @param int  $default
     *
     * @return int
     */
    public static function is_num($value, $min = null, $max = null, $default = 0) {
      $result = filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
      if ($min !== null && $result < $min) {
        $result = false;
      }
      if ($max !== null && $result > $max) {
        $result = false;
      }
      return !($result === false || $result === null);
    }
    /**
     *   Read numeric value from user formatted input
     *
     * @param null $postname
     * @param int  $default
     * @param null $min
     * @param null $max
     *
     * @internal param int $dflt
     * @return bool|float|int|mixed|string
     */
    public static function input_num($postname = null, $default = 0, $min = null, $max = null) {
      if (!isset($_POST) || !isset($_POST[$postname])) {
        $_POST[$postname] = $default;
      }
      $result = filter_var($_POST[$postname], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
      if ($min !== null && $result < $min) {
        $result = false;
      }
      if ($max !== null && $result > $max) {
        $result = false;
      }
      return ($result === false || $result === null) ? 0 : $result;
    }
  }
