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
  use ADV\Core\DB\DB;

  /**
   *
   */
  class Sales_Branch
  {
    /**
     * @static
     *
     * @param $branch_id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get($branch_id) {
      $sql
              = "SELECT branches.*,salesman.salesman_name
		FROM branches, salesman
		WHERE branches.salesman=salesman.salesman_code
		AND branch_id=" . DB::_escape($branch_id);
      $result = DB::_query($sql, "Cannot retreive a customer branch");
      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $branch_id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get_accounts($branch_id) {
      $sql
              = "SELECT receivables_account,sales_account, sales_discount_account, payment_discount_account
		FROM branches WHERE branch_id=" . DB::_escape($branch_id);
      $result = DB::_query($sql, "Cannot retreive a customer branch");
      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $branch_id
     *
     * @return mixed
     */
    public static function get_name($branch_id) {
      $sql
              = "SELECT br_name FROM branches
		WHERE branch_id = " . DB::_escape($branch_id);
      $result = DB::_query($sql, "could not retreive name for branch" . $branch_id);
      $myrow  = DB::_fetchRow($result);
      return $myrow[0];
    }
    /**
     * @static
     *
     * @param $group_no
     *
     * @return null|PDOStatement
     */
    public static function get_from_group($group_no) {
      $sql
        = "SELECT branch_id, debtor_id FROM branches
		WHERE group_no = " . DB::_escape($group_no);
      return DB::_query($sql, "could not retreive branches for group " . $group_no);
    }
    /**
     * @static
     *
     * @param $customer_no
     *
     * @return mixed
     */
    public static function get_main($customer_no) {
      $sql
              = "SELECT *
 FROM branches
 WHERE debtor_id={$customer_no}
 ORDER BY branch_id ";
      $result = DB::_query($sql, "Could not retrieve any branches");
      $myrow  = DB::_fetchAssoc($result);
      return $myrow;
    }
  }
