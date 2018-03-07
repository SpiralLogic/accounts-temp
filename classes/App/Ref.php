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

  use ADV\Core\DB\DB;

  /** **/
  class Ref
  {
    /**
     * @static
     *
     * @param $type
     * @param $id
     * @param $reference
     */
    public static function add($type, $id, $reference) {
      $sql
        = "INSERT INTO refs (type, id, reference)
			VALUES (" . DB::_escape($type) . ", " . DB::_escape($id) . ", " . DB::_escape(trim($reference)) . ")";
      DB::_query($sql, "could not add reference entry");
      if ($reference != 'auto') {
        static::save_last($type);
      }
    }
    /**
     * @static
     *
     * @param $type
     * @param $reference
     *
     * @return bool
     */
    public static function find($type, $reference) {
      $sql    = "SELECT id FROM refs WHERE type=" . DB::_escape($type) . " AND reference=" . DB::_escape($reference);
      $result = DB::_query($sql, "could not query reference table");
      return (DB::_numRows($result) > 0);
    }
    /**
     * @static
     *
     * @param $type
     * @param $reference
     */
    public static function save($type, $reference) {
      $sql = "UPDATE sys_types SET next_reference= REPLACE(" . DB::_escape(trim($reference)) . ",prefix,'') WHERE type_id = " . DB::_escape($type);
      DB::_query($sql, "The next transaction ref for $type could not be updated");
    }
    /**
     * @static
     *
     * @param $type
     *
     * @return string
     */
    public static function get_next($type) {
      $sql    = "SELECT CONCAT(prefix,next_reference) FROM sys_types WHERE type_id = " . DB::_escape($type);
      $result = DB::_query($sql, "The last transaction ref for $type could not be retreived");
      $row    = DB::_fetchRow($result);
      $ref    = $row[0];
      if (!static::is_valid($ref)) {
        $db_info = SysTypes::get_db_info($type);
        $db_name = $db_info[0];
        $db_type = $db_info[1];
        $db_ref  = $db_info[3];
        if ($db_ref != null) {
          $sql = "SELECT $db_ref FROM $db_name ";
          if ($db_type != null) {
            $sql .= " AND $db_type=$type";
          }
          $sql .= " ORDER BY $db_ref DESC LIMIT 1";
          $result = DB::_query($sql, "The last transaction ref for $type could not be retreived");
          $result = DB::_fetch($result);
          $ref    = $result[0];
        }
      }
      $oldref = 'auto';
      while (!static::is_new($ref, $type) && ($oldref != $ref)) {
        $oldref = $ref;
        $ref    = static::increment($ref);
      }
      return $ref;
    }
    /**
     * @static
     *
     * @param $type
     * @param $id
     *
     * @return mixed
     */
    public static function get($type, $id) {
      $sql    = "SELECT * FROM refs WHERE type=" . DB::_escape($type) . " AND id=" . DB::_escape($id);
      $result = DB::_query($sql, "could not query reference table");
      $row    = DB::_fetch($result);
      return $row['reference'];
    }
    /**
     * @static
     *
     * @param $type
     * @param $id
     *
     * @return null|\PDOStatement
     */
    public static function delete($type, $id) {
      $sql = "DELETE FROM refs WHERE type=$type AND id=" . DB::_escape($id);
      return DB::_query($sql, "could not delete from reference table");
    }
    /**
     * @static
     *
     * @param $type
     * @param $id
     * @param $reference
     */
    public static function update($type, $id, $reference) {
      $sql = "UPDATE refs SET reference=" . DB::_escape($reference) . " WHERE type=" . DB::_escape($type) . " AND id=" . DB::_escape($id);
      DB::_query($sql, "could not update reference entry");
      if ($reference != 'auto') {
        static::save_last($type);
      }
    }
    /**
     * @static
     *
     * @param $type
     * @param $reference
     *
     * @return bool
     */
    public static function exists($type, $reference) {
      return (static::find($type, $reference) != null);
    }
    /**
     * @static
     *
     * @param $type
     */
    public static function save_last($type) {
      $next = static::increment(static::get_next($type));
      static::save($type, $next);
    }
    /**
     * @static
     *
     * @param $reference
     *
     * @return bool
     */
    public static function is_valid($reference) {
      return strlen(trim($reference)) > 0;
    }
    /**
     * @static
     *
     * @param $reference
     *
     * @return string
     */
    public static function increment($reference) {
      // New method done by Pete. So f.i. WA036 will increment to WA037 and so on.
      // If $reference contains at least one group of digits,
      // extract first didgits group and add 1, then put all together.
      // NB. preg_match returns 1 if the regex matches completely
      // also $result[0] holds entire string, 1 the first captured, 2 the 2nd etc.
      //
      if (preg_match('/^(\D*?)(\d+)(.*)/', $reference, $result) == 1) {
        list($all, $prefix, $number, $postfix) = $result;
        $dig_count = strlen($number); // How many digits? eg. 0003 = 4
        $fmt       = '%0' . $dig_count . 'd'; // Make a format string - leading zeroes
        $nextval   = sprintf($fmt, intval($number + 1)); // Add one on, and put prefix back on
        return $prefix . $nextval . $postfix;
      } else {
        return $reference . '1';
      }
    }
    /**
     * @static
     *
     * @param $ref
     * @param $type
     *
     * @return bool
     */
    public static function is_new($ref, $type) {
      $db_info = SysTypes::get_db_info($type);
      $db_name = $db_info[0];
      $db_type = $db_info[1];
      $db_ref  = $db_info[3];
      if ($db_ref != null) {
        $sql = "SELECT $db_ref FROM $db_name WHERE $db_ref='$ref'";
        if ($db_type != null) {
          $sql .= " AND $db_type=$type";
        }
        $result = DB::_query($sql, "could not test for unique reference");
        return (DB::_numRows($result) == 0);
      }
      // it's a type that doesn't use references - shouldn't be calling here, but say yes anyways
      return true;
    }
  }


