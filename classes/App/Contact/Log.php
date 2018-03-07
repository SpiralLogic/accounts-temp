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
  class Contact_Log {
    /** @var string **/
    static private $_table = 'contact_log';
    /**
     * @static
     *
     * @param $parent_id
     * @param $contact_name
     * @param $type
     * @param $message
     *
     * @internal param $contact_id
     * @return bool|string
     */
    public static function add($parent_id, $contact_name, $type, $message) {
      $sql = "INSERT INTO " . self::$_table . " (parent_id, contact_name, parent_type,
 message) VALUES (" . DB::_escape($parent_id) . "," . DB::_escape($contact_name) . "," . DB::_escape($type) . ",
 " . DB::_escape($message) . ")";
      DB::_query($sql, "Couldn't insert contact log");
      return DB::_insertId();
    }
    /**
     * @static
     *
     * @param $parent_id
     * @param $type
     *
     * @internal param $contact_id
     * @return array|bool
     */
    public static function read($parent_id, $type) {
      $sql     = "SELECT * FROM " . self::$_table . " WHERE parent_id=" . $parent_id . " AND parent_type=" . DB::_escape($type) . " ORDER BY date DESC";
      $result  = DB::_query($sql, "Couldn't get contact log entries");
      $results = [];
      while ($row = DB::_fetchAssoc($result)) {
        $results[] = $row;
      }
      return $results;
    }
  }
