<?php
  use ADV\App\Dates;

  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class DB_Comments
  {
    /** @var \ADV\Core\DB\DB */
    static $DB;
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     * @param $date_
     * @param $memo_
     */
    public static function add($type, $type_no, $date_, $memo_) {
      if ($memo_ != null && $memo_ != "") {
        $date = Dates::_dateToSql($date_);
        $sql
              = "INSERT INTO comments (type, id, date_, memo_)
                 VALUES (" . static::$DB->_escape($type) . ", " . static::$DB->_escape($type_no) . ", '$date', " . static::$DB->_escape($memo_) . ")";
        static::$DB->_query($sql, "could not add comments transaction entry");
      }
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    public static function delete($type, $type_no) {
      $sql = "DELETE FROM comments WHERE type=" . static::$DB->_escape($type) . " AND id=" . static::$DB->_escape($type_no);
      static::$DB->_query($sql, "could not delete from comments transaction table");
    }
    /**
     * @static
     *
     * @param $type
     * @param $id
     */
    public static function display_row($type, $id) {
      $comments = DB_Comments::get($type, $id);
      if ($comments and static::$DB->_numRows($comments)) {
        echo "<tr><td class='label'>Comments</td><td colspan=15>";
        while ($comment = static::$DB->_fetch($comments)) {
          echo $comment["memo_"] . "<br>";
        }
        echo "</td></tr>";
      }
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     *
     * @return null|PDOStatement
     */
    public static function get($type, $type_no) {
      $sql = "SELECT * FROM comments WHERE type=" . static::$DB->_escape($type) . " AND id=" . static::$DB->_escape($type_no);
      return static::$DB->_query($sql, "could not query comments transaction table");
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     *
     * @return string
     */
    public static function get_string($type, $type_no) {
      $str_return = "";
      $result     = DB_Comments::get($type, $type_no);
      while ($comment = static::$DB->_fetch($result)) {
        if (strlen($str_return)) {
          $str_return = $str_return . " \n";
        }
        $str_return = $str_return . $comment["memo_"];
      }
      return $str_return;
    }
    /**
     * @static
     *
     * @param $type
     * @param $id
     * @param $date_
     * @param $memo_
     */
    public static function update($type, $id, $date_, $memo_) {
      if ($date_ == null) {
        DB_Comments::delete($type, $id);
        DB_Comments::add($type, $id, Dates::_today(), $memo_);
      } else {
        $date = Dates::_dateToSql($date_);
        $sql  = "UPDATE comments SET memo_=" . static::$DB->_escape($memo_) . " WHERE type=" . static::$DB->_escape($type) . " AND id=" . static::$DB->_escape(
          $id
        ) . " AND date_='$date'";
        static::$DB->_query($sql, "could not update comments");
      }
    }
  }

  DB_Comments::$DB = \ADV\Core\DB\DB::i();
