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
  class Printer
  {
    /**
     * @static
     *
     * @param $id
     * @param $name
     * @param $descr
     * @param $queue
     * @param $host
     * @param $port
     * @param $timeout
     *
     * @return null|PDOStatement
     */
    public static function write_def($id, $name, $descr, $queue, $host, $port, $timeout) {
      if ($id > 0) {
        $sql = "UPDATE printers SET description=" . DB::_escape($descr) . ",name=" . DB::_escape($name) . ",queue=" . DB::_escape($queue) . ",host=" . DB::_escape(
          $host
        ) . ",port=" . DB::_escape($port) . ",timeout=" . DB::_escape($timeout) . " WHERE id=" . DB::_escape($id);
      } else {
        $sql = "INSERT INTO printers (" . "name,description,queue,host,port,timeout) " . "VALUES (" . DB::_escape($name) . "," . DB::_escape($descr) . "," . DB::_escape(
          $queue
        ) . "," . DB::_escape($host) . "," . DB::_escape($port) . "," . DB::_escape($timeout) . ")";
      }

      return DB::_query($sql, "could not write printer definition");
    }
    /**
     * @static
     * @return null|PDOStatement
     */
    public static function getAll() {
      $sql = "SELECT * FROM printers";

      return DB::_query($sql, "could not get printer definitions");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get($id) {
      $sql    = "SELECT * FROM printers WHERE id=" . DB::_escape($id);
      $result = DB::_query($sql, "could not get printer definition");

      return DB::_fetch($result);
    }
    //============================================================================
    // printer profiles public static functions
    //
    /**
     * @static
     *
     * @param $name
     * @param $dest
     *
     * @return bool
     */
    public static function update_profile($name, $dest) {
      foreach ($dest as $rep => $printer) {
        if ($printer != '' || $rep == '') {
          $sql = "REPLACE INTO print_profiles " . "(profile, report, printer) VALUES (" . DB::_escape($name) . "," . DB::_escape($rep) . "," . DB::_escape($printer) . ")";
        } else {
          $sql = "DELETE FROM print_profiles WHERE (" . "report=" . DB::_escape($rep) . " AND profile=" . DB::_escape($name) . ")";
        }
        $result = DB::_query($sql, "could not update printing profile");
        if (!$result) {
          return false;
        }
      }

      return true;
    }
    //
    //	Get destination for report defined in given printing profile.
    //
    /**
     * @static
     *
     * @param $profile
     * @param $report
     *
     * @return \ADV\Core\DB\Query\Result|Array|bool
     */
    public static function get_report($profile, $report) {
      $sql    = "SELECT printer FROM print_profiles WHERE profile=" . DB::_quote($profile) . " AND report=" . DB::_quote($report);
      $result = DB::_query($sql, 'report printer lookup failed');
      if (!$result) {
        return false;
      }
      $ret = DB::_fetch($result);
      if ($ret === false) {
        $result = DB::_query($sql . "''", 'default report printer lookup failed');
        if (!$result) {
          return false;
        }
        $ret = DB::_fetch($result);
        if (!$ret) {
          return false;
        }
      }

      return static::get($ret['printer']);
    }
    /**
     * @static
     *
     * @param $name
     *
     * @return null|PDOStatement
     */
    public static function delete_profile($name) {
      $sql = "DELETE FROM print_profiles WHERE profile=" . DB::_escape($name);

      return DB::_query($sql, "could not delete printing profile");
    }
    //
    // Get all report destinations for given profile.
    //
    /**
     * @static
     *
     * @param $name
     *
     * @return null|PDOStatement
     */
    public static function get_profile($name) {
      $sql = "SELECT	* FROM print_profiles WHERE profile=" . DB::_escape($name);

      return DB::_query($sql, "could not get printing profile");
    }
  }
